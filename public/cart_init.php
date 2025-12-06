<?php
/**
 * Cart initialization helper
 * Include this early in the request lifecycle to ensure logged-in users have their cart loaded from DB
 */

if (!function_exists('initializeUserCart')) {
    function initializeUserCart() {
        try {
            // Check if user is logged in
            if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
                require_once __DIR__ . '/../app/Controllers/AuthController.php';
                $user = AuthController::getCurrentUser();
                
                if (!$user) {
                    // Guest user: no initialization needed
                    return;
                }
                
                $user_id = intval($user['id']);
                
                // If session cart is already populated, skip DB load
                if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                    return;
                }
                
                // Session cart is empty or not set; load from DB for this user
                require_once __DIR__ . '/../config/database.php';
                global $conn;
                
                // Find user's active cart
                $cStmt = $conn->prepare('SELECT id FROM carts WHERE user_id = :user_id AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
                $cStmt->execute([':user_id' => $user_id]);
                $cart = $cStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cart) {
                    // No active cart in DB; ensure session cart is empty
                    $_SESSION['cart'] = [];
                    return;
                }
                
                $cartId = intval($cart['id']);
                
                // Load cart items from DB into session
                $itStmt = $conn->prepare('SELECT product_id, product_name, price, quantity, image FROM cart_items WHERE cart_id = :cart_id');
                $itStmt->execute([':cart_id' => $cartId]);
                $items = $itStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $_SESSION['cart'] = [];
                foreach ($items as $it) {
                    $_SESSION['cart'][ $it['product_id'] ] = [
                        'id' => $it['product_id'],
                        'name' => $it['product_name'],
                        'price' => floatval($it['price']),
                        'quantity' => intval($it['quantity']),
                        'image' => $it['image']
                    ];
                }
                
            }
        } catch (Exception $e) {
            // If initialization fails, log error but don't block page load
            error_log('Cart initialization failed: ' . $e->getMessage());
        }
    }
}

// Call initialization
initializeUserCart();
?>
