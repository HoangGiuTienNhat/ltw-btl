<?php
session_start();

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

include '../app/Views/layouts/header.php';
?>
<div class="container py-4 flex-fill" id="news-content" style="background-color: #f5f5f5;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="news.php" class="text-decoration-none text-dark">Tin tức</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
                </ol>
            </nav>

            <article class="d-flex flex-column bg-white p-4 p-md-5 rounded shadow-sm border mb-5">
                <img id="viewImage" src="" class="img-fluid object-fit-contain" alt="ảnh bìa bài viết" loading="lazy" style="display: none;">
                <h2 class="fw-bold my-3 display-6" id="news-title">Loading...</h2>
                <div class="text-muted mb-4 border-bottom pb-3">
                    <i class="bi bi-calendar3 me-2"></i> <span id="news-date"></span>
                </div>
                <div id="news-body" class="fs-6 fs-md-5 fs-lg-5 lh-lg text-break">
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

<script src="news_detail.js"></script>
<?php include '../app/Views/layouts/footer.php'; ?>