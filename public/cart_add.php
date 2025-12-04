<?php
session_start();

// Kiểm tra request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Lấy dữ liệu từ Ajax gửi sang
    $id = $_POST['product_id'] ?? null;
    $name = $_POST['product_name'] ?? '';
    $price = $_POST['product_price'] ?? 0;
    $image = $_POST['product_image'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($id) {
        // Khởi tạo giỏ hàng
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Logic thêm/cộng dồn
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'image' => $image
            ];
        }

        // Tính tổng số lượng sản phẩm trong giỏ để trả về client
        $total_items = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_items += $item['quantity'];
        }

        // --- Persist current session cart into DB so admin can view active carts ---
        try {
            require_once __DIR__ . '/../config/database.php';
            // optional: get current user if logged in
            if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
                require_once __DIR__ . '/../app/Controllers/AuthController.php';
                $user = AuthController::getCurrentUser();
                $user_id = $user ? $user['id'] : null;
            } else {
                $user_id = null;
            }

            $sessionId = session_id();
            // compute total amount
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $it) { $total_amount += ($it['price'] * $it['quantity']); }

            // find existing active cart for this session
            $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status <> "completed" ORDER BY created_at DESC LIMIT 1');
            $find->execute([':session_id'=>$sessionId]);
            $found = $find->fetch();
            if ($found && !empty($found['id'])){
                $cartId = intval($found['id']);
                $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
                $up->execute([':user_id'=>$user_id, ':total_amount'=>$total_amount, ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
                $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id'); $del->execute([':cart_id'=>$cartId]);
            } else {
                $stmt = $conn->query('SELECT MAX(id) as mx FROM carts'); $mx = $stmt->fetch();
                $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
                $sql = 'INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id,:user_id,:session_id,:status,:total_amount,:created_at)';
                $stmt = $conn->prepare($sql);
                $stmt->execute([':id'=>$cartId, ':user_id'=>$user_id, ':session_id'=>$sessionId, ':status'=>'pending', ':total_amount'=>$total_amount, ':created_at'=>date('Y-m-d H:i:s')]);
            }

            // insert cart items from session
            $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id,:product_id,:product_name,:price,:quantity,:image)');
            foreach ($_SESSION['cart'] as $it){
                $ins->execute([':cart_id'=>$cartId, ':product_id'=>isset($it['id'])?$it['id']:null, ':product_name'=>$it['name'], ':price'=>$it['price'], ':quantity'=>$it['quantity'], ':image'=>isset($it['image'])?$it['image']:null]);
            }
        } catch (Exception $e) {
            error_log('Failed to persist session cart: '.$e->getMessage());
        }

        // Trả về JSON thành công
        echo json_encode([
            'status' => 'success',
            'message' => 'Đã thêm sản phẩm vào giỏ!',
            'total_items' => $total_items
        ]);
    } else {
        // Lỗi thiếu ID
        echo json_encode(['status' => 'error', 'message' => 'Lỗi dữ liệu sản phẩm']);
    }
    exit();
}
?>