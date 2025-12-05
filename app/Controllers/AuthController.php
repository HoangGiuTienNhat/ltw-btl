<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Utils/JWT.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'full_name' => trim($_POST['full_name']),
                'email' => trim($_POST['email']),
                'password' => $_POST['password'],
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? '')
            ];

            // Validation
            if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
                $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
                header('Location: register.php');
                exit;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Email không hợp lệ.';
                header('Location: register.php');
                exit;
            }

            if (strlen($data['password']) < 6) {
                $_SESSION['error'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
                header('Location: register.php');
                exit;
            }

            if ($this->userModel->findByEmail($data['email'])) {
                $_SESSION['error'] = 'Email đã được sử dụng.';
                header('Location: register.php');
                exit;
            }

            if ($this->userModel->create($data)) {
                $_SESSION['success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header('Location: login.php');
                exit;
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại.';
                header('Location: register.php');
                exit;
            }
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $_SESSION['error'] = 'Vui lòng điền email và mật khẩu.';
                header('Location: login.php');
                exit;
            }

            $user = $this->userModel->findByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                $payload = [
                    'iss' => 'techstore',
                    'iat' => time(),
                    'exp' => time() + (7 * 24 * 60 * 60), // 7 days
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];

                $token = JWT::encode($payload);
                setcookie('auth_token', $token, time() + (7 * 24 * 60 * 60), '/', '', false, true); // HttpOnly

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];

                // Merge any guest/session cart into this user's cart so admin-visible guest carts are preserved
                $this->mergeGuestCartIntoUser($user['id'], session_id());

                header('Location: index.php');
                exit;
            } else {
                $_SESSION['error'] = 'Email hoặc mật khẩu không đúng.';
                header('Location: login.php');
                exit;
            }
        }
    }

    /**
     * Merge a guest cart (identified by session_id) into the user's active cart.
     * If the user has an existing active cart, merge items and respect product inventory.
     * If only a guest cart exists, attach it to the user.
     */
    private function mergeGuestCartIntoUser($userId, $sessionId) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            global $conn;
            // find active guest cart by session_id
            $gStmt = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND (user_id IS NULL OR user_id = "") AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
            $gStmt->execute([':session_id' => $sessionId]);
            $guest = $gStmt->fetch(PDO::FETCH_ASSOC);

            // find active user cart
            $uStmt = $conn->prepare('SELECT id FROM carts WHERE user_id = :user_id AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
            $uStmt->execute([':user_id' => $userId]);
            $userCart = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$guest) {
                // nothing to merge
                return;
            }

            $guestId = intval($guest['id']);

            if (!$userCart) {
                // Attach guest cart to user (simple case)
                $up = $conn->prepare('UPDATE carts SET user_id = :user_id, updated_at = :updated_at WHERE id = :id');
                $up->execute([':user_id' => $userId, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $guestId]);
                // update session representation from DB
                $this->refreshSessionCartFromDb($userId);
                return;
            }

            // Merge guest into user's existing cart
            $userIdCart = intval($userCart['id']);

            // Begin transaction
            $conn->beginTransaction();

            // Load guest items
            $gItemsStmt = $conn->prepare('SELECT product_id, product_name, price, quantity, image FROM cart_items WHERE cart_id = :cart_id');
            $gItemsStmt->execute([':cart_id' => $guestId]);
            $guestItems = $gItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // For each guest item, merge into user's cart
            foreach ($guestItems as $gIt) {
                $pid = $gIt['product_id'];
                $gQty = intval($gIt['quantity']);

                // check if product already in user cart
                $check = $conn->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :pid LIMIT 1');
                $check->execute([':cart_id' => $userIdCart, ':pid' => $pid]);
                $existing = $check->fetch(PDO::FETCH_ASSOC);

                // get product inventory
                $prodStmt = $conn->prepare('SELECT amount FROM products WHERE id = :id LIMIT 1');
                $prodStmt->execute([':id' => $pid]);
                $prod = $prodStmt->fetch(PDO::FETCH_ASSOC);
                $available = $prod ? intval($prod['amount']) : 0;

                if ($existing) {
                    $newQty = intval($existing['quantity']) + $gQty;
                    if ($newQty > $available) {
                        $newQty = $available;
                    }
                    $upd = $conn->prepare('UPDATE cart_items SET quantity = :q WHERE id = :id');
                    $upd->execute([':q' => $newQty, ':id' => $existing['id']]);
                } else {
                    $insertQty = $gQty;
                    if ($insertQty > $available) {
                        $insertQty = $available;
                    }
                    $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id, :product_id, :product_name, :price, :quantity, :image)');
                    $ins->execute([':cart_id' => $userIdCart, ':product_id' => $pid, ':product_name' => $gIt['product_name'], ':price' => $gIt['price'], ':quantity' => $insertQty, ':image' => $gIt['image']]);
                }
            }

            // Recalculate total for user cart
            $sumStmt = $conn->prepare('SELECT SUM(price * quantity) as total FROM cart_items WHERE cart_id = :cart_id');
            $sumStmt->execute([':cart_id' => $userIdCart]);
            $sum = $sumStmt->fetch(PDO::FETCH_ASSOC);
            $total = $sum && $sum['total'] ? floatval($sum['total']) : 0.0;
            $upCart = $conn->prepare('UPDATE carts SET total_amount = :total, updated_at = :updated_at WHERE id = :id');
            $upCart->execute([':total' => $total, ':updated_at' => date('Y-m-d H:i:s'), ':id' => $userIdCart]);

            // Remove guest cart and its items
            $delItems = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
            $delItems->execute([':cart_id' => $guestId]);
            $delCart = $conn->prepare('DELETE FROM carts WHERE id = :id');
            $delCart->execute([':id' => $guestId]);

            $conn->commit();

            // Refresh session cart data to reflect merged cart
            $this->refreshSessionCartFromDb($userId);

        } catch (Exception $e) {
            if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Error merging guest cart into user ' . $userId . ': ' . $e->getMessage());
        }
    }

    // Load user's active cart items from DB into session to keep UI in sync
    private function refreshSessionCartFromDb($userId) {
        try {
            require_once __DIR__ . '/../../config/database.php';
            global $conn;
                global $conn;
            $cStmt = $conn->prepare('SELECT id FROM carts WHERE user_id = :user_id AND status NOT IN ("completed","cancelled") ORDER BY created_at DESC LIMIT 1');
            $cStmt->execute([':user_id' => $userId]);
            $cart = $cStmt->fetch(PDO::FETCH_ASSOC);
            if (!$cart) {
                // clear session cart
                $_SESSION['cart'] = [];
                return;
            }
            $cartId = intval($cart['id']);
            $itStmt = $conn->prepare('SELECT product_id, product_name, price, quantity, image FROM cart_items WHERE cart_id = :cart_id');
            $itStmt->execute([':cart_id' => $cartId]);
            $items = $itStmt->fetchAll(PDO::FETCH_ASSOC);
            $_SESSION['cart'] = [];
            foreach ($items as $it) {
                $_SESSION['cart'][ $it['product_id'] ] = [
                    'id' => $it['product_id'],
                    'name' => $it['product_name'],
                    'price' => $it['price'],
                    'quantity' => intval($it['quantity']),
                    'image' => $it['image']
                ];
            }
        } catch (Exception $e) {
            error_log('Failed to refresh session cart from DB for user ' . $userId . ': ' . $e->getMessage());
        }
    }

    public function logout() {
        setcookie('auth_token', '', time() - 3600, '/');
        session_destroy();
        header('Location: index.php');
        exit;
    }

    public static function getCurrentUser() {
        if (isset($_COOKIE['auth_token'])) {
            $payload = JWT::decode($_COOKIE['auth_token']);
            if ($payload) {
                $userModel = new User();
                return $userModel->findById($payload['user_id']);
            }
        }
        return null;
    }

    public static function requireAuth() {
        $user = self::getCurrentUser();
        if (!$user) {
            header('Location: login.php');
            exit;
        }
        return $user;
    }
}
?>