<?php
// Đường dẫn: .../project-web/public/api/products.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Gọi file cấu hình database
require_once __DIR__ . '/../../config/database.php'; 

// Xử lý tương thích biến kết nối (vì có thể file config dùng $pdo hoặc $conn)
if (!isset($conn) && isset($pdo)) { $conn = $pdo; }
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection variable not found ($conn or $pdo)']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$jsonInput = json_decode($raw, true);
// Input có thể đến từ JSON body hoặc Form POST truyền thống
$input = $jsonInput ?: $_POST;

// --- Helper: Tìm Category ID theo tên ---
function findCategoryId($conn, $name){
    if (empty($name)) return null;
    $stmt = $conn->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $stmt->execute([':name'=>$name]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? intval($r['id']) : null;
}

// =================================================================
// 1. XỬ LÝ GET (READ)
// =================================================================
if ($method === 'GET') {
    
    // TRƯỜNG HỢP A: Lấy chi tiết 1 sản phẩm (Dùng cho product.js trang Edit)
    // product.js mong đợi trả về thẳng Object sản phẩm { "id": 1, "name": "..." }
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id LIMIT 1');
        $stmt->execute([':id'=>intval($_GET['id'])]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE); // Trả về object thuần
        } else { 
            http_response_code(404); 
            echo json_encode(['error'=>'Product not found']); 
        }
        exit;
    }

    // TRƯỜNG HỢP B: Lấy danh sách phân trang (Dùng cho manage.js)
    // manage.js mong đợi { "status": "success", "data": [...], "pagination": {...} }
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Query cơ bản
        $sqlBase = "FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        $where = "";
        $params = [];

        if ($q) {
            $where = " WHERE p.name LIKE :q OR p.chip LIKE :q OR c.name LIKE :q";
            $params[':q'] = '%' . $q . '%';
        }

        // Đếm tổng
        $stmtCount = $conn->prepare("SELECT COUNT(*) as total $sqlBase $where");
        $stmtCount->execute($params);
        $totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Lấy dữ liệu
        // product.js dùng alias 'category', manage.js dùng 'category_name'. 
        // Ta select cả 2 alias để an toàn cho cả 2 bên nếu dùng chung.
        $sqlData = "SELECT p.*, c.name AS category, c.name AS category_name $sqlBase $where ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($sqlData);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
    }
    exit;
}

// =================================================================
// CÁC TRƯỜNG CHO PHÉP UPDATE/INSERT
// =================================================================
$allowed = ['category','name','price','old_price','amount','image','image1','image2','image3','chip','ram','screen','battery','guarantee','outstanding','rating','is_featured'];

// =================================================================
// 2. XỬ LÝ POST (CREATE HOẶC UPDATE FORM)
// =================================================================

// TH1: POST nhưng có ID -> Coi như là UPDATE (Hỗ trợ code cũ form submit thuần)
if ($method === 'POST' && (!empty($input['id']) )) {
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            $fields[] = "$f = :$f";
            if (in_array($f, ['price','old_price','rating'])) $params[':'.$f] = floatval($input[$f]);
            elseif (in_array($f, ['amount','is_featured'])) $params[':'.$f] = intval($input[$f]);
            else $params[':'.$f] = $input[$f];
        }
        // Xử lý category text -> id
        if ($f === 'category' && isset($input['category'])) {
            $cid = findCategoryId($conn, $input['category']);
            $fields[] = "category_id = :category_id";
            $params[':category_id'] = $cid;
            // Xóa trường category thừa nếu lỡ add vào fields
            if (($k = array_search('category = :category', $fields)) !== false) unset($fields[$k]);
        }
    }
    
    if (!empty($fields)) {
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    }
    echo json_encode(['success'=>true]);
    exit;
}

// TH2: POST THẬT (CREATE NEW PRODUCT) - Dùng cho product.js (Step A)
if ($method === 'POST') {
    if (empty($input['name']) || !isset($input['price'])) { 
        http_response_code(400); 
        echo json_encode(['error'=>'Missing name or price']); 
        exit; 
    }
    
    $categoryId = null;
    if (!empty($input['category'])) $categoryId = findCategoryId($conn, $input['category']);

    // Tự động tính ID mới (do cấu trúc cũ của bạn không dùng Auto Increment hoặc muốn kiểm soát ID)
    $stmt = $conn->query('SELECT MAX(id) as mx FROM products');
    $mx = $stmt->fetch(PDO::FETCH_ASSOC);
    $newId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;

    // Chuẩn bị Insert
    $cols = ['id','category_id','name','price','old_price','amount','image','image1','image2','image3','chip','ram','screen','battery','guarantee','outstanding','rating','is_featured','created_at'];
    
    $params = [
        ':id'=>$newId, 
        ':category_id'=>$categoryId, 
        ':name'=>$input['name'], 
        ':price'=>floatval($input['price']), 
        ':old_price'=>isset($input['old_price'])?floatval($input['old_price']):null, 
        ':amount'=>isset($input['amount'])?intval($input['amount']):100, 
        ':image'=>isset($input['image'])?$input['image']:null, 
        ':image1'=>isset($input['image1'])?$input['image1']:null, 
        ':image2'=>isset($input['image2'])?$input['image2']:null, 
        ':image3'=>isset($input['image3'])?$input['image3']:null, 
        ':chip'=>isset($input['chip'])?$input['chip']:null, 
        ':ram'=>isset($input['ram'])?$input['ram']:null, 
        ':screen'=>isset($input['screen'])?$input['screen']:null, 
        ':battery'=>isset($input['battery'])?$input['battery']:null, 
        ':guarantee'=>isset($input['guarantee'])?$input['guarantee']:'12 Tháng', 
        ':outstanding'=>isset($input['outstanding'])?$input['outstanding']:null, 
        ':rating'=>isset($input['rating'])?floatval($input['rating']):5, 
        ':is_featured'=>isset($input['is_featured'])?intval($input['is_featured']):0, 
        ':created_at'=>date('Y-m-d H:i:s')
    ];

    $placeholders = array_map(function($c){ return ':' . $c; }, $cols);
    $sql = 'INSERT INTO products (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // QUAN TRỌNG: product.js cần trả về { "id": ... } để upload ảnh
    echo json_encode(['success'=>true, 'id'=>$newId]);
    exit;
}

// =================================================================
// 3. XỬ LÝ PUT (UPDATE PRODUCT) - Dùng cho product.js (Step C)
// =================================================================
if ($method === 'PUT') {
    if (empty($input['id'])) { 
        http_response_code(400); 
        echo json_encode(['error'=>'Missing id']); 
        exit; 
    }
    
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            // Xử lý đặc biệt cho category
            if ($f === 'category') {
                $cid = findCategoryId($conn, $input['category']);
                $fields[] = 'category_id = :category_id';
                $params[':category_id'] = $cid;
                continue;
            }
            
            $fields[] = "$f = :$f";
            
            // Ép kiểu dữ liệu
            if (in_array($f, ['price','old_price','rating'])) $params[':'.$f] = floatval($input[$f]);
            elseif (in_array($f, ['amount','is_featured'])) $params[':'.$f] = intval($input[$f]);
            else $params[':'.$f] = $input[$f];
        }
    }
    
    if (!empty($fields)) {
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    }
    
    echo json_encode(['success'=>true]);
    exit;
}

// =================================================================
// 4. XỬ LÝ DELETE - Dùng cho manage.js
// =================================================================
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($input['id']) ? intval($input['id']) : 0);
    
    if (!$id) { 
        http_response_code(400); 
        echo json_encode(['error'=>'Missing id']); 
        exit; 
    }
    
    $stmt = $conn->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    
    // manage.js cần success: true
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
?>