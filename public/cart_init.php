<?php
/**
 * Cart initialization helper
 * Include this early in the request lifecycle to ensure logged-in users have their cart loaded from DB
 *
 * - Start session if needed
 * - Ensure DB is required before any Model that depends on $conn
 * - Prefer $_SESSION['user'] (set at login) to avoid DB hit
 * - Defensive checks to prevent fatal errors when $conn is not available
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure DB connection is available for any Model usage (adjust path if needed)
$dbPath = __DIR__ . '/../config/database.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
}

if (!function_exists('initializeUserCart')) {
    function initializeUserCart() {
        try {
            // If user info already stored in session (set at login), use it first
            $user = null;
            if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                $user = $_SESSION['user'];
            } else {
                // If no session user, try to use AuthController to get current user (if exists)
                $authPath = __DIR__ . '/../app/Controllers/AuthController.php';
                if (file_exists($authPath)) {
                    require_once $authPath;
                    if (class_exists('AuthController') && method_exists('AuthController', 'getCurrentUser')) {
                        // getCurrentUser may query DB internally; we already required database above to ensure $conn exists
                        $user = AuthController::getCurrentUser();
                    }
                }
            }

            if (!$user) {
                // Guest user: no initialization needed
                return;
            }

            $user_id = intval($user['id']);

            // If session cart is already populated, skip DB load
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                return;
            }

            // Ensure $conn is available before any DB operations
            global $conn;
            if (!isset($conn) || $conn === null) {
                // Can't load cart without DB connection — fail silently (logged)
                error_log('Cart initialization skipped: $conn not available for user ' . $user_id);
                return;
            }

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
                // Use product_id as key to match your existing cart structure
                $_SESSION['cart'][ $it['product_id'] ] = [
                    'id' => $it['product_id'],
                    'name' => $it['product_name'],
                    'price' => floatval($it['price']),
                    'quantity' => intval($it['quantity']),
                    'image' => $it['image']
                ];
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
