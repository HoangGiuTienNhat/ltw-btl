<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();
require_once __DIR__ . '/../../config/database.php';
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = isset($input['action']) ? $input['action'] : null;

// helper: get or create active cart for current session
function find_or_create_cart($conn, $user_id = null) {
    $sessionId = session_id();
    $stmt = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':session_id'=>$sessionId]);
    $found = $stmt->fetch();
    if ($found && !empty($found['id'])) return intval($found['id']);
    $stmt = $conn->query('SELECT MAX(id) as mx FROM carts'); $mx = $stmt->fetch();
    $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
    $ins = $conn->prepare('INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id,:user_id,:session_id,:status,:total_amount,:created_at)');
    $ins->execute([':id'=>$cartId, ':user_id'=>$user_id, ':session_id'=>$sessionId, ':status'=>'pending', ':total_amount'=>0, ':created_at'=>date('Y-m-d H:i:s')]);
    return $cartId;
}

try {
    if ($action === 'update_quantity' || $action === 'sync_items') {
        // expect product_id & quantity OR items array
        // update session
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if ($action === 'update_quantity') {
            $pid = isset($input['product_id']) ? (string)$input['product_id'] : null;
            $qty = isset($input['quantity']) ? intval($input['quantity']) : 0;
            if ($pid === null) { http_response_code(400); echo json_encode(['error'=>'Missing product_id']); exit; }
            if ($qty <= 0) { unset($_SESSION['cart'][$pid]); }
            else {
                if (!isset($_SESSION['cart'][$pid])) {
                    // minimal fields required
                    $_SESSION['cart'][$pid] = ['id'=>$pid, 'name'=>($input['name']?:''), 'price'=>($input['price']?:0), 'image'=>($input['image']?:null), 'quantity'=>$qty];
                } else {
                    $_SESSION['cart'][$pid]['quantity'] = $qty;
                }
            }
        } else {
            // sync_items: replace whole item list
            $_SESSION['cart'] = [];
            if (!empty($input['items']) && is_array($input['items'])) {
                foreach ($input['items'] as $it) {
                    $id = isset($it['id']) ? (string)$it['id'] : null; if (!$id) continue;
                    $qty = isset($it['quantity']) ? intval($it['quantity']) : 0; if ($qty<=0) continue;
                    $_SESSION['cart'][$id] = ['id'=>$id,'name'=>$it['name']??'','price'=>$it['price']??0,'quantity'=>$qty,'image'=>$it['image']??null];
                }
            }
        }
        // persist to DB
        $user_id = null;
        if (file_exists(__DIR__.'/../../app/Controllers/AuthController.php')) {
            require_once __DIR__.'/../../app/Controllers/AuthController.php';
            $u = AuthController::getCurrentUser(); $user_id = $u? $u['id'] : null;
        }
        $cartId = find_or_create_cart($conn, $user_id);
        // compute total
        $total = 0; foreach ($_SESSION['cart'] as $it) $total += ($it['price'] * $it['quantity']);
        $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
        $up->execute([':user_id'=>$user_id, ':total_amount'=>$total, ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
        // replace items
        $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id'); $del->execute([':cart_id'=>$cartId]);
        $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id,:product_id,:product_name,:price,:quantity,:image)');
        foreach ($_SESSION['cart'] as $it) {
            $ins->execute([':cart_id'=>$cartId, ':product_id'=>isset($it['id'])?$it['id']:null, ':product_name'=>$it['name'], ':price'=>$it['price'], ':quantity'=>$it['quantity'], ':image'=>isset($it['image'])?$it['image']:null]);
        }
        echo json_encode(['success'=>true,'cart_id'=>$cartId,'total'=>$total]);
        exit;
    }

    if ($action === 'set_processing') {
        // set session cart to processing and set updated_at
        $user_id = null;
        if (file_exists(__DIR__.'/../../app/Controllers/AuthController.php')) {
            require_once __DIR__.'/../../app/Controllers/AuthController.php';
            $u = AuthController::getCurrentUser(); $user_id = $u? $u['id'] : null;
        }
        $cartId = find_or_create_cart($conn, $user_id);
        // ensure items persisted
        $total = 0; if (!empty($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $it) $total += ($it['price'] * $it['quantity']); }
        $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, status = :status, updated_at = :updated_at WHERE id = :id');
        $up->execute([':user_id'=>$user_id, ':total_amount'=>$total, ':status'=>'processing', ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
        echo json_encode(['success'=>true,'cart_id'=>$cartId]);
        exit;
    }

    if ($action === 'confirm_payment') {
        // confirm payment: require logged-in user
        if (!file_exists(__DIR__.'/../../app/Controllers/AuthController.php')) { http_response_code(500); echo json_encode(['error'=>'Auth not available']); exit; }
        require_once __DIR__.'/../../app/Controllers/AuthController.php';
        $u = AuthController::getCurrentUser();
        if (!$u) { http_response_code(401); echo json_encode(['error'=>'not_logged_in']); exit; }
        // find active cart
        $sessionId = session_id();
        $stmt = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status = "processing" ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([':session_id'=>$sessionId]); $f = $stmt->fetch();
        if (!$f || empty($f['id'])) { http_response_code(404); echo json_encode(['error'=>'no_processing_cart']); exit; }
        $cartId = intval($f['id']);
        // create order using Order model
        if (!file_exists(__DIR__.'/../../app/Models/Order.php')) { http_response_code(500); echo json_encode(['error'=>'Order model missing']); exit; }
        require_once __DIR__.'/../../app/Models/Order.php';
        $orderModel = new Order();
        $totalMoney = 0; $items = [];
        if (!empty($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $it) { $totalMoney += ($it['price'] * $it['quantity']); $items[] = $it; } }
        $orderNumber = $orderModel->create($u['id'], $_SESSION['cart'], $totalMoney);
        // mark cart completed
        $upd = $conn->prepare('UPDATE carts SET status = :status, updated_at = :updated_at WHERE id = :id');
        $upd->execute([':status'=>'completed', ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
        // clear session cart
        unset($_SESSION['cart']);
        echo json_encode(['success'=>true,'order_number'=>$orderNumber]);
        exit;
    }

    http_response_code(400); echo json_encode(['error'=>'Invalid action']); exit;
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); exit;
}
