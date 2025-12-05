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

        // Check inventory before adding to cart
        require_once __DIR__ . '/../config/database.php';
        $chkStmt = $conn->prepare('SELECT amount FROM products WHERE id = :id LIMIT 1');
        $chkStmt->execute([':id'=>$id]);
        $product = $chkStmt->fetch();
        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }
        $available = intval($product['amount']);
        $newQty = $quantity;
        if (isset($_SESSION['cart'][$id])) {
            $newQty = $_SESSION['cart'][$id]['quantity'] + $quantity;
        }
        if ($newQty > $available) {
            echo json_encode(['status' => 'error', 'message' => 'Số lượng vượt quá hàng hiện có. Còn lại: '.$available]);
            exit;
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

        // Persist current session cart into DB only for logged-in users
        $user_id = null;
        if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
            require_once __DIR__ . '/../app/Controllers/AuthController.php';
            $user = AuthController::getCurrentUser();
            $user_id = $user ? intval($user['id']) : null;
        }

        if ($user_id) {
            $cart_persist_log = [
                'attempted' => true,
                'success' => false,
                'steps' => []
            ];
            try {
                if (!isset($conn) || !($conn instanceof PDO)) {
                    require_once __DIR__ . '/../config/database.php';
                    $cart_persist_log['steps'][] = 'DB connection loaded';
                }
                // compute total amount
                $total_amount = 0;
                foreach ($_SESSION['cart'] as $it) {
                    $total_amount += (floatval($it['price']) * intval($it['quantity']));
                }
                // find existing active cart for this user
                $find = $conn->prepare('SELECT id FROM carts WHERE user_id = :user_id AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
                $find->execute([':user_id' => $user_id]);
                $found = $find->fetch(PDO::FETCH_ASSOC);
                if ($found && !empty($found['id'])) {
                    $cartId = intval($found['id']);
                    $up = $conn->prepare('UPDATE carts SET total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
                    $up->execute([':total_amount' => $total_amount, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $cartId]);
                    $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
                    $del->execute([':cart_id' => $cartId]);
                } else {
                    $stmt = $conn->query('SELECT MAX(id) as mx FROM carts');
                    $mx = $stmt->fetch(PDO::FETCH_ASSOC);
                    $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
                    $sql = 'INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id, :user_id, :session_id, :status, :total_amount, :created_at)';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':id' => $cartId,
                        ':user_id' => $user_id,
                        ':session_id' => session_id(),
                        ':status' => 'pending',
                        ':total_amount' => $total_amount,
                        ':created_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id, :product_id, :product_name, :price, :quantity, :image)');
                foreach ($_SESSION['cart'] as $it) {
                    $ins->execute([
                        ':cart_id' => $cartId,
                        ':product_id' => isset($it['id']) ? intval($it['id']) : null,
                        ':product_name' => $it['name'] ?? '',
                        ':price' => floatval($it['price']),
                        ':quantity' => intval($it['quantity']),
                        ':image' => isset($it['image']) ? $it['image'] : null
                    ]);
                }
            } catch (Exception $e) {
                error_log('Failed to persist session cart for user ' . $user_id . ': ' . $e->getMessage());
            }
        } else {
            // Guest: persist cart into DB as a guest cart (user_id = NULL) so admin can see it
            try {
                if (!isset($conn) || !($conn instanceof PDO)) {
                    require_once __DIR__ . '/../config/database.php';
                }
                // compute total amount
                $total_amount = 0;
                foreach ($_SESSION['cart'] as $it) {
                    $total_amount += (floatval($it['price']) * intval($it['quantity']));
                }
                // find existing active cart for this session (guest)
                $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND (user_id IS NULL OR user_id = "") AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
                $find->execute([':session_id' => session_id()]);
                $found = $find->fetch(PDO::FETCH_ASSOC);
                if ($found && !empty($found['id'])) {
                    $cartId = intval($found['id']);
                    $up = $conn->prepare('UPDATE carts SET total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
                    $up->execute([':total_amount' => $total_amount, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $cartId]);
                    $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
                    $del->execute([':cart_id' => $cartId]);
                } else {
                    $stmt = $conn->query('SELECT MAX(id) as mx FROM carts');
                    $mx = $stmt->fetch(PDO::FETCH_ASSOC);
                    $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
                    $sql = 'INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id, NULL, :session_id, :status, :total_amount, :created_at)';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':id' => $cartId,
                        ':session_id' => session_id(),
                        ':status' => 'pending',
                        ':total_amount' => $total_amount,
                        ':created_at' => date('Y-m-d H:i:s')
                    ]);
                }
                $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id, :product_id, :product_name, :price, :quantity, :image)');
                foreach ($_SESSION['cart'] as $it) {
                    $ins->execute([
                        ':cart_id' => $cartId,
                        ':product_id' => isset($it['id']) ? intval($it['id']) : null,
                        ':product_name' => $it['name'] ?? '',
                        ':price' => floatval($it['price']),
                        ':quantity' => intval($it['quantity']),
                        ':image' => isset($it['image']) ? $it['image'] : null
                    ]);
                }
            } catch (Exception $e) {
                error_log('Failed to persist guest session cart: ' . $e->getMessage());
            }
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