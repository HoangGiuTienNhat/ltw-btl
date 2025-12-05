<?php
session_start();
require_once '../config/database.php';
require_once '../app/Controllers/AuthController.php';
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
            $page  = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
            $keyword = trim($_GET['keyword'] ?? '');

            if ($limit === false || $limit < 1) {
                $limit = 6;
            }
            if ($page === false || $page < 1) {
                $page = 1;
            }
            
            $offset = ($page - 1) * $limit;
            
            $sqlWhere = "";
            $params = [];
            
            if (!empty($keyword)) {
                $sqlWhere = "WHERE title LIKE ? OR content LIKE ?";
                $keyword = stripslashes($keyword);
                $keyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
                $searchTerm = "%" . $keyword . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Truy vấn 1: Đếm tổng số bài viết (để tính phân trang)
            $sqlCount = "SELECT COUNT(*) as total FROM news $sqlWhere";
            $stmtCount = $conn->prepare($sqlCount);
            foreach ($params as $key => $val) {
                $stmtCount->bindValue($key + 1, $val);
            }
            $stmtCount->execute();
            $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $limit);

            $sqlData = "SELECT id, title, image, created_at 
                        FROM news 
                        $sqlWhere 
                        ORDER BY created_at DESC 
                        LIMIT $limit OFFSET $offset";
            
            $stmt = $conn->prepare($sqlData);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key + 1, $val);
            }

            $stmt->execute();
            
            echo json_encode([
                'status'     => 'success', 
                'data'       => $stmt->fetchAll(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords
                ]
            ]);
            break;

        case 'detail':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id === false) {
                echo json_encode(['status' => 'error', 'message' => 'ID không hợp lệ']);
                exit();
            }
            $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $news = $stmt->fetch();
            
            if ($news) {
                echo json_encode(['status' => 'success', 'data' => $news]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Bài viết không tồn tại']);
            }
            break;

        case 'get_comments':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id === false) {
                echo json_encode(['status' => 'error', 'message' => 'ID bài viết không hợp lệ']);
                exit();
            }
            $page  = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
            if ($page === false || $page < 1) {
                $page = 1;
            }
            $limit = 6;
            $offset = ($page - 1) * $limit;

            // Đếm tổng số comment đã duyệt của bài viết này
            $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM news_comments WHERE news_id = ? AND status = 1");
            $stmtCount->execute([$id]);
            $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $limit);

            // Lấy dữ liệu comment theo trang
            $stmt = $conn->prepare("SELECT author, content, created_at FROM news_comments WHERE news_id = ? AND status = 1 ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute([$id]);
            
            echo json_encode([
                'status' => 'success', 
                'data'   => $stmt->fetchAll(),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords
                ]
            ]);
            break;

        case 'post_comment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Yêu cầu phương thức POST']);
                exit();
            }

            $currentUser = AuthController::getCurrentUser();
            if (!$currentUser) {
                echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để bình luận']);
                exit;
            }

            $news_id = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);
            $author = '';
            $content = trim($_POST['content'] ?? '');
            if (isset($_SESSION['user']) && !empty($_SESSION['user']['full_name'])) {
                $author = $_SESSION['user']['full_name'];
            } else if (isset($currentUser['full_name']) && $currentUser['full_name'] != '') {
                $author = $currentUser['full_name'];
            }

            // Validate
            if ($news_id === false) {
                echo json_encode(['status' => 'error', 'message' => 'ID không hợp lệ']);
                exit();
            }
            if ($content === '') { 
                echo json_encode(['status' => 'error', 'message' => 'Nội dung bình luận không được để trống']);
                exit();
            }
            if (strlen($content) > 2000) {
                echo json_encode(['status' => 'error', 'message' => 'Nội dung quá dài']);
                exit();
            }
            $content = stripslashes($content);
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            if ($news_id && $content && $author !== '') {
                $stmt = $conn->prepare("INSERT INTO news_comments (news_id, author, content, status, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt->execute([$news_id, $author, $content]);
                echo json_encode(['status' => 'success']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action invalid']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>