<?php
session_start();
require_once '../config/database.php';
require_once '../app/Controllers/AuthController.php';
header('Content-Type: application/json; charset=utf-8');

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
if ($news_id === false) {
    echo json_encode(['status' => 'error', 'message' => 'ID không hợp lệ']);
    exit();
}

$content = trim($_POST['content'] ?? '');
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

$author = '';
if (isset($_SESSION['user']) && !empty($_SESSION['user']['full_name'])) {
    $author = $_SESSION['user']['full_name'];
} else if (isset($currentUser['full_name']) && $currentUser['full_name'] != '') {
    $author = $currentUser['full_name'];
}

if ($news_id && $content && $author !== '') {
    $stmt = $conn->prepare("INSERT INTO news_comments (news_id, author, content, status, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$news_id, $author, $content]);
    echo json_encode(['status' => 'success']);
}
?>