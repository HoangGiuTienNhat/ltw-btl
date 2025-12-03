<?php
// File: submit_review.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json'); // Bắt buộc

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request");
    }

    // Lấy dữ liệu
    $product_id = $_POST['product_id'] ?? 0;
    $user_name = $_POST['user_name'] ?? 'Khách';
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';

    // Validate cơ bản
    if (empty($comment)) throw new Exception("Vui lòng nhập nội dung đánh giá");

    // Insert vào DB
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_name, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$product_id, $user_name, $rating, $comment]);

    // Trả về kết quả JSON thành công kèm dữ liệu vừa nhập để JS hiển thị
    echo json_encode([
        'status' => 'success',
        'message' => 'Cảm ơn bạn đã đánh giá sản phẩm',
        'review' => [
            'user_name' => $user_name,
            'rating' => $rating,
            'comment' => $comment
        ]
    ]);

} catch (Exception $e) {
    // Trả về lỗi dạng JSON
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>