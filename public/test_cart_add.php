<?php
/**
 * Test file để xác minh cart_add.php hoạt động và cập nhật DB
 */
session_start();

echo "<h2>Test Cart Add Functionality</h2>";

// 1. Check if cart_add.php exists
$cart_add_file = __DIR__ . '/cart_add.php';
echo "<p><strong>1. File cart_add.php tồn tại:</strong> " . (file_exists($cart_add_file) ? "✓ Yes" : "✗ No") . "</p>";

// 2. Check database connection
require_once __DIR__ . '/../config/database.php';
echo "<p><strong>2. Database connection:</strong> " . (isset($conn) && $conn instanceof PDO ? "✓ Connected" : "✗ Failed") . "</p>";

// 3. Check if tables exist
try {
    $tables = ['carts', 'cart_items', 'products'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        echo "<p><strong>3.$table Table:</strong> " . ($exists ? "✓ Exists" : "✗ Missing") . "</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>3. Table check failed:</strong> " . $e->getMessage() . "</p>";
}

// 4. Test adding a product to cart via POST
echo "<h3>Test: Simulate POST request to cart_add.php</h3>";

$_POST['product_id'] = 5;  // Example product ID
$_POST['product_name'] = 'Test Product';
$_POST['product_price'] = 10000000;
$_POST['product_image'] = 'pic/test.jpg';
$_POST['quantity'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<p><strong>Simulating POST request:</strong></p>";
echo "<pre>POST data: " . print_r($_POST, true) . "</pre>";

// Capture output
ob_start();
include 'cart_add.php';
$output = ob_get_clean();

echo "<p><strong>Response from cart_add.php:</strong></p>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// 5. Check database for new cart
try {
    $sessionId = session_id();
    $stmt = $conn->prepare('SELECT * FROM carts WHERE session_id = ? AND status != "completed" ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$sessionId]);
    $cart = $stmt->fetch();
    
    if ($cart) {
        echo "<h3>✓ Cart found in database</h3>";
        echo "<p><strong>Cart ID:</strong> " . $cart['id'] . "</p>";
        echo "<p><strong>Session ID:</strong> " . $cart['session_id'] . "</p>";
        echo "<p><strong>Status:</strong> " . $cart['status'] . "</p>";
        echo "<p><strong>Total Amount:</strong> " . $cart['total_amount'] . "</p>";
        
        // Get cart items
        $stmt2 = $conn->prepare('SELECT * FROM cart_items WHERE cart_id = ?');
        $stmt2->execute([$cart['id']]);
        $items = $stmt2->fetchAll();
        
        echo "<h4>Cart Items:</h4>";
        if (count($items) > 0) {
            foreach ($items as $item) {
                echo "<p>- " . $item['product_name'] . " x " . $item['quantity'] . " @ " . $item['price'] . "₫</p>";
            }
        } else {
            echo "<p>No items found</p>";
        }
    } else {
        echo "<h3>✗ No cart found in database for this session</h3>";
    }
} catch (Exception $e) {
    echo "<p><strong>Error checking database:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Current Session Cart:</strong></p>";
echo "<pre>" . print_r($_SESSION['cart'] ?? [], true) . "</pre>";
?>
