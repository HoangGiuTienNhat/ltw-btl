<?php
// Đường dẫn file: .../project-web/public/api/orders.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

// --- 1. GET REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // CASE A: Lấy chi tiết 1 đơn hàng (Giữ nguyên logic cũ cho viewOrder)
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare('SELECT o.*, u.full_name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = :id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit; }
        
        // Lấy items của đơn hàng này
        $stmt2 = $conn->prepare('SELECT * FROM order_items WHERE order_id = :oid');
        $stmt2->execute([':oid'=>$id]);
        $order['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($order, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CASE B: Lấy danh sách đơn hàng (Bổ sung PHÂN TRANG)
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // 10 orders mỗi trang
        $offset = ($page - 1) * $limit;

        // 1. Đếm tổng số đơn hàng
        $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM orders");
        $stmtCount->execute();
        $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);

        // 2. Lấy danh sách đơn hàng cho trang hiện tại
        $stmt = $conn->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Lấy items cho từng đơn hàng (Chỉ loop qua 10 đơn hàng này thôi -> Tối ưu hơn code cũ)
        foreach ($orders as &$o) {
            $stmt2 = $conn->prepare('SELECT product_name, quantity, price FROM order_items WHERE order_id = :oid');
            $stmt2->execute([':oid'=>$o['id']]);
            $o['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        // Trả về cấu trúc JSON chuẩn có pagination
        echo json_encode([
            'status' => 'success',
            'data' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- 2. POST REQUESTS (UPDATE STATUS) ---
// (Giữ nguyên logic cũ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id']) || empty($input['status'])) { http_response_code(400); echo json_encode(['error'=>'Missing id or status']); exit; }
    
    $stmt = $conn->prepare('UPDATE orders SET status = :status WHERE id = :id');
    $stmt->execute([':status'=>$input['status'], ':id'=>intval($input['id'])]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
?>