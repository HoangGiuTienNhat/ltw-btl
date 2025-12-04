<?php
session_start();

// Xử lý Xóa hoặc Cập nhật (Logic đơn giản ngay tại file view để tiện demo)
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $removeId = $_GET['id'];
    unset($_SESSION['cart'][$removeId]);
    // Persist change to DB: update or delete the cart for this session
    try {
        require_once __DIR__ . '/../config/database.php';
        if (file_exists(__DIR__ . '/../app/Controllers/AuthController.php')) {
            require_once __DIR__ . '/../app/Controllers/AuthController.php';
            $user = AuthController::getCurrentUser();
            $user_id = $user ? $user['id'] : null;
        } else {
            $user_id = null;
        }

        $sessionId = session_id();
        // find existing active cart for this session
        $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status <> "completed" ORDER BY created_at DESC LIMIT 1');
        $find->execute([':session_id'=>$sessionId]);
        $found = $find->fetch();
        if (!empty($_SESSION['cart'])) {
            // still have items -> update total and items
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $it) { $total_amount += ($it['price'] * $it['quantity']); }
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
            // insert current items
            $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id,:product_id,:product_name,:price,:quantity,:image)');
            foreach ($_SESSION['cart'] as $it){
                $ins->execute([':cart_id'=>$cartId, ':product_id'=>isset($it['id'])?$it['id']:null, ':product_name'=>$it['name'], ':price'=>$it['price'], ':quantity'=>$it['quantity'], ':image'=>isset($it['image'])?$it['image']:null]);
            }
        } else {
            // cart is empty -> delete persisted cart if exists
            if ($found && !empty($found['id'])){
                $cartId = intval($found['id']);
                $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id'); $del->execute([':cart_id'=>$cartId]);
                $del2 = $conn->prepare('DELETE FROM carts WHERE id = :id'); $del2->execute([':id'=>$cartId]);
            }
        }
    } catch (Exception $e) {
        error_log('Failed to persist cart remove action: '.$e->getMessage());
    }

    $_SESSION['cart_message'] = 'Đã xóa sản phẩm khỏi giỏ hàng';
    header('Location: cart.php');
    exit;
}

// Xử lý thanh toán
if (isset($_GET['action']) && $_GET['action'] == 'checkout') {
    require_once '../app/Controllers/AuthController.php';
    // Allow saving the session cart for guests as well — get current user if logged in
    $user = AuthController::getCurrentUser();

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        $_SESSION['error'] = 'Giỏ hàng trống!';
        header('Location: cart.php');
        exit;
    }

    require_once '../app/Models/Order.php';
    $orderModel = new Order();

    $totalMoney = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalMoney += $item['price'] * $item['quantity'];
    }

    // --- Persist session cart into carts/cart_items for admin tracking ---
    // require DB connection and save cart + items (store even for guests: user_id may be null)
    try{
        // config is located at project-web/config/database.php; from public/ go up one level
        require_once __DIR__ . '/../config/database.php';
        $sessionId = session_id();
        $user_id = $user ? $user['id'] : null;
        // try to find existing active cart for this session (prevent duplicate carts on repeated checkout)
        $find = $conn->prepare('SELECT id FROM carts WHERE session_id = :session_id AND status <> "completed" ORDER BY created_at DESC LIMIT 1');
        $find->execute([':session_id'=>$sessionId]);
        $found = $find->fetch();
        if ($found && !empty($found['id'])){
            $cartId = intval($found['id']);
            // update totals and updated_at
            $up = $conn->prepare('UPDATE carts SET user_id = :user_id, total_amount = :total_amount, updated_at = :updated_at WHERE id = :id');
            $up->execute([':user_id'=>$user_id, ':total_amount'=>$totalMoney, ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
            // remove previous items and re-insert
            $del = $conn->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id'); $del->execute([':cart_id'=>$cartId]);
        } else {
            $stmt = $conn->query('SELECT MAX(id) as mx FROM carts'); $mx = $stmt->fetch();
            $cartId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;
            $sql = 'INSERT INTO carts (id, user_id, session_id, status, total_amount, created_at) VALUES (:id,:user_id,:session_id,:status,:total_amount,:created_at)';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id'=>$cartId, ':user_id'=>$user_id, ':session_id'=>$sessionId, ':status'=>'pending', ':total_amount'=>$totalMoney, ':created_at'=>date('Y-m-d H:i:s')]);
        }

        // insert items
        $ins = $conn->prepare('INSERT INTO cart_items (cart_id, product_id, product_name, price, quantity, image) VALUES (:cart_id,:product_id,:product_name,:price,:quantity,:image)');
        foreach ($_SESSION['cart'] as $item){
            $ins->execute([':cart_id'=>$cartId, ':product_id'=>isset($item['id'])?$item['id']:null, ':product_name'=>$item['name'], ':price'=>$item['price'], ':quantity'=>$item['quantity'], ':image'=>isset($item['image'])?$item['image']:null]);
        }
    }catch(Exception $e){
        // If DB save fails, log error
        error_log('Failed to save cart to DB: '.$e->getMessage());
    }

    // If user is not logged in, redirect to login so they can complete checkout (cart already persisted)
    if (!$user) {
        $_SESSION['info'] = 'Giỏ hàng đã được lưu. Vui lòng đăng nhập để hoàn tất thanh toán.';
        header('Location: login.php');
        exit;
    }

    $orderNumber = $orderModel->create($user['id'], $_SESSION['cart'], $totalMoney);

    // Mark persisted cart as completed (converted to order) so admin carts view shows only active carts
    try{
        if (!empty($cartId)){
            $upd = $conn->prepare('UPDATE carts SET status = :status, updated_at = :updated_at WHERE id = :id');
            $upd->execute([':status'=>'completed', ':updated_at'=>date('Y-m-d H:i:s'), ':id'=>$cartId]);
        }
    }catch(Exception $e){
        error_log('Failed to update cart status after order: '.$e->getMessage());
    }

    // Clear session cart
    unset($_SESSION['cart']);

    $_SESSION['order_success'] = 'Đặt hàng thành công! Mã đơn hàng: ' . $orderNumber;
    header('Location: history.php');
    exit;
}

include '../app/Views/layouts/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<?php if (isset($_SESSION['cart_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Toastify({
            text: "<?= addslashes($_SESSION['cart_message']) ?>",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
            stopOnFocus: true
        }).showToast();
    });
</script>
<?php unset($_SESSION['cart_message']); ?>
<?php endif; ?>

<div class="container py-5 flex-grow-1" style="background-color: #f5f5f5;">
    <h2 class="fw-bold mb-4">Giỏ hàng của bạn</h2>

    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tạm tính</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_money = 0;
                            foreach ($_SESSION['cart'] as $item): 
                                $line_total = $item['price'] * $item['quantity'];
                                $total_money += $line_total;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $item['image'] ?? 'https://via.placeholder.com/50' ?>" alt="" style="width: 50px; height: 50px; object-fit: cover;" class="rounded me-3">
                                        <div>
                                            <h6 class="mb-0"><?= $item['name'] ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm text-center" value="<?= $item['quantity'] ?>" min="1" style="width: 60px;" readonly>
                                    </td>
                                <td class="fw-bold text-danger"><?= number_format($line_total, 0, ',', '.') ?>đ</td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="text-danger" onclick="return confirm('Xóa sản phẩm này?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Tổng đơn hàng</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tạm tính:</span>
                            <span class="fw-bold"><?= number_format($total_money, 0, ',', '.') ?>đ</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Tổng cộng:</span>
                            <span class="fs-5 fw-bold text-danger"><?= number_format($total_money, 0, ',', '.') ?>đ</span>
                        </div>
                        <a href="#" onclick="confirmCheckout()" class="btn btn-dark w-100 py-2">Tiến hành thanh toán</a>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Tiếp tục mua sắm</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <p class="mt-3 text-muted">Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="btn btn-primary">Mua sắm ngay</a>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmCheckout() {
    if (confirm('Bạn có chắc chắn muốn thanh toán đơn hàng này?')) {
        window.location.href = 'cart.php?action=checkout';
    }
}
</script>

<?php include '../app/Views/layouts/footer.php'; ?>