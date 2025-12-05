<?php
// Quick CLI test to create a guest cart and invoke mergeGuestCartIntoUser
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Start a session for completeness (not used by DB merge)
session_start();

$sessionId = 'cli_test_session_' . bin2hex(random_bytes(4));

try {
    // pick a product id
    $prod = $conn->query('SELECT id FROM products LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
        echo "No products found in database. Seed a product first.\n";
        exit(1);
    }
    $pid = $prod['id'];

    // ensure a user exists
    $user = $conn->query('SELECT id FROM users LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $pwd = password_hash('test1234', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (full_name,email,password,role,created_at) VALUES (:n,:e,:p,:r,:c)');
        $stmt->execute([':n'=>'CLI Test User',':e'=>'cli-test@example.com',':p'=>$pwd,':r'=>'user',':c'=>date('Y-m-d H:i:s')]);
        // attempt to get last insert id
        $userId = $conn->lastInsertId();
        if (!$userId) {
            $userId = $conn->query('SELECT id FROM users WHERE email = "cli-test@example.com" LIMIT 1')->fetchColumn();
        }
    } else {
        $userId = $user['id'];
    }

    // create guest cart id
    $mx = $conn->query('SELECT MAX(id) as mx FROM carts')->fetch(PDO::FETCH_ASSOC);
    $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;

    $insCart = $conn->prepare('INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id, NULL, :session, :status, :total, :created)');
    $insCart->execute([':id'=>$cartId, ':session'=>$sessionId, ':status'=>'pending', ':total'=>10.0, ':created'=>date('Y-m-d H:i:s')]);

    $insItem = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id,:product_id,:product_name,:price,:quantity,:image)');
    $insItem->execute([':cart_id'=>$cartId, ':product_id'=>$pid, ':product_name'=>'CLI Test Product', ':price'=>10.0, ':quantity'=>1, ':image'=>null]);

    echo "Created guest cart id={$cartId} session={$sessionId} with product={$pid}\n";

    // invoke merge via reflection (method is private)
    $ctrl = new AuthController();
    $ref = new ReflectionClass($ctrl);
    $method = $ref->getMethod('mergeGuestCartIntoUser');
    $method->setAccessible(true);
    $method->invoke($ctrl, $userId, $sessionId);

    echo "Invoked mergeGuestCartIntoUser for user={$userId}\n";

    // show resulting user cart
    $uCartStmt = $conn->prepare('SELECT id FROM carts WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
    $uCartStmt->execute([':user_id'=>$userId]);
    $uc = $uCartStmt->fetch(PDO::FETCH_ASSOC);
    if ($uc) {
        echo "User cart id={$uc['id']} items:\n";
        $items = $conn->prepare('SELECT product_id, product_name, price, quantity FROM cart_items WHERE cart_id = :cart_id');
        $items->execute([':cart_id'=>$uc['id']]);
        $rows = $items->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    } else {
        echo "No user cart found after merge\n";
    }

} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test completed.\n";
