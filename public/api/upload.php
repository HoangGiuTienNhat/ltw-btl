<?php
// Enhanced image upload endpoint. Saves to public/pic/product/ and returns path(s).
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

$uploadDir = __DIR__ . '/../pic/product/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

if (empty($_FILES)) {
    http_response_code(400);
    echo json_encode(['error' => 'No files uploaded']);
    exit;
}

function findCategoryIdLocal($conn, $name){
    if (empty($name)) return null;
    $stmt = $conn->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $stmt->execute([':name'=>$name]);
    $r = $stmt->fetch();
    return $r ? intval($r['id']) : null;
}

// attempt to get category and product info from POST
$post = $_POST;
$saved = [];

foreach ($_FILES as $field => $fileInfo) {
    // normalize multiple and single
    if (is_array($fileInfo['name'])) {
        foreach ($fileInfo['name'] as $idx => $name) {
            if ($fileInfo['error'][$idx] !== UPLOAD_ERR_OK) continue;
            $tmp = $fileInfo['tmp_name'][$idx];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);

            // determine naming pieces
            $category_id = null;
            if (!empty($post['category_id'])) $category_id = intval($post['category_id']);
            elseif (!empty($post['category'])) $category_id = findCategoryIdLocal($conn, $post['category']);
            elseif (!empty($post['product_id'])) {
                $stmt = $conn->prepare('SELECT category_id FROM products WHERE id = :id LIMIT 1');
                $stmt->execute([':id'=>intval($post['product_id'])]);
                $r = $stmt->fetch();
                if ($r) $category_id = intval($r['category_id']);
            }

            $product_num = null;
            if (!empty($post['product_num'])) $product_num = str_pad(intval($post['product_num']), 2, '0', STR_PAD_LEFT);
            elseif (!empty($post['product_id'])) $product_num = str_pad(intval($post['product_id']) % 100, 2, '0', STR_PAD_LEFT);

            $image_index = isset($post['image_index']) ? intval($post['image_index']) : $idx;

            $targetName = null;
            if ($category_id !== null && $product_num !== null && $image_index !== null) {
                $prefix = substr(strval($category_id), -1); // take last digit of category id to keep single digit
                $targetBase = $prefix . $product_num . strval($image_index);
                $targetName = $targetBase . ($safeExt ? '.' . $safeExt : '');
                // if file exists, append random suffix to avoid overwrite
                if (file_exists($uploadDir . $targetName)) {
                    $targetName = $targetBase . '-' . bin2hex(random_bytes(3)) . ($safeExt ? '.' . $safeExt : '');
                }
            } else {
                $basename = bin2hex(random_bytes(8));
                $targetName = $basename . ($safeExt ? '.' . $safeExt : '');
            }

            $targetPath = $uploadDir . $targetName;
            if (move_uploaded_file($tmp, $targetPath)) {
                $saved[] = 'pic/product/' . $targetName;
            }
        }
    } else {
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) continue;
        $tmp = $fileInfo['tmp_name'];
        $name = $fileInfo['name'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);

        $category_id = null;
        if (!empty($post['category_id'])) $category_id = intval($post['category_id']);
        elseif (!empty($post['category'])) $category_id = findCategoryIdLocal($conn, $post['category']);
        elseif (!empty($post['product_id'])) {
            $stmt = $conn->prepare('SELECT category_id FROM products WHERE id = :id LIMIT 1');
            $stmt->execute([':id'=>intval($post['product_id'])]);
            $r = $stmt->fetch();
            if ($r) $category_id = intval($r['category_id']);
        }

        $product_num = null;
        if (!empty($post['product_num'])) $product_num = str_pad(intval($post['product_num']), 2, '0', STR_PAD_LEFT);
        elseif (!empty($post['product_id'])) $product_num = str_pad(intval($post['product_id']) % 100, 2, '0', STR_PAD_LEFT);

        $image_index = isset($post['image_index']) ? intval($post['image_index']) : 0;

        $targetName = null;
        if ($category_id !== null && $product_num !== null && $image_index !== null) {
            $prefix = substr(strval($category_id), -1);
            $targetBase = $prefix . $product_num . strval($image_index);
            $targetName = $targetBase . ($safeExt ? '.' . $safeExt : '');
            if (file_exists($uploadDir . $targetName)) {
                $targetName = $targetBase . '-' . bin2hex(random_bytes(3)) . ($safeExt ? '.' . $safeExt : '');
            }
        } else {
            $basename = bin2hex(random_bytes(8));
            $targetName = $basename . ($safeExt ? '.' . $safeExt : '');
        }

        $targetPath = $uploadDir . $targetName;
        if (move_uploaded_file($tmp, $targetPath)) {
            $saved[] = 'pic/product/' . $targetName;
        }
    }
}

if (empty($saved)) {
    http_response_code(500);
    echo json_encode(['error' => 'No files saved']);
    exit;
}

if (count($saved) === 1) echo json_encode(['success' => true, 'path' => $saved[0]]);
else echo json_encode(['success' => true, 'paths' => $saved]);
