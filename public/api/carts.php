<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$jsonInput = json_decode($raw, true);
$input = $jsonInput ?: $_POST;

// GET: list carts or single cart with items
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare('SELECT * FROM carts WHERE id = :id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $cart = $stmt->fetch();
        if (!$cart) { http_response_code(404); echo json_encode(['error'=>'Cart not found']); exit; }
        $stmt = $conn->prepare('SELECT * FROM cart_items WHERE cart_id = :id');
        $stmt->execute([':id'=>$id]);
        $items = $stmt->fetchAll();
        $cart['items'] = $items;
        echo json_encode($cart, JSON_UNESCAPED_UNICODE);
        exit;
    }
    // list carts (support pagination, search and status filter)
    $page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : null;
    $limit = isset($_GET['limit']) ? max(1,intval($_GET['limit'])) : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $q = isset($_GET['q']) ? trim($_GET['q']) : null;

    $where = [];
    $params = [];
    // Cleanup: cancel processing carts older than 10 minutes and remove their items
    try {
        $conn->exec("UPDATE carts SET status = 'cancelled', updated_at = NOW() WHERE status = 'processing' AND TIMESTAMPDIFF(MINUTE, IFNULL(updated_at, created_at), NOW()) > 10");
        // remove items for cancelled carts
        $conn->exec("DELETE ci FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE c.status = 'cancelled'");
    } catch (Exception $e) {
        // ignore cleanup errors
    }

    // By default, exclude carts that were already converted to orders (completed) or cancelled
    if ($status) { $where[] = 'c.status = :status'; $params[':status'] = $status; }
    else { $where[] = "c.status NOT IN ('completed','cancelled')"; }
    if ($q) { $where[] = '(c.session_id LIKE :q OR u.full_name LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // total count for pagination
    $countSql = 'SELECT COUNT(*) as cnt FROM carts c LEFT JOIN users u ON c.user_id = u.id ' . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    $stmtCount->execute($params);
    $totalRow = $stmtCount->fetch();
    $total = $totalRow ? intval($totalRow['cnt']) : 0;

    // data query with optional limit
    $dataSql = 'SELECT c.*, u.full_name AS user_name FROM carts c LEFT JOIN users u ON c.user_id = u.id ' . $whereSql . ' ORDER BY c.created_at DESC';
    if ($page && $limit){
        $offset = ($page - 1) * $limit;
        $dataSql .= ' LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);
    }
    $stmt = $conn->prepare($dataSql);
    $stmt->execute($params);
    $carts = $stmt->fetchAll();

    // fetch items for returned carts
    $cartIds = array_column($carts, 'id');
    $items = [];
    if (!empty($cartIds)){
        $in = implode(',', array_map('intval', $cartIds));
        $stmt2 = $conn->query('SELECT * FROM cart_items WHERE cart_id IN (' . $in . ')');
        $rows = $stmt2->fetchAll();
        foreach ($rows as $it){ $items[$it['cart_id']][] = $it; }
    }
    foreach ($carts as &$c){ $c['items'] = isset($items[$c['id']]) ? $items[$c['id']] : []; }

    echo json_encode(['data'=>$carts,'total'=>$total], JSON_UNESCAPED_UNICODE);
    exit;
}

// Create a new cart (optional)
if ($method === 'POST') {
    // minimal creation for admin/testing
    $user_id = !empty($input['user_id']) ? intval($input['user_id']) : null;
    $session_id = !empty($input['session_id']) ? $input['session_id'] : null;
    $status = !empty($input['status']) ? $input['status'] : 'pending';
    $total_amount = !empty($input['total_amount']) ? floatval($input['total_amount']) : 0;
    $stmt = $conn->query('SELECT MAX(id) as mx FROM carts'); $mx = $stmt->fetch();
    $newId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
    $sql = 'INSERT INTO carts (id,user_id,session_id,status,total_amount,created_at) VALUES (:id,:user_id,:session_id,:status,:total_amount,:created_at)';
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id'=>$newId,':user_id'=>$user_id,':session_id'=>$session_id,':status'=>$status,':total_amount'=>$total_amount,':created_at'=>date('Y-m-d H:i:s')]);
    echo json_encode(['success'=>true,'id'=>$newId]);
    exit;
}

// Update cart (PUT) - status, shipping_info, total_amount
if ($method === 'PUT') {
    if (empty($input['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    $allowed = ['status','total_amount','shipping_name','shipping_phone','shipping_address','user_id','session_id'];
    foreach ($allowed as $f){ if (isset($input[$f])){ $fields[] = "$f = :$f"; $params[':'.$f] = $input[$f]; } }
    if (empty($fields)) { echo json_encode(['success'=>true]); exit; }
    $params[':updated_at'] = date('Y-m-d H:i:s');
    // If status is being set to completed, create an order from cart items (transactional)
    // If status is being set to cancelled, restore inventory and delete items
    $isCompleting = isset($input['status']) && $input['status'] === 'completed';
    $isCancelling = isset($input['status']) && $input['status'] === 'cancelled';
    try {
        if ($isCancelling) {
            // Retrieve session_id for this cart so we can clear the user's session
            $stmtSession = $conn->prepare('SELECT session_id FROM carts WHERE id = :id LIMIT 1');
            $stmtSession->execute([':id' => $id]);
            $cartRow = $stmtSession->fetch(PDO::FETCH_ASSOC);
            $sessionToClear = $cartRow && !empty($cartRow['session_id']) ? $cartRow['session_id'] : null;

            // Load cart items to restore inventory
            $stmtItems = $conn->prepare('SELECT product_id, quantity FROM cart_items WHERE cart_id = :id');
            $stmtItems->execute([':id'=>$id]);
            $items = $stmtItems->fetchAll();
            // Restore inventory
            $restoreStmt = $conn->prepare('UPDATE products SET amount = amount + :qty WHERE id = :product_id');
            foreach ($items as $it) {
                $restoreStmt->execute([':qty'=>intval($it['quantity']), ':product_id'=>intval($it['product_id'])]);
            }
            // Delete cart items
            $delItems = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :id');
            $delItems->execute([':id'=>$id]);
            // Update cart status
            $sql = 'UPDATE carts SET status = :status, updated_at = :updated_at WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':status'=>'cancelled', ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$id]);

            // Attempt to clear PHP session data for that session id (works when session.save_handler = files)
            if ($sessionToClear) {
                try {
                    $saveHandler = ini_get('session.save_handler');
                    if ($saveHandler === 'files') {
                        $savePath = ini_get('session.save_path');
                        if (!$savePath) $savePath = sys_get_temp_dir();
                        // session files are named sess_{session_id}
                        $sessFile = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . $sessionToClear;
                        if (file_exists($sessFile)) {
                            @unlink($sessFile);
                            // also try to remove any related .lock files (some setups)
                            @unlink($sessFile . '.lock');
                        }
                    } else {
                        // For non-file session handlers (redis, memcached, etc.) we cannot reliably delete from here.
                        // As a fallback we mark the cart as cancelled (already done) and admin may notify user.
                    }
                } catch (Exception $ex) {
                    // ignore session clear errors
                }
            }

            echo json_encode(['success'=>true]);
            exit;
        }

        if ($isCompleting) {
            // load cart and items
            $stmt0 = $conn->prepare('SELECT * FROM carts WHERE id = :id FOR UPDATE');
            $conn->beginTransaction();
            $stmt0->execute([':id'=>$id]);
            $cart = $stmt0->fetch();
            if (!$cart) { $conn->rollBack(); http_response_code(404); echo json_encode(['error'=>'Cart not found']); exit; }
            if ($cart['status'] === 'completed') { $conn->rollBack(); echo json_encode(['success'=>true,'note'=>'already completed']); exit; }
            $stmtItems = $conn->prepare('SELECT * FROM cart_items WHERE cart_id = :id'); $stmtItems->execute([':id'=>$id]);
            $items = $stmtItems->fetchAll();
            // require Order model
            require_once __DIR__ . '/../../app/Models/Order.php';
            $orderModel = new Order($conn);
            // prepare cartItems in expected format for Order::create (id,name,price,quantity,image)
            $orderItems = [];
            $total = 0;
            foreach ($items as $it){
                $orderItems[] = ['id'=>$it['product_id'],'name'=>$it['product_name'],'price'=>$it['price'],'quantity'=>$it['quantity'],'image'=>$it['image']];
                $total += ($it['price'] * $it['quantity']);
            }
            $orderNumber = $orderModel->create($cart['user_id'], $orderItems, $total);
            // Deduct inventory when order is created
            $deductStmt = $conn->prepare('UPDATE products SET amount = amount - :qty WHERE id = :product_id');
            foreach ($items as $it) {
                $deductStmt->execute([':qty'=>intval($it['quantity']), ':product_id'=>intval($it['product_id'])]);
            }
            // update cart status
            $sql = 'UPDATE carts SET ' . implode(', ', $fields) . ', updated_at = :updated_at WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $conn->commit();
            echo json_encode(['success'=>true,'order_number'=>$orderNumber]);
            exit;
        } else {
            $sql = 'UPDATE carts SET ' . implode(', ', $fields) . ', updated_at = :updated_at WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success'=>true]);
            exit;
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error'=>$e->getMessage()]);
        exit;
    }
}

// DELETE cart and its items
if ($method === 'DELETE') {
    $id = null;
    if (isset($_GET['id'])) $id = intval($_GET['id']);
    elseif (!empty($input['id'])) $id = intval($input['id']);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $stmt = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :id'); $stmt->execute([':id'=>$id]);
    $stmt = $conn->prepare('DELETE FROM carts WHERE id = :id'); $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);



?>
