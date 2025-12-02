<?php
// Products API - MySQL-backed (enhanced to support full product fields)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$jsonInput = json_decode($raw, true);
$input = $jsonInput ?: $_POST;

function findCategoryId($conn, $name){
    if (empty($name)) return null;
    $stmt = $conn->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $stmt->execute([':name'=>$name]);
    $r = $stmt->fetch();
    return $r ? intval($r['id']) : null;
}

// GET: list or single product (include category name)
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id LIMIT 1');
        $stmt->execute([':id'=>intval($_GET['id'])]);
        $row = $stmt->fetch();
        if ($row) echo json_encode($row, JSON_UNESCAPED_UNICODE);
        else { http_response_code(404); echo json_encode(['error'=>'Product not found']); }
        exit;
    }
    if (isset($_GET['q'])) {
        $q = '%' . $_GET['q'] . '%';
        $stmt = $conn->prepare('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE :q OR p.chip LIKE :q ORDER BY p.id DESC');
        $stmt->execute([':q'=>$q]);
        $rows = $stmt->fetchAll();
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $conn->query('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC');
    $rows = $stmt->fetchAll();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper: collect allowed fields
$allowed = ['category','name','price','old_price','amount','image','image1','image2','image3','chip','ram','screen','battery','guarantee','outstanding','rating','is_featured'];

// If POST contains an id -> treat as update (supports form POST updates)
if ($method === 'POST' && (!empty($input['id']) )) {
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            $fields[] = "$f = :$f";
            // cast booleans/numbers
            if (in_array($f, ['price','old_price','rating'])) $params[':'.$f] = floatval($input[$f]);
            elseif (in_array($f, ['amount','is_featured'])) $params[':'.$f] = intval($input[$f]);
            else $params[':'.$f] = $input[$f];
        }
        if ($f === 'category' && isset($input['category'])) {
            $cid = findCategoryId($conn, $input['category']);
            $fields[] = "category_id = :category_id";
            $params[':category_id'] = $cid;
            // avoid duplicate 'category' field insertion
            // remove explicit category if set
            if (($k = array_search('category = :category', $fields)) !== false) unset($fields[$k]);
        }
    }
    if (empty($fields)) { echo json_encode(['success'=>true]); exit; }
    $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success'=>true]);
    exit;
}

if ($method === 'POST') {
    // Create new product
    if (empty($input['name']) || !isset($input['price'])) { http_response_code(400); echo json_encode(['error'=>'Missing name or price']); exit; }
    $categoryId = null;
    if (!empty($input['category'])) $categoryId = findCategoryId($conn, $input['category']);

    // determine new id if table not auto-increment
    $stmt = $conn->query('SELECT MAX(id) as mx FROM products');
    $mx = $stmt->fetch();
    $newId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;

    // Build insert columns and params
    $cols = ['id','category_id','name','price','old_price','amount','image','image1','image2','image3','chip','ram','screen','battery','guarantee','outstanding','rating','is_featured','created_at'];
    $params = [':id'=>$newId, ':category_id'=>$categoryId, ':name'=>$input['name'], ':price'=>floatval($input['price']), ':old_price'=>isset($input['old_price'])?floatval($input['old_price']):null, ':amount'=>isset($input['amount'])?intval($input['amount']):100, ':image'=>isset($input['image'])?$input['image']:null, ':image1'=>isset($input['image1'])?$input['image1']:null, ':image2'=>isset($input['image2'])?$input['image2']:null, ':image3'=>isset($input['image3'])?$input['image3']:null, ':chip'=>isset($input['chip'])?$input['chip']:null, ':ram'=>isset($input['ram'])?$input['ram']:null, ':screen'=>isset($input['screen'])?$input['screen']:null, ':battery'=>isset($input['battery'])?$input['battery']:null, ':guarantee'=>isset($input['guarantee'])?$input['guarantee']:'12 Tháng', ':outstanding'=>isset($input['outstanding'])?$input['outstanding']:null, ':rating'=>isset($input['rating'])?floatval($input['rating']):5, ':is_featured'=>isset($input['is_featured'])?intval($input['is_featured']):0, ':created_at'=>date('Y-m-d H:i:s')];

    $placeholders = array_map(function($c){ return ':' . $c; }, $cols);
    $sql = 'INSERT INTO products (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success'=>true, 'id'=>$newId]);
    exit;
}

if ($method === 'PUT') {
    if (empty($input['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            if ($f === 'category') {
                $cid = findCategoryId($conn, $input['category']);
                $fields[] = 'category_id = :category_id';
                $params[':category_id'] = $cid;
                continue;
            }
            $fields[] = "$f = :$f";
            if (in_array($f, ['price','old_price','rating'])) $params[':'.$f] = floatval($input[$f]);
            elseif (in_array($f, ['amount','is_featured'])) $params[':'.$f] = intval($input[$f]);
            else $params[':'.$f] = $input[$f];
        }
    }
    if (empty($fields)) { echo json_encode(['success'=>true]); exit; }
    $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success'=>true]);
    exit;
}

if ($method === 'DELETE') {
    $id = null;
    if (isset($_GET['id'])) $id = intval($_GET['id']);
    elseif (!empty($input['id'])) $id = intval($input['id']);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $stmt = $conn->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
