<?php
// Products API - MySQL-backed
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

function findCategoryId($conn, $name){
    if (empty($name)) return null;
    $stmt = $conn->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $stmt->execute([':name'=>$name]);
    $r = $stmt->fetch();
    return $r ? intval($r['id']) : null;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id'=>intval($_GET['id'])]);
        $row = $stmt->fetch();
        if ($row) echo json_encode($row, JSON_UNESCAPED_UNICODE);
        else { http_response_code(404); echo json_encode(['error'=>'Product not found']); }
        exit;
    }
    if (isset($_GET['q'])) {
        $q = '%' . $_GET['q'] . '%';
        $stmt = $conn->prepare('SELECT * FROM products WHERE name LIKE :q OR chip LIKE :q ORDER BY id DESC');
        $stmt->execute([':q'=>$q]);
        $rows = $stmt->fetchAll();
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $conn->query('SELECT * FROM products ORDER BY id DESC');
    $rows = $stmt->fetchAll();
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    if (empty($input['name']) || !isset($input['price'])) { http_response_code(400); echo json_encode(['error'=>'Missing name or price']); exit; }
    $name = $input['name'];
    $price = floatval($input['price']);
    $image = !empty($input['image']) ? $input['image'] : null;
    $categoryId = null;
    if (!empty($input['category'])) $categoryId = findCategoryId($conn, $input['category']);

    // determine new id if table not auto-increment
    $stmt = $conn->query('SELECT MAX(id) as mx FROM products');
    $mx = $stmt->fetch();
    $newId = ($mx && $mx['mx']) ? intval($mx['mx']) + 1 : 1;

    $sql = 'INSERT INTO products (id, category_id, name, price, image, created_at) VALUES (:id, :cid, :name, :price, :image, NOW())';
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id'=>$newId, ':cid'=>$categoryId, ':name'=>$name, ':price'=>$price, ':image'=>$image]);
    echo json_encode(['success'=>true, 'id'=>$newId]);
    exit;
}

if ($method === 'PUT') {
    if (empty($input['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = intval($input['id']);
    $fields = [];
    $params = [':id'=>$id];
    if (isset($input['name'])) { $fields[] = 'name = :name'; $params[':name'] = $input['name']; }
    if (isset($input['price'])) { $fields[] = 'price = :price'; $params[':price'] = floatval($input['price']); }
    if (isset($input['image'])) { $fields[] = 'image = :image'; $params[':image'] = $input['image']; }
    if (isset($input['category'])) { $cid = findCategoryId($conn, $input['category']); $fields[] = 'category_id = :cid'; $params[':cid'] = $cid; }
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
