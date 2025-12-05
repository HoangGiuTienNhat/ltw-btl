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

        // --- Persist current session cart into DB so admin can view active carts ---
        $cart_persist_log = [
            'attempted' => true,
            'success' => false,
            'steps' => []
        ];
        
        try {
            if (!isset($conn) || !($conn instanceof PDO)) {
                require_once __DIR__ . '/../config/database.php';
                $cart_persist_log['steps'][] = 'DB connection loaded';
            } else {
                $cart_persist_log['steps'][] = 'DB connection already available';
            }
            
            if (!isset($conn) || !($conn instanceof PDO)) {
                throw new Exception('Database connection not available after require');
            }
            
            // optional: get current user if logged in
            $user_id = null;
            if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
                require_once __DIR__ . '/../app/Controllers/AuthController.php';
                $user = AuthController::getCurrentUser();
                $user_id = $user ? intval($user['id']) : null;
                $cart_persist_log['steps'][] = 'User checked: ' . ($user_id ? 'Logged in (ID: ' . $user_id . ')' : 'Guest');
            } else {
                $cart_persist_log['steps'][] = 'AuthController not found, treating as guest';
            }

            $sessionId = session_id();
            $cart_persist_log['session_id'] = $sessionId;
            
            // compute total amount
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $it) { 
                $total_amount += (floatval($it['price']) * intval($it['quantity'])); 
            }
            $cart_persist_log['total_amount'] = $total_amount;

            // find existing active cart for this session
            $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status NOT IN ("completed", "cancelled") ORDER BY created_at DESC LIMIT 1');
            $find->execute([':session_id' => $sessionId]);
            $found = $find->fetch(PDO::FETCH_ASSOC);
            $cart_persist_log['steps'][] = 'Searched for existing cart: ' . ($found ? 'Found ID ' . $found['id'] : 'Not found');
            
            if ($found && !empty($found['id'])) {
                // Update existing cart
                $cartId = intval($found['id']);
                $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
                $up->execute([':user_id' => $user_id, ':total_amount' => $total_amount, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $cartId]);
                $cart_persist_log['steps'][] = 'Updated cart ID ' . $cartId;
                
                // Delete old cart items
                $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
                $del->execute([':cart_id' => $cartId]);
                $cart_persist_log['steps'][] = 'Deleted ' . $del->rowCount() . ' old items';
            } else {
                // Create new cart
                $stmt = $conn->query('SELECT MAX(id) as mx FROM carts');
                $mx = $stmt->fetch(PDO::FETCH_ASSOC);
                $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
                $cart_persist_log['steps'][] = 'Creating new cart, ID: ' . $cartId;
                
                $sql = 'INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id, :user_id, :session_id, :status, :total_amount, :created_at)';
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':id' => $cartId,
                    ':user_id' => $user_id,
                    ':session_id' => $sessionId,
                    ':status' => 'pending',
                    ':total_amount' => $total_amount,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                $cart_persist_log['steps'][] = 'Inserted new cart';
            }

            // Insert all cart items from session
            $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id, :product_id, :product_name, :price, :quantity, :image)');
            $items_inserted = 0;
            foreach ($_SESSION['cart'] as $it) {
                $ins->execute([
                    ':cart_id' => $cartId,
                    ':product_id' => isset($it['id']) ? intval($it['id']) : null,
                    ':product_name' => $it['name'] ?? '',
                    ':price' => floatval($it['price']),
                    ':quantity' => intval($it['quantity']),
                    ':image' => isset($it['image']) ? $it['image'] : null
                ]);
                $items_inserted++;
            }
            $cart_persist_log['steps'][] = 'Inserted ' . $items_inserted . ' items to cart';
            $cart_persist_log['success'] = true;
            
        } catch (Exception $e) {
            $cart_persist_log['error'] = $e->getMessage();
            $cart_persist_log['error_code'] = $e->getCode();
            error_log('Failed to persist session cart: ' . json_encode($cart_persist_log));
        }
        
        // Log persistence attempt (for admin debug if needed)
        @error_log('Cart persist log: ' . json_encode($cart_persist_log));

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