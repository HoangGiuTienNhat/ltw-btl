<?php
// Orders API - read-only list + update status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // If id provided, return single order with items and user info
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare('SELECT o.*, u.full_name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = :id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $order = $stmt->fetch();
        if (!$order) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit; }
        $stmt2 = $conn->prepare('SELECT * FROM order_items WHERE order_id = :oid');
        $stmt2->execute([':oid'=>$id]);
        $order['items'] = $stmt2->fetchAll();
        echo json_encode($order, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // fetch orders with items (list) - with server-side pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM orders');
    $countStmt->execute();
    $countRow = $countStmt->fetch();
    $total = intval($countRow['total']);
    $totalPages = ceil($total / $limit);
    
    // Fetch paginated orders
    $stmt = $conn->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$o) {
        $stmt2 = $conn->prepare('SELECT * FROM order_items WHERE order_id = :oid');
        $stmt2->execute([':oid'=>$o['id']]);
        $o['items'] = $stmt2->fetchAll();
    }
    
    // Return paginated response with metadata
    echo json_encode([
        'data' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'offset' => $offset
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // update status via JSON body {id:..., status:...}
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['id']) || empty($input['status'])) { http_response_code(400); echo json_encode(['error'=>'Missing id or status']); exit; }
    $stmt = $conn->prepare('UPDATE orders SET status = :status WHERE id = :id');
    $stmt->execute([':status'=>$input['status'], ':id'=>intval($input['id'])]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
