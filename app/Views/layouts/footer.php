<!-- </main>
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
</body>
</html>
 -->

<?php
// 1. TỰ ĐỘNG TÌM VÀ KẾT NỐI DATABASE
$db_files = [
    __DIR__ . '/database.php',
    __DIR__ . '/../../../../config/database.php', // Thử tìm ngược ra ngoài
    $_SERVER['DOCUMENT_ROOT'] . '/LTW-xampp/project-web/config/database.php',
    'database.php'
];

foreach ($db_files as $file) {
    if (file_exists($file)) {
        include_once $file;
        break;
    }
}

// 2. CẤU HÌNH ĐƯỜNG DẪN ẢNH TỪ ADMIN (QUAN TRỌNG)
// Dựa trên đường dẫn bạn cung cấp: C:\xampp\htdocs\BTL\ltw-admin-main\ltw-admin-main\dashboard\task2\uploads
// XAMPP map thư mục htdocs thành http://localhost/
// Vì vậy đường dẫn web sẽ là:
$admin_url = '/LTW-xampp/tabler-1.4.0-hienthuc/dashboard/task2/';

// 3. KHỞI TẠO DỮ LIỆU MẶC ĐỊNH
$cfg = [
    'site_title' => 'TechStore',
    'slogan'     => 'Uy tín - Chất lượng',
    'site_logo'  => '',
    'address'    => '123 Đường ABC, TP.HCM',
    'email'      => 'contact@techstore.com'
];
$phones = []; $links = []; $socials = []; $partners = [];

// 4. LẤY DỮ LIỆU TỪ DATABASE
global $conn;

if (isset($conn) && $conn instanceof PDO) {
    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cfg[$row['setting_key']] = $row['setting_value'];
        }

        // Giải mã JSON
        $phones   = json_decode($cfg['footer_col1'] ?? '{}', true);
        $links    = json_decode($cfg['footer_col2'] ?? '[]', true);
        $socials  = json_decode($cfg['footer_socials'] ?? '{}', true);
        $partners = json_decode($cfg['partner_logos'] ?? '[]', true);

    } catch (Exception $e) { }
}
?>

</main>

<footer class="bg-dark text-white pt-5 pb-3 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 col-sm-12">
                <h5 class="fw-bold text-uppercase text-warning mb-3 d-flex align-items-center">
                    <?php if (!empty($cfg['site_logo'])): 
                        $logoPath = $admin_url . $cfg['site_logo'];
                    ?>
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="height: 40px; margin-right: 10px; background:white; padding:2px; border-radius:4px;">
                    <?php else: ?>
                        <i class="bi bi-box-seam me-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($cfg['site_title']); ?>
                </h5>
                <p class="text-white-50 small mb-4"><?php echo htmlspecialchars($cfg['slogan']); ?></p>
                <ul class="list-unstyled text-white-50 small">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-warning"></i> <?php echo htmlspecialchars($cfg['address']); ?></li>
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-warning"></i> <?php echo htmlspecialchars($cfg['email']); ?></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6 col-sm-12">
                <h6 class="fw-bold text-light mb-3">Về chúng tôi</h6>
                <ul class="list-unstyled small">
                    <?php if (!empty($links) && is_array($links)): ?>
                        <?php foreach ($links as $link): ?>
                            <li class="mb-2"><a href="<?php echo htmlspecialchars($link['url']); ?>" class="text-white-50 text-decoration-none hover-link"><?php echo htmlspecialchars($link['text']); ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
                <h6 class="fw-bold text-light mb-3">Tổng đài hỗ trợ</h6>
                <ul class="list-unstyled small">
                    <li class="mb-3">
                        <span class="d-block text-white-50">Gọi mua hàng</span>
                        <a href="tel:<?php echo isset($phones['call_buy']) ? str_replace([' ','.'], '', $phones['call_buy']) : ''; ?>" class="fw-bold text-warning text-decoration-none fs-5">
                            <?php echo htmlspecialchars($phones['call_buy'] ?? '---'); ?>
                        </a>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-white-50">Khiếu nại</span>
                        <span class="fw-bold text-light"><?php echo htmlspecialchars($phones['call_complain'] ?? '---'); ?></span>
                    </li>
                    <li class="mb-3">
                        <span class="d-block text-white-50">Bảo hành</span>
                        <span class="fw-bold text-light"><?php echo htmlspecialchars($phones['call_warranty'] ?? '---'); ?></span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-12">
                <?php if (!empty($partners) && is_array($partners) && count($partners) > 0): ?>
                    <h6 class="fw-bold text-light mb-3">Đối tác chính thức</h6>
                    <div class="d-flex justify-content-start flex-wrap align-items-center gap-3 opacity-75 mb-4">
                        <?php foreach ($partners as $partner): ?>
                            <?php 
                                $dbPath = is_string($partner) ? $partner : ($partner['image'] ?? '');
                                if (!empty($dbPath)) {
                                    $fullPath = $admin_url . $dbPath;
                            ?>
                                <img src="<?php echo htmlspecialchars($fullPath); ?>" 
                                     alt="Partner" 
                                     style="height: 40px; width: auto; object-fit: contain; max-width: 120px; filter: grayscale(0%); transition: all 0.3s;">
                            <?php } ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h6 class="fw-bold text-light mb-3">Kết nối với chúng tôi</h6>
                <div class="d-flex gap-2 mb-4">
                    <?php if (!empty($socials['facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($socials['facebook']); ?>" class="btn btn-outline-light btn-sm rounded-circle social-btn"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($socials['youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($socials['youtube']); ?>" class="btn btn-outline-light btn-sm rounded-circle social-btn"><i class="bi bi-youtube"></i></a>
                    <?php endif; ?>
                </div>
                <a href="https://zalo.me/<?php echo isset($phones['call_buy']) ? str_replace([' ','.'], '', $phones['call_buy']) : ''; ?>" target="_blank" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                    <i class="bi bi-chat-dots me-2"></i> <?php echo htmlspecialchars($socials['zalo'] ?? 'Zalo OA'); ?>
                </a>
            </div>
        </div>
        <div class="border-top border-secondary mt-5 pt-3 text-center">
            <small class="text-white-50">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($cfg['site_title']); ?>. All rights reserved.</small>
        </div>
    </div>
</footer>

<style>
    .hover-link:hover { color: #ffc107 !important; padding-left: 5px; transition: all 0.3s ease; }
    .social-btn { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; }
    .opacity-75 { opacity: 0.75; }
    .opacity-75:hover { opacity: 1; transition: opacity 0.3s; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>