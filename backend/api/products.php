<?php
// ============================================
// MUSICSTORE - API de Produtos
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

match (true) {
    $method === 'GET'    && !$id  => listProducts(),
    $method === 'GET'    && $id   => getProduct($id),
    $method === 'POST'            => createProduct(),
    $method === 'PUT'    && $id   => updateProduct($id),
    $method === 'DELETE' && $id   => deleteProduct($id),
    default => jsonResponse(['error' => 'Rota não encontrada'], 404)
};

function listProducts(): void {
    $db = getDB();
    $where = ['p.active = 1'];
    $params = [];

    if (!empty($_GET['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['search'])) {
        $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)';
        $s = '%' . $_GET['search'] . '%';
        $params = array_merge($params, [$s, $s, $s]);
    }
    if (isset($_GET['featured'])) {
        $where[] = 'p.featured = 1';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereStr
            ORDER BY p.featured DESC, p.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    foreach ($products as &$p) {
        $p['id']           = (int)$p['id'];
        $p['price']        = (float)$p['price'];
        $p['stock']        = (int)$p['stock'];
        $p['featured']     = (bool)$p['featured'];
        $p['active']       = (bool)$p['active'];
        $p['category_id']  = $p['category_id'] ? (int)$p['category_id'] : null;
    }

    // Retorna todos se admin (inclui inativos se pedido)
    $user = getAuthUser();
    if ($user && $user['role'] === 'admin' && isset($_GET['all'])) {
        $stmt2 = $db->query("SELECT p.*, c.name as category_name, c.slug as category_slug
                              FROM products p LEFT JOIN categories c ON p.category_id = c.id
                              ORDER BY p.created_at DESC");
        $products = $stmt2->fetchAll();
        foreach ($products as &$p) {
            $p['id']    = (int)$p['id'];
            $p['price'] = (float)$p['price'];
            $p['stock'] = (int)$p['stock'];
            $p['featured'] = (bool)$p['featured'];
            $p['active']   = (bool)$p['active'];
            $p['category_id'] = $p['category_id'] ? (int)$p['category_id'] : null;
        }
    }

    jsonResponse($products);
}

function getProduct(int $id): void {
    $db   = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) jsonResponse(['error' => 'Produto não encontrado.'], 404);
    $p['id']    = (int)$p['id'];
    $p['price'] = (float)$p['price'];
    $p['stock'] = (int)$p['stock'];
    $p['featured'] = (bool)$p['featured'];
    $p['active']   = (bool)$p['active'];
    jsonResponse($p);
}

function createProduct(): void {
    requireAdmin();
    $body = getBody();
    $name    = trim($body['name'] ?? '');
    $price   = (float)($body['price'] ?? 0);
    $stock   = (int)($body['stock'] ?? 0);
    $catId   = $body['category_id'] ? (int)$body['category_id'] : null;
    $desc    = trim($body['description'] ?? '');
    $image   = trim($body['image_url'] ?? '');
    $brand   = trim($body['brand'] ?? '');
    $sku     = trim($body['sku'] ?? '');
    $featured = (bool)($body['featured'] ?? false);

    if (!$name || $price <= 0) {
        jsonResponse(['error' => 'Nome e preço são obrigatórios.'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO products (name, description, price, stock, category_id, image_url, brand, sku, featured)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $desc, $price, $stock, $catId, $image, $brand, $sku ?: null, $featured]);
    $newId = (int)$db->lastInsertId();
    jsonResponse(['message' => 'Produto criado com sucesso.', 'id' => $newId], 201);
}

function updateProduct(int $id): void {
    requireAdmin();
    $body = getBody();
    $db   = getDB();

    $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Produto não encontrado.'], 404);

    $fields = [];
    $params = [];
    $allowed = ['name','description','price','stock','category_id','image_url','brand','sku','featured','active'];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            $params[]  = $body[$field];
        }
    }
    if (!$fields) jsonResponse(['error' => 'Nenhum campo para atualizar.'], 400);
    $params[] = $id;
    $stmt = $db->prepare("UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);
    jsonResponse(['message' => 'Produto atualizado com sucesso.']);
}

function deleteProduct(int $id): void {
    requireAdmin();
    $db   = getDB();
    $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Produto não encontrado.'], 404);
    jsonResponse(['message' => 'Produto excluído com sucesso.']);
}
