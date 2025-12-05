<?php
session_start();

// =================================================================
// 1. KẾT NỐI DATABASE (TRỰC TIẾP)
// =================================================================
$host = 'localhost';
$db   = 'techstore_db';
$user = 'root';
$pass = '123123';          
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$conn = null;

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    // Nếu lỗi kết nối vẫn chạy tiếp với dữ liệu mặc định
}

// 2. KHỞI TẠO DỮ LIỆU & XỬ LÝ
$cfg = [
    'site_title' => 'TechStore',
    'address'    => 'TP. Hồ Chí Minh',
    'email'      => 'contact@techstore.com',
    'hotline'    => '1900 1000',
    'google_map' => '' 
];
$faqs = [];

if ($conn) {
    try {
        // A. LẤY CẤU HÌNH
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['setting_key'];
            $val = $row['setting_value'];
            $decoded = json_decode($val, true);
            $cfg[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $val;
        }

        // B. XỬ LÝ GỬI CÂU HỎI HỎI
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_ask_question'])) {
            $user_question = trim($_POST['user_question']);
            if (!empty($user_question)) {
                $sql = "INSERT INTO faq (question, answer, status) VALUES (?, '', 0)";
                $stmt_insert = $conn->prepare($sql);
                $stmt_insert->execute([$user_question]);
                
                $_SESSION['success_faq'] = "Câu hỏi của bạn đã được gửi. Admin sẽ trả lời sớm nhất!";
                header("Location: contact.php");
                exit;
            }
        }

        // C. LẤY DANH SÁCH FAQ
        $stmt_faq = $conn->prepare("SELECT * FROM faq ORDER BY id DESC");
        $stmt_faq->execute();
        $faqs = $stmt_faq->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) { }
}

// Sử dụng chung header & footer
include '../app/Views/layouts/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỏi đáp & Liên hệ - <?php echo htmlspecialchars(is_string($cfg['site_title']) ? $cfg['site_title'] : 'TechStore'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8f9fa; /* giữ lại để fallback nếu có lỗi */
        }

        /* Nền trắng bao toàn trang */
        .page-wrapper {
            min-height: 100vh;
            background: #ffffff;
        }

        .info-card { 
            background: white; 
            border-radius: 16px; 
            padding: 30px 20px; 
            text-align: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.04); 
            transition: transform 0.2s;
        }
        .info-card:hover { transform: translateY(-5px); }

        .icon-box { 
            width: 70px; 
            height: 70px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 28px; 
            margin-bottom: 15px; 
        }

        /* Hỏi đáp */
        .qa-section { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 5px 25px rgba(0,0,0,0.06); 
        }

        .ask-box { 
            position: relative; 
            max-width: 720px; 
            margin: 0 auto 50px; 
        }
        .ask-box input {
            padding-left: 25px; 
            padding-right: 130px; 
            border-radius: 50px; 
            height: 60px;
            background: #f8f9fa; 
            border: 2px solid #e9ecef; 
            font-size: 16px;
        }
        .ask-box button { 
            position: absolute; 
            right: 8px; 
            top: 8px; 
            bottom: 8px; 
            border-radius: 40px; 
            padding: 0 30px; 
            font-weight: 600; 
        }

        .map-wrapper { 
            background: #fff; 
            padding: 12px; 
            border-radius: 20px; 
            box-shadow: 0 5px 25px rgba(0,0,0.08); 
            height: 520px; 
            overflow: hidden; 
        }
        .map-wrapper iframe { 
            width: 100% !important; 
            height: 100% !important; 
            border: 0; 
        }

        .accordion-item { 
            border: none; 
            margin-bottom: 12px; 
            border-radius: 12px !important; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        }

        .accordion-button:not(.collapsed) { 
            background-color: #eef2ff; 
            color: #0d6efd; 
            font-weight: 600; 
        }
        .accordion-button:focus { box-shadow: none; }

        .answered-q .accordion-button { background-color: #f8fff8; color: #28a745; }
        .pending-q .accordion-button { background-color: #fff8f8; color: #dc3545; }

        .faq-question { 
            flex-grow: 1; 
            word-break: break-word; 
            white-space: normal; 
        }

        /* Responsive */
        @media (max-width: 768px) {
            .qa-section { padding: 25px 20px; }
            .ask-box input { height: 55px; padding-right: 110px; }
            .ask-box button { padding: 0 20px; font-size: 14px; }
            .map-wrapper { height: 350px; }
            .info-card { padding: 20px; }
            .icon-box { width: 60px; height: 60px; font-size: 24px; }
            .accordion-button { flex-direction: column; align-items: flex-start; padding: 15px; }
            .status-badge { margin-top: 8px; }
        }
    </style>
</head>
<body>

    <!-- NỀN TRẮNG BAO TOÀN TRANG -->
    <div class="page-wrapper">

        <div class="container py-4 py-md-5">

            <div class="text-center mb-5">
                <h2 class="fw-bold display-5 display-md-4">Trung tâm hỗ trợ</h2>
                <p class="text-muted lead">Chúng tôi ở đây để giải đáp mọi thắc mắc của bạn</p>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="row g-4 mb-5 justify-content-center">
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <h5 class="fw-bold">Hotline</h5>
                        <a href="tel:<?php echo htmlspecialchars($cfg['hotline'] ?? ''); ?>" class="fw-bold fs-3 text-dark text-decoration-none">
                            <?php echo htmlspecialchars($cfg['hotline'] ?? '1900 1000'); ?>
                        </a>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <h5 class="fw-bold">Email</h5>
                        <a href="mailto:<?php echo htmlspecialchars($cfg['email'] ?? ''); ?>" class="text-dark text-decoration-none">
                            <?php echo htmlspecialchars($cfg['email'] ?? 'support@techstore.vn'); ?>
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h5 class="fw-bold">Địa chỉ</h5>
                        <p class="mb-0 text-dark fw-500">
                            <?php echo nl2br(htmlspecialchars($cfg['address'] ?? '939A Kha Vạn Căn, P. Linh Trung, TP. Thủ Đức, TP.HCM')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bản đồ -->
            <div class="row mb-5">
                <div class="col-12">
                    <h4 class="fw-bold mb-4 text-danger"><i class="bi bi-map-fill me-2"></i>Bản đồ đường đi</h4>
                    <div class="map-wrapper">
                        <?php 
                        $raw_map = $cfg['google_map'] ?? '';
                        $map_data = html_entity_decode($raw_map, ENT_QUOTES, 'UTF-8');

                        if (!empty($map_data) && stripos($map_data, '<iframe') !== false) {
                            echo $map_data;
                        } else {
                            $addr = urlencode($cfg['address'] ?? '939A Kha Vạn Căn Thủ Đức');
                            echo '<iframe src="https://maps.google.com/maps?q=' . $addr . '&t=&z=16&ie=UTF8&iwloc=&output=embed" allowfullscreen="" loading="lazy"></iframe>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Hỏi đáp -->
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xxl-9">
                    <div class="qa-section">

                        <h4 class="text-center fw-bold mb-4">Gửi câu hỏi cho chúng tôi</h4>
                        <p class="text-center text-muted mb-5">Admin sẽ trả lời sớm và hiển thị công khai để mọi người cùng tham khảo.</p>

                        <?php if (isset($_SESSION['success_faq'])): ?>
                            <div class="alert alert-success alert-dismissible fade show text-center mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $_SESSION['success_faq']; unset($_SESSION['success_faq']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="ask-box">
                            <input type="text" name="user_question" class="form-control" placeholder="VD: Bảo hành sản phẩm kéo dài bao lâu?" required>
                            <button type="submit" name="btn_ask_question" class="btn btn-primary btn-lg">
                                <i class="bi bi-send-fill me-2"></i>Gửi câu hỏi
                            </button>
                        </form>

                        <h5 class="text-center fw-bold mt-5 mb-4">Câu hỏi từ khách hàng</h5>

                        <div class="accordion" id="faqAccordion">
                            <?php if (!empty($faqs)): ?>
                                <?php foreach ($faqs as $i => $faq): ?>
                                    <?php $answered = ($faq['status'] == 1 && trim($faq['answer']) !== ''); ?>
                                    <div class="accordion-item <?php echo $answered ? 'answered-q' : 'pending-q'; ?>">
                                        <h2 class="accordion-header" id="heading<?php echo $faq['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse<?php echo $faq['id']; ?>">
                                                <i class="bi bi-chat-dots-fill me-2 text-primary"></i>
                                                <span class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></span>
                                                <span class="badge rounded-pill <?php echo $answered ? 'bg-success' : 'bg-warning text-dark'; ?> ms-3 status-badge">
                                                    <?php echo $answered ? 'Đã trả lời' : 'Chờ trả lời'; ?>
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $faq['id']; ?>" class="accordion-collapse collapse" 
                                             data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                <?php if ($answered): ?>
                                                    <strong><i class="bi bi-person-fill me-1 text-success"></i> Admin:</strong><br>
                                                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>
                                                <?php else: ?>
                                                    <em class="text-muted"><i class="bi bi-clock-fill me-1"></i> Câu hỏi đang chờ Admin trả lời...</em>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-chat-square-text fs-1 opacity-25 d-block mb-3"></i>
                                    Chưa có câu hỏi nào.
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div> <!-- /.container -->

    </div> <!-- /.page-wrapper -->

    <?php include '../app/Views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>