<?php
session_start();

// 1. TỰ ĐỘNG TÌM VÀ KẾT NỐI DATABASE (giống footer.php)
$db_files = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../../../../config/database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/LTW-xampp/project-web/config/database.php',
];

foreach ($db_files as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

// 2. ĐƯỜNG DẪN ẢNH TỪ ADMIN (QUAN TRỌNG - giống footer)
// $admin_url = '/BTL/ltw-admin-main/ltw-admin-main/dashboard/task2/';

$admin_url = '/LTW-xampp/tabler-1.4.0-hienthuc/dashboard/task2/';

// 3. LẤY BANNER TỪ DATABASE
$banners = [];

if (isset($conn) && $conn instanceof PDO) {
    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'site_banner'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $banners = json_decode($row['setting_value'], true);
        }
    } catch (Exception $e) {
        // Không làm gì, dùng fallback
    }
}

// Nếu không có dữ liệu từ DB → fallback về banner cũ (hardcode)
if (empty($banners) || !is_array($banners)) {
    $banners = [
        'pic/banner-main1.webp',
        'pic/banner-main2.webp',
        'pic/banner-main3.webp'
    ];
}

// Helper: Map Icon
function getCategoryIcon($name) {
    $name = mb_strtolower($name, 'UTF-8');
    if (strpos($name, 'laptop') !== false) return 'bi-laptop';
    if (strpos($name, 'điện thoại') !== false) return 'bi-phone';
    if (strpos($name, 'tai nghe') !== false) return 'bi-headphones';
    if (strpos($name, 'đồng hồ') !== false) return 'bi-smartwatch';
    if (strpos($name, 'tablet') !== false) return 'bi-tablet';
    if (strpos($name, 'camera') !== false) return 'bi-camera';
    return 'bi-box-seam';
}

try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 16");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
    
    $stmtPhone = $conn->prepare("SELECT * FROM products WHERE category_id = 2 ORDER BY created_at DESC LIMIT 16");
    $stmtPhone->execute();
    $phone_products = $stmtPhone->fetchAll();

    $stmtTablet = $conn->prepare("SELECT * FROM products WHERE category_id = 5 ORDER BY created_at DESC LIMIT 16");
    $stmtTablet->execute();
    $tablet_products = $stmtTablet->fetchAll();

    $stmtCat = $conn->query("SELECT * FROM categories LIMIT 6");
    $categories = $stmtCat->fetchAll();

    $stmtNews = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 16");
    $news_list = $stmtNews->fetchAll();

} catch(Exception $e) { 
    $featured_products = []; $phone_products = []; $tablet_products = []; $categories = []; $news_list = [];
}

// Danh sách Thương hiệu (Tĩnh)
$brands = [
    ['img' => 'pic/brand1.webp', 'link' => 'products.php?q=iphone'],
    ['img' => 'pic/brand2.webp', 'link' => 'products.php?q=asus'],
    ['img' => 'pic/brand3.webp', 'link' => 'products.php?cat_id=1'],
    ['img' => 'pic/brand4.png', 'link' => 'products.php?q=samsung']
];

include '../app/Views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row g-3">
        
        <div class="col-xxl-2 d-none d-xxl-block text-end">
            <a href="products.php">
                <img src="banner-side.jpg" class="side-banner-img shadow img-fluid" style="max-width: 200px; margin-left: auto;">
            </a>
        </div>

        <div class="col-12 col-xxl-8" style="margin-top: 0;">
            <div class="main-wrapper p-3 p-md-4">
                
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

                <!-- CAROUSEL BANNER - BÂY GIỜ LẤY TỪ DATABASE -->
                <div id="homeCarousel" class="carousel slide mb-5 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($banners as $index => $banner): ?>
                            <?php 
                                // Xử lý đường dẫn ảnh (hỗ trợ cả string và array)
                                $imgPath = is_string($banner) ? $banner : ($banner['image'] ?? $banner['img'] ?? '');
                                
                                // Nếu là ảnh từ admin (có chứa "uploads/")
                                if (strpos($imgPath, 'uploads/') === 0) {
                                    $imgPath = $admin_url . $imgPath;
                                }
                            ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($imgPath) ?>" 
                                     class="d-block w-100 object-fit-cover" 
                                     style="max-height: 450px; min-height: 200px;"
                                     alt="Banner <?= $index + 1 ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
                <!-- HẾT PHẦN CAROUSEL ĐỘNG -->

                <!-- Phần còn lại giữ nguyên 100% như code cũ của bạn -->
                <div class="mb-5">
                    <h4 class="fw-bold mb-4 border-start border-4 border-primary ps-3">Danh mục nổi bật</h4>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2 g-md-3">
                        <?php foreach($categories as $cat): ?>
                        <div class="col text-center">
                            <a href="products.php?cat_id=<?= $cat['id'] ?>" class="text-decoration-none text-dark card h-100 p-3 border-0 shadow-sm hover-shadow transition-card bg-light">
                                <i class="<?= getCategoryIcon($cat['name']) ?> fs-1 text-primary mb-2"></i>
                                <div class="fw-semibold small text-truncate"><?= $cat['name'] ?></div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold border-start border-4 border-danger ps-3 m-0">Sản phẩm nổi bật</h4>
                    <a href="products.php" class="btn btn-outline-dark btn-sm rounded-pill">Xem tất cả <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="position-relative mb-5">
                    <div class="swiper product-slider">
                        <div class="swiper-wrapper">
                            <?php foreach($featured_products as $prod): ?>
                            <div class="swiper-slide h-auto">
                                <?php include 'product_card_template.php'; ?> 
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <div class="swiper-button-next" id="btn-next-featured"></div>
                    <div class="swiper-button-prev" id="btn-prev-featured"></div>
                </div>

                <!-- Giữ nguyên toàn bộ phần còn lại (tab điện thoại/tablet, thương hiệu, tin tức, toast, script...) -->
                <!-- ... (copy nguyên như code bạn gửi, không thay đổi gì) ... -->

                <div class="mb-5">
                    <div class="row g-3">
                        <div class="col-lg-3 d-none d-lg-block">
                            <div class="d-flex flex-column gap-3 h-100">
                                <a href="products.php" class="flex-fill overflow-hidden rounded shadow-sm position-relative">
                                    <img src="banner-side.jpg" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0 hover-zoom">
                                </a>
                                <a href="products.php" class="flex-fill overflow-hidden rounded shadow-sm position-relative">
                                    <img src="banner-side.jpg" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0 hover-zoom">
                                </a>
                            </div>
                        </div>

                        <div class="col-12 col-lg-9">
                            <div class="bg-white rounded shadow-sm border p-3">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3 overflow-x-auto">
                                    <div class="d-flex gap-2 gap-md-3 flex-nowrap">
                                        <span class="cat-tab-btn active" onclick="switchTab('phone')" id="tab-phone">ĐIỆN THOẠI</span>
                                        <span class="cat-tab-btn text-muted" onclick="switchTab('tablet')" id="tab-tablet">TABLET</span>
                                    </div>
                                </div>

                                <div id="block-phone" class="tab-content-block">
                                    <div class="position-relative">
                                        <div class="swiper phone-slider">
                                            <div class="swiper-wrapper">
                                                <?php foreach($phone_products as $prod): ?>
                                                <div class="swiper-slide h-auto">
                                                    <?php include 'product_card_template.php'; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="swiper-button-next" id="btn-next-phone"></div>
                                        <div class="swiper-button-prev" id="btn-prev-phone"></div>
                                    </div>
                                </div>

                                <div id="block-tablet" class="tab-content-block d-none">
                                    <div class="position-relative">
                                        <div class="swiper tablet-slider">
                                            <div class="swiper-wrapper">
                                                <?php foreach($tablet_products as $prod): ?>
                                                <div class="swiper-slide h-auto">
                                                    <?php include 'product_card_template.php'; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="swiper-button-next" id="btn-next-tablet"></div>
                                        <div class="swiper-button-prev" id="btn-prev-tablet"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <h4 class="fw-bold mb-4 border-start border-4 border-primary ps-3">CHUYÊN TRANG THƯƠNG HIỆU</h4>
                    <div class="row row-cols-2 row-cols-md-4 g-3">
                        <?php foreach($brands as $brand): ?>
                        <div class="col">
                            <a href="<?= $brand['link'] ?>">
                                <img src="<?= $brand['img'] ?>" class="img-fluid rounded shadow w-100" alt="Brand">
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                
                 <!-- anh hieu -->
                <!------- Thêm cho bài viết -------->
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-4 border-start border-4 border-primary ps-3">CÁC BÀI VIẾT</h4>
                        <a href="news.php" class="btn btn-outline-dark btn-sm rounded-pill">NHIỀU BÀI VIẾT HƠN <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div id="home-news-container" class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
                        <!-- Bài viết sẽ được load động bằng JS -->
                    </div>
                </div>
                <script src="news.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        loadHomeNews();
                    });
                </script>
                <!------- kết thúc thêm cho bài viết -------->

                <!-- anh hieu -->
<!-- 

                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold border-start border-4 border-info ps-3 m-0">TIN TỨC</h4>
                        <a href="news.php" class="btn btn-outline-dark btn-sm rounded-pill">Xem tất cả <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                        <?php foreach($news_list as $index => $news): ?>
                        <div class="col<?= ($index >= 5) ? ' news-hidden' : '' ?>">
                            <a href="<?= $news['link'] ?>" class="text-decoration-none h-100 d-block">
                                <div class="card h-100 border-0 shadow-sm hover-shadow transition-card">
                                    <div class="ratio ratio-16x9 overflow-hidden rounded-top">
                                        <img src="<?= $news['image'] ?>" class="object-fit-cover w-100 h-100" alt="<?= htmlspecialchars($news['title']) ?>">
                                    </div>
                                    <div class="card-body p-2 bg-white rounded-bottom d-flex flex-column">
                                        <h6 class="card-title small text-dark mb-0 text-clamp-3 fw-semibold">
                                            <?= htmlspecialchars($news['title']) ?>
                                        </h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($news_list) > 5): ?>
                    <div class="text-center mt-4">
                        <button id="btnLoadMoreNews" class="btn btn-outline-primary rounded-pill px-4">Xem thêm</button>
                    </div>
                    <?php endif; ?>
                </div> -->

            </div>
        </div>

        <div class="col-xxl-2 d-none d-xxl-block text-start">
            <a href="products.php">
                <img src="banner-side.jpg" class="side-banner-img shadow img-fluid" style="max-width: 200px; margin-right: auto;">
            </a>
        </div>
    </div>
</div>

<!-- Toast và Footer + Script giữ nguyên như cũ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="liveToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        Đã thêm vào giỏ hàng!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php include '../app/Views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    const commonBreakpoints = {
        0: { slidesPerView: 2, spaceBetween: 10, grid: { rows: 2, fill: 'row' } },
        576: { slidesPerView: 3, spaceBetween: 10, grid: { rows: 2, fill: 'row' } },
        992: { slidesPerView: 3, spaceBetween: 15, grid: { rows: 2, fill: 'row' } },
        1200: { slidesPerView: 4, spaceBetween: 20, grid: { rows: 2, fill: 'row' } }
    };

    new Swiper(".product-slider", { slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10, navigation: { nextEl: "#btn-next-featured", prevEl: "#btn-prev-featured" }, pagination: { el: ".swiper-pagination", clickable: true }, breakpoints: commonBreakpoints });
    new Swiper(".phone-slider", { slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10, observer: true, observeParents: true, navigation: { nextEl: "#btn-next-phone", prevEl: "#btn-prev-phone" }, breakpoints: commonBreakpoints });
    new Swiper(".tablet-slider", { slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10, observer: true, observeParents: true, navigation: { nextEl: "#btn-next-tablet", prevEl: "#btn-prev-tablet" }, breakpoints: commonBreakpoints });

    function switchTab(type) {
        document.querySelectorAll('.cat-tab-btn').forEach(btn => { btn.classList.remove('active'); btn.classList.add('text-muted'); });
        const activeBtn = document.getElementById('tab-' + type);
        activeBtn.classList.add('active'); activeBtn.classList.remove('text-muted');
        document.querySelectorAll('.tab-content-block').forEach(block => block.classList.add('d-none'));
        document.getElementById('block-' + type).classList.remove('d-none');
    }

    document.getElementById('btnLoadMoreNews')?.addEventListener('click', function() {
        const hiddenNews = document.querySelectorAll('.news-hidden');
        let count = 0;
        hiddenNews.forEach(news => { if (count < 10) { news.classList.remove('news-hidden'); count++; } });
        if (document.querySelectorAll('.news-hidden').length === 0) this.style.display = 'none';
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-add-to-cart');
        if (!btn) return;
        e.preventDefault();
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('product_id', btn.dataset.id);
        formData.append('product_name', btn.dataset.name);
        formData.append('product_price', btn.dataset.price);
        formData.append('product_image', btn.dataset.image);
        formData.append('quantity', 1);

        fetch('cart_add.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const badge = document.getElementById('cart-badge');
                if (badge) badge.innerText = data.total_items;
                new bootstrap.Toast(document.getElementById('liveToast')).show();
            } else alert('Lỗi: ' + data.message);
        })
        .catch(err => console.error(err))
        .finally(() => { btn.innerHTML = originalIcon; btn.disabled = false; });
    });
</script>