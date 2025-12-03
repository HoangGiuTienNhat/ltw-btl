<?php
session_start();
require_once '../config/database.php';

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
    // 1. Lấy sản phẩm nổi bật
    $stmt = $conn->prepare("SELECT * FROM products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 16");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
    
    // 2. Điện thoại (Cat ID = 2)
    $stmtPhone = $conn->prepare("SELECT * FROM products WHERE category_id = 2 ORDER BY created_at DESC LIMIT 16");
    $stmtPhone->execute();
    $phone_products = $stmtPhone->fetchAll();

    // 3. Tablet (Cat ID = 5)
    $stmtTablet = $conn->prepare("SELECT * FROM products WHERE category_id = 5 ORDER BY created_at DESC LIMIT 16");
    $stmtTablet->execute();
    $tablet_products = $stmtTablet->fetchAll();

    // 4. Danh mục
    $stmtCat = $conn->query("SELECT * FROM categories LIMIT 6");
    $categories = $stmtCat->fetchAll();

    // 5. Tin tức
    $stmtNews = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 16");
    $news_list = $stmtNews->fetchAll();

} catch(Exception $e) { 
    $featured_products = []; $phone_products = []; $tablet_products = []; $categories = []; $news_list = [];
    echo ""; 
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

                <div id="homeCarousel" class="carousel slide mb-5 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="pic/banner-main1.webp" class="d-block w-100 object-fit-cover" style="max-height: 450px; min-height: 200px;">
                        </div>
                        <div class="carousel-item">
                            <img src="pic/banner-main2.webp" class="d-block w-100 object-fit-cover" style="max-height: 450px; min-height: 200px;">
                        </div>
                        <div class="carousel-item">
                            <img src="pic/banner-main3.webp" class="d-block w-100 object-fit-cover" style="max-height: 450px; min-height: 200px;">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                </div>

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
                </div>


                

            </div> </div>

        <div class="col-xxl-2 d-none d-xxl-block text-start">
            <a href="products.php">
                <img src="banner-side.jpg" class="side-banner-img shadow img-fluid" style="max-width: 200px; margin-right: auto;">
            </a>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="liveToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle-fill me-2"></i> Đã thêm vào giỏ hàng!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php include '../app/Views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // Cấu hình chung cho Slider
    const commonBreakpoints = {
        0: { slidesPerView: 2, spaceBetween: 10, grid: { rows: 2, fill: 'row' } }, // Mobile: 2 cột, 2 hàng
        576: { slidesPerView: 3, spaceBetween: 10, grid: { rows: 2, fill: 'row' } }, // Tablet nhỏ
        992: { slidesPerView: 3, spaceBetween: 15, grid: { rows: 2, fill: 'row' } }, // Desktop nhỏ
        1200: { slidesPerView: 4, spaceBetween: 20, grid: { rows: 2, fill: 'row' } } // Desktop lớn
    };

    // 1. Slider Featured
    new Swiper(".product-slider", {
        slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10,
        navigation: { nextEl: "#btn-next-featured", prevEl: "#btn-prev-featured" },
        pagination: { el: ".swiper-pagination", clickable: true },
        breakpoints: commonBreakpoints
    });

    // 2. Slider Phone (Trong Tab)
    // Lưu ý: Slider trong Tab ẩn (display:none) cần observer để render đúng khi Tab hiện
    new Swiper(".phone-slider", {
        slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10,
        observer: true, observeParents: true,
        navigation: { nextEl: "#btn-next-phone", prevEl: "#btn-prev-phone" },
        breakpoints: commonBreakpoints
    });

    // 3. Slider Tablet
    new Swiper(".tablet-slider", {
        slidesPerView: 2, grid: { rows: 2, fill: 'row' }, spaceBetween: 10,
        observer: true, observeParents: true,
        navigation: { nextEl: "#btn-next-tablet", prevEl: "#btn-prev-tablet" },
        breakpoints: commonBreakpoints
    });

    // --- Tab Switching ---
    function switchTab(type) {
        document.querySelectorAll('.cat-tab-btn').forEach(btn => {
            btn.classList.remove('active'); btn.classList.add('text-muted');
        });
        const activeBtn = document.getElementById('tab-' + type);
        activeBtn.classList.add('active'); activeBtn.classList.remove('text-muted');

        document.querySelectorAll('.tab-content-block').forEach(block => block.classList.add('d-none'));
        document.getElementById('block-' + type).classList.remove('d-none');
    }

    // --- Load More News ---
    document.getElementById('btnLoadMoreNews')?.addEventListener('click', function() {
        const hiddenNews = document.querySelectorAll('.news-hidden');
        let count = 0;
        hiddenNews.forEach(news => {
            if (count < 10) { 
                news.classList.remove('news-hidden');
                count++;
            }
        });
        if (document.querySelectorAll('.news-hidden').length === 0) {
            this.style.display = 'none';
        }
    });

    // --- Add To Cart AJAX ---
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-add-to-cart');
        if (btn) {
            e.preventDefault();
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('product_id', btn.getAttribute('data-id'));
            formData.append('product_name', btn.getAttribute('data-name'));
            formData.append('product_price', btn.getAttribute('data-price'));
            formData.append('product_image', btn.getAttribute('data-image'));
            formData.append('quantity', 1);

            fetch('cart_add.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const badge = document.getElementById('cart-badge');
                    if (badge) badge.innerText = data.total_items;
                    new bootstrap.Toast(document.getElementById('liveToast')).show();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                btn.innerHTML = originalIcon;
                btn.disabled = false;
            });
        }
    });
</script>