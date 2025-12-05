<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

try {
    // GET: check product stock(s)
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // single product
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT id, name, price, amount FROM products WHERE id = :id LIMIT 1');
            $stmt->execute([':id'=>$id]);
            $prod = $stmt->fetch();
            if (!$prod) { http_response_code(404); echo json_encode(['error'=>'Product not found']); exit; }
            echo json_encode($prod, JSON_UNESCAPED_UNICODE);
            exit;
        } else if (isset($_GET['ids'])) {
            // multiple products: ?ids=1,2,3
            $ids = array_map('intval', array_filter(explode(',', $_GET['ids'])));
            if (empty($ids)) { http_response_code(400); echo json_encode(['error'=>'Invalid ids']); exit; }
            $in = implode(',', $ids);
            $stmt = $conn->query('SELECT id, name, price, amount FROM products WHERE id IN (' . $in . ')');
            $products = $stmt->fetchAll();
            echo json_encode(['products'=>$products], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // POST: validate/check if items can be added (qty <= available)
    if ($method === 'POST') {
        $action = isset($input['action']) ? $input['action'] : null;
        
        if ($action === 'validate_cart') {
            // expect: { items: [{product_id, quantity}, ...] }
            // return: { valid: true/false, errors: [...], available: {product_id: qty} }
            if (empty($input['items']) || !is_array($input['items'])) {
                http_response_code(400);
                echo json_encode(['error'=>'Missing or invalid items array']);
                exit;
            }

            $product_ids = array_map(function($it) { return intval($it['product_id'] ?? 0); }, $input['items']);
            $product_ids = array_filter($product_ids);
            if (empty($product_ids)) {
                http_response_code(400);
                echo json_encode(['error'=>'No valid product_ids']);
                exit;
            }

            $in = implode(',', $product_ids);
            $stmt = $conn->query('SELECT id, amount FROM products WHERE id IN (' . $in . ')');
            $products = $stmt->fetchAll();
            $available = [];
            foreach ($products as $p) { $available[$p['id']] = intval($p['amount']); }

            $valid = true;
            $errors = [];
            foreach ($input['items'] as $item) {
                $pid = intval($item['product_id'] ?? 0);
                $qty = intval($item['quantity'] ?? 0);
                if ($qty <= 0) { $valid = false; $errors[] = "Item $pid has invalid quantity"; continue; }
                if (!isset($available[$pid])) { $valid = false; $errors[] = "Product $pid not found"; continue; }
                if ($available[$pid] < $qty) { $valid = false; $errors[] = "Product $pid: only {$available[$pid]} available, requested $qty"; }
            }

            echo json_encode(['valid'=>$valid, 'errors'=>$errors, 'available'=>$available], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // POST: deduct inventory (called when order completes)
        if ($action === 'deduct') {
            // expect: { items: [{product_id, quantity}, ...] }
            // return: { success: true/false }
            if (empty($input['items']) || !is_array($input['items'])) {
                http_response_code(400);
                echo json_encode(['error'=>'Missing or invalid items array']);
                exit;
            }

            $stmt = $conn->prepare('UPDATE products SET amount = amount - :qty WHERE id = :id');
            foreach ($input['items'] as $item) {
                $pid = intval($item['product_id'] ?? 0);
                $qty = intval($item['quantity'] ?? 0);
                if ($pid > 0 && $qty > 0) {
                    $stmt->execute([':qty'=>$qty, ':id'=>$pid]);
                }
            }
            echo json_encode(['success'=>true]);
            exit;
        }

        // POST: restore inventory (called when admin cancels cart)
        if ($action === 'restore') {
            // expect: { items: [{product_id, quantity}, ...] }
            // return: { success: true/false }
            if (empty($input['items']) || !is_array($input['items'])) {
                http_response_code(400);
                echo json_encode(['error'=>'Missing or invalid items array']);
                exit;
            }

            $stmt = $conn->prepare('UPDATE products SET amount = amount + :qty WHERE id = :id');
            foreach ($input['items'] as $item) {
                $pid = intval($item['product_id'] ?? 0);
                $qty = intval($item['quantity'] ?? 0);
                if ($pid > 0 && $qty > 0) {
                    $stmt->execute([':qty'=>$qty, ':id'=>$pid]);
                }
            }
            echo json_encode(['success'=>true]);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['error'=>'Invalid request']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
    exit;
}
