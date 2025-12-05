<?php
session_start();
require_once '../config/database.php';
require_once '../app/Controllers/AuthController.php';
$currentUser = AuthController::getCurrentUser();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM news WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if ($article) {
    $page_title = $article['title']; 
    $page_desc = mb_substr(strip_tags($article['content']), 0, 50) . '...';
    $page_image = 'http://localhost/ltw-btl/public/news_uploads/' . $article['image'];
    $page_url = "http://localhost/ltw-btl/public/news_detail.php?id=" . $id;
} else {
    $page_title = "Tin tức không tồn tại";
    $page_desc = "Trang tin tức không tìm thấy nội dung yêu cầu.";
    $page_image = "http://localhost/ltw-btl/public/news_uploads/default.jpg";
    $page_url = "http://localhost/ltw-btl/public/news.php";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $page_url; ?>" />

    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>" />
    <meta property="og:image" content="<?php echo $page_image; ?>" />
    <meta property="og:url" content="<?php echo $page_url; ?>" />

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
        <div class="container py-4" id="news-content" style="background-color: #f5f5f5;">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
                            <li class="breadcrumb-item"><a href="news.php" class="text-decoration-none text-dark">Tin tức</a></li>
                            <li class="breadcrumb-item active">Chi tiết</li>
                        </ol>
                    </nav>

                    <article class="bg-white p-4 p-md-5 rounded shadow-sm border mb-5">
                        <img id="viewImage" src="" class="img-fluid object-fit-contain d-block" alt="ảnh bìa bài viết" loading="lazy" style="display: none;">
                        <h2 class="fw-bold my-3 display-6" id="news-title">Loading...</h2>
                        <div class="text-muted mb-4 border-bottom pb-3">
                            <i class="bi bi-calendar3 me-2"></i> <span id="news-date"></span>
                        </div>
                        <div id="news-body" style="line-height: 1.6; font-size: calc(0.7rem + 0.5vw);">
                        </div>
                    </article>

                    <div class="bg-light p-4 rounded border" id="comments-section">
                        <h4 class="fw-bold mb-4">Bình luận (<span id="comment-count">0</span>)</h4>
                        
                        <?php if($currentUser): ?>
                            <form id="comment-form" onsubmit="submitComment(event)" class="mb-4">
                                <div class="mb-3">
                                    <textarea class="form-control" rows="3" placeholder="Nhập bình luận..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary px-4">Gửi</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Vui lòng <a href="login.php" class="fw-bold text-decoration-none">đăng nhập</a> để bình luận.
                            </div>
                        <?php endif; ?>

                        <div id="comment-list">
                        </div>
                        <nav aria-label="Comment navigation" class="mt-4">
                            <ul id="comment-pagination" class="pagination justify-content-center">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="liveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2"></i> Đã gửi bình luận, vui lòng chờ duyệt.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-white pt-5 pb-3 border-top shadow-sm" >
        <div class="container-xxl">
            <div class="row g-4">
                <div class="col-12 col-md-4 text-center text-md-start">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="bi bi-box-seam-fill"></i> TechStore
                    </h5>
                    <p class="text-muted small">
                        Chuyên cung cấp sản phẩm công nghệ chính hãng, uy tín hàng đầu. Cam kết chất lượng và dịch vụ hậu mãi tận tâm.
                    </p>
                </div>
                
                <div class="col-12 col-md-4 text-center text-md-start">
                    <h6 class="fw-bold mb-3">Liên hệ</h6>
                    <ul class="list-unstyled text-muted small mb-0">
                        <li class="mb-2"><i class="bi bi-geo-alt me-1"></i> 123 Đường ABC, Quận 1, TP.HCM</li>
                        <li class="mb-2"><i class="bi bi-telephone me-1"></i> 0123 456 789</li>
                        <li><i class="bi bi-envelope me-1"></i> hotro@techstore.com</li>
                    </ul>
                </div>
                
                <div class="col-12 col-md-4 text-center text-md-end">
                    <h6 class="fw-bold mb-3">Kết nối với chúng tôi</h6>
                    <div class="d-flex justify-content-center justify-content-md-end gap-2">
                        <a href="#" class="btn btn-outline-primary btn-sm rounded-circle" style="width: 32px; height: 32px; display: grid; place-items: center;"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-danger btn-sm rounded-circle" style="width: 32px; height: 32px; display: grid; place-items: center;"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="btn btn-outline-info btn-sm rounded-circle" style="width: 32px; height: 32px; display: grid; place-items: center;"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="text-center text-muted small mt-4 pt-3 border-top">
                &copy; 2025 TechStore. All rights reserved. Designed for performance.
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="news_detail.js"></script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "NewsArticle",
        "headline": "<?php echo htmlspecialchars($page_title);?>",
        "image": [
            "<?php echo $page_image;?>"
        ],
        "datePublished": "<?php echo $article['created_at'];?>",
        "author": {
            "@type": "Person",
            "name": "Admin" 
        }
    }
    </script>
</body>
</html>