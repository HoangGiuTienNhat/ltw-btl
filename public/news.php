<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Các bài viết về công nghệ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
        <div class="container-xxl">
            <a class="navbar-brand fw-bold text-primary fs-3 d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-box-seam-fill"></i> TechStore
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="products.php">Sản phẩm</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="news.php">Bài viết</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="contact.php">Liên hệ</a></li>
                </ul>
                
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 mt-3 mt-lg-0">
                    <div class="d-flex align-items-center gap-3 justify-content-between justify-content-lg-start">
                        <div class="d-flex gap-3 align-items-center">
                            <a href="cart.php" class="text-dark fs-5 position-relative">
                                <i class="bi bi-cart3"></i>
                                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5em;">
                                    <?php 
                                        $cart_count = 0;
                                        if(isset($_SESSION['cart'])) {
                                            foreach($_SESSION['cart'] as $item) $cart_count += $item['quantity'];
                                        }
                                        echo $cart_count;
                                    ?>
                                </span>
                            </a>
                            <a href="history.php" class="text-decoration-none text-dark fw-semibold small hover-underline">
                                <i class="bi bi-clock-history"></i> Lịch sử
                            </a>
                        </div>

                        <?php
                        require_once '../app/Controllers/AuthController.php';
                        $currentUser = AuthController::getCurrentUser();
                        if($currentUser): ?>
                            <div class="dropdown">
                                <a href="#" class="btn btn-outline-dark btn-sm rounded-pill dropdown-toggle d-flex align-items-center gap-1" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> 
                                    <span class="text-truncate" style="max-width: 100px;"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="profile.php">Tài khoản</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php">Đăng xuất</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-3 text-white">Đăng nhập</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="flex-grow-1 d-flex flex-column">
        <div class="container py-4" style="background-color: #f5f5f5;">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Bài viết</li>
                </ol>
            </nav>
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Các bài viết</h2>
                
                <form id="search-form" class="m-2" onsubmit="handleSearch(event)" style="max-width: 400px; width: 100%;">
                    <div class="input-group shadow-sm">
                        <input type="text" id="search-keyword" class="form-control border-1 border-info" placeholder="Nhập từ khóa tiêu đề, nội dung..." aria-label="Từ khóa tìm kiếm">
                        <button class="btn btn-info" type="button" onclick="handleSearch(event)">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div id="news-container" class="row row-cols-1 row-cols-md-3 g-4"></div>
            <nav aria-label="Page navigation" class="mt-5">
                <ul id="pagination" class="pagination justify-content-center"></ul>
            </nav>
        </div>

        <script src="news.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                loadNewsList('news-container', 6, 1); 
            });
        </script>
<?php include '../app/Views/layouts/footer.php'; ?>