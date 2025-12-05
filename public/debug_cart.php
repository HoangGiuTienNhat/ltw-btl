<?php
/**
 * Debug endpoint để kiểm tra cart_add.php có lưu vào DB không
 */
header('Content-Type: application/json');

session_start();

// Thêm product vào session cart
$id = 5;
$name = 'Điện Thoại Test';
$price = 15000000;
$image = 'pic/test.jpg';
$quantity = 1;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to session
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

// Try to persist to database
$result = ['success' => false, 'error' => null];

try {
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception('Database connection failed');
    }

    $result['db_connected'] = true;

    // Get user
    $user_id = null;
    if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $user = AuthController::getCurrentUser();
        $user_id = $user ? intval($user['id']) : null;
    }

    $sessionId = session_id();
    $result['session_id'] = $sessionId;
    $result['user_id'] = $user_id;

    // Calculate total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $it) {
        $total_amount += (floatval($it['price']) * intval($it['quantity']));
    }
    $result['total_amount'] = $total_amount;

    // Check existing cart
    $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status NOT IN ("completed", "cancelled") ORDER BY created_at DESC LIMIT 1');
    $find->execute([':session_id' => $sessionId]);
    $found = $find->fetch(PDO::FETCH_ASSOC);
    $result['existing_cart'] = $found ? $found['id'] : null;

    if ($found && !empty($found['id'])) {
        $result['action'] = 'UPDATE';
        $cartId = intval($found['id']);
        $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
        $up->execute([':user_id' => $user_id, ':total_amount' => $total_amount, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $cartId]);

        // Delete old items
        $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $del->execute([':cart_id' => $cartId]);
        $result['deleted_items'] = $del->rowCount();
    } else {
        $result['action'] = 'INSERT';
        // Create new cart
        $stmt = $conn->query('SELECT MAX(id) as mx FROM carts');
        $mx = $stmt->fetch(PDO::FETCH_ASSOC);
        $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;

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
        $result['new_cart_id'] = $cartId;
    }

    // Insert items
    $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id, :product_id, :product_name, :price, :quantity, :image)');
    $inserted_count = 0;
    foreach ($_SESSION['cart'] as $it) {
        $ins->execute([
            ':cart_id' => $cartId,
            ':product_id' => isset($it['id']) ? intval($it['id']) : null,
            ':product_name' => $it['name'] ?? '',
            ':price' => floatval($it['price']),
            ':quantity' => intval($it['quantity']),
            ':image' => isset($it['image']) ? $it['image'] : null
        ]);
        $inserted_count++;
    }
    $result['inserted_items'] = $inserted_count;

    // Verify insertion
    $verify = $conn->prepare('SELECT COUNT(*) as cnt FROM carts WHERE session_id = :session_id');
    $verify->execute([':session_id' => $sessionId]);
    $verify_row = $verify->fetch(PDO::FETCH_ASSOC);
    $result['carts_count'] = $verify_row['cnt'];

    $verify2 = $conn->prepare('SELECT COUNT(*) as cnt FROM cart_items WHERE cart_id = :cart_id');
    $verify2->execute([':cart_id' => $cartId]);
    $verify2_row = $verify2->fetch(PDO::FETCH_ASSOC);
    $result['items_count_in_cart'] = $verify2_row['cnt'];

    $result['success'] = true;

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    $result['error_code'] = $e->getCode();
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
