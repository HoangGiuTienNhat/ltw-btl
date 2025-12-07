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
<div class="container py-4 flex-fill" style="background-color: #f5f5f5;">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">Trang chủ</a></li>
            <li class="breadcrumb-item active">Bài viết</li>
        </ol>
    </nav>
    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center bg-white p-3 rounded shadow-sm border">
                <h1 class="fw-bold text-primary text-center">Bài viết</h1>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
                <div class="col-lg-6">
                    <span class="alert alert-info py-1 px-2" id="total-news"></span>
                </div>
                <div class="col-lg-6">
                    <form id="search-form" class="m-2" onsubmit="handleSearch(event)" style="width: 100%;">
                        <div class="input-group shadow-sm">
                            <input type="text" id="search-keyword" class="form-control border-1 border-info" placeholder="Nhập từ khóa tiêu đề, nội dung..." aria-label="Từ khóa tìm kiếm">
                            <button class="btn btn-info" type="button" onclick="handleSearch(event)">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <hr class="border-0 border-top border-secondary opacity-50">
    <div id="news-container" class="row row-cols-1 row-cols-md-3 g-4">

    </div>
    <nav aria-label="Page navigation" class="mt-5">
        <ul id="pagination" class="pagination justify-content-center"></ul>
    </nav>
</div>

<script src="news.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadNewsList(); 
    });
</script>
<?php include '../app/Views/layouts/footer.php'; ?>