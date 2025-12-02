<?php
// Kiểm tra dữ liệu đầu vào để tránh lỗi
if (!isset($prod)) return;

// 1. Tính toán % giảm giá
$discount_percent = 0;
if (!empty($prod['old_price']) && $prod['old_price'] > $prod['price']) {
    $discount_percent = round((($prod['old_price'] - $prod['price']) / $prod['old_price']) * 100);
}

// 2. Xử lý hiển thị sao từ Database (Cột 'rating')
$rating = isset($prod['rating']) ? floatval($prod['rating']) : 0;
$full_stars = floor($rating); // Số ngôi sao đầy
$has_half_star = ($rating - $full_stars) >= 0.5; // Có nửa sao không?
$empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0); // Số ngôi sao rỗng
?>

<div class="product-card h-100 position-relative rounded-3 bg-white d-flex flex-column">
    
    <?php if ($discount_percent > 0): ?>
    <span class="position-absolute top-0 start-0 mt-2 ms-0 badge bg-danger text-white rounded-end-pill shadow-sm" style="z-index: 5; font-size: 0.75rem; padding: 6px 10px;">
        Giảm <?= $discount_percent ?>%
    </span>
    <?php endif; ?>

    <span class="position-absolute top-0 end-0 mt-2 me-2 badge bg-warning text-dark border border-warning rounded-pill" style="z-index: 5; font-size: 0.65rem;">
        Trả góp 0%
    </span>

    <a href="detail.php?id=<?= $prod['id'] ?>" class="d-block text-decoration-none">
        <div class="product-img-box border-bottom position-relative">
            <img src="<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" loading="lazy">
        </div>
    </a>

    <div class="p-3 d-flex flex-column flex-grow-1">
        
        <div class="mb-2">
            <a href="detail.php?id=<?= $prod['id'] ?>" class="text-decoration-none text-dark" title="<?= htmlspecialchars($prod['name']) ?>">
                <h6 class="fw-semibold mb-0" style="
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    height: 40px; 
                    line-height: 20px;
                    font-size: 0.95rem;
                ">
                    <?= htmlspecialchars($prod['name']) ?>
                </h6>
            </a>
        </div>

        <div class="d-flex align-items-center mb-2" style="font-size: 0.75rem;">
            <div class="text-warning me-1">
                <?php 
                // Vẽ sao đầy
                for ($i = 0; $i < $full_stars; $i++) { echo '<i class="bi bi-star-fill"></i> '; }
                // Vẽ nửa sao
                if ($has_half_star) { echo '<i class="bi bi-star-half"></i> '; }
                // Vẽ sao rỗng
                for ($i = 0; $i < $empty_stars; $i++) { echo '<i class="bi bi-star"></i> '; }
                ?>
            </div>
            <span class="text-muted">(<?= $rating ?>/5)</span>
        </div>

        <div class="mt-auto">
            <div class="d-flex align-items-baseline gap-2 flex-wrap">
                <span class="text-danger fw-bold fs-5">
                    <?= number_format($prod['price'], 0, ',', '.') ?>₫
                </span>
                <?php if ($discount_percent > 0): ?>
                <span class="text-decoration-line-through text-muted small">
                    <?= number_format($prod['old_price'], 0, ',', '.') ?>₫
                </span>
                <?php endif; ?>
            </div>
            
            <button class="btn btn-outline-primary w-100 rounded-pill mt-3 btn-add-to-cart d-flex align-items-center justify-content-center gap-2"
                data-id="<?= $prod['id'] ?>"
                data-name="<?= htmlspecialchars($prod['name']) ?>"
                data-price="<?= $prod['price'] ?>"
                data-image="<?= htmlspecialchars($prod['image']) ?>">
                <i class="bi bi-cart-plus"></i> <span class="fw-semibold">Thêm vào giỏ</span>
            </button>
        </div>
    </div>
</div>