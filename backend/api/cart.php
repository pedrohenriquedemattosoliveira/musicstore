<?php
// ============================================
// MUSICSTORE - API do Carrinho
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();

$user   = requireCustomer();
$userId = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $method === 'GET'    => getCart($userId),
    $method === 'POST'   && $action === 'add'    => addItem($userId),
    $method === 'PUT'    && $action === 'update'  => updateItem($userId),
    $method === 'DELETE' && $action === 'remove'  => removeItem($userId),
    $method === 'DELETE' && $action === 'clear'   => clearCart($userId),
    default => jsonResponse(['error' => 'Rota não encontrada'], 404)
};

function getCart(int $userId): void {
    $db   = getDB();
    $stmt = $db->prepare("SELECT ci.id, ci.quantity, p.id as product_id, p.name, p.price,
                                 p.image_url, p.stock, p.brand
                          FROM cart_items ci
                          JOIN products p ON ci.product_id = p.id
                          WHERE ci.user_id = ?");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $item['id']         = (int)$item['id'];
        $item['quantity']   = (int)$item['quantity'];
        $item['product_id'] = (int)$item['product_id'];
        $item['price']      = (float)$item['price'];
        $item['stock']      = (int)$item['stock'];
    }
    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
    jsonResponse(['items' => $items, 'total' => round($total, 2), 'count' => count($items)]);
}

function addItem(int $userId): void {
    $body      = getBody();
    $productId = (int)($body['product_id'] ?? 0);
    $qty       = max(1, (int)($body['quantity'] ?? 1));

    if (!$productId) jsonResponse(['error' => 'Produto inválido.'], 400);

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, stock FROM products WHERE id = ? AND active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) jsonResponse(['error' => 'Produto não encontrado.'], 404);
    if ($product['stock'] < $qty) jsonResponse(['error' => 'Estoque insuficiente.'], 400);

    $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity)
                          VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->execute([$userId, $productId, $qty, $qty]);
    jsonResponse(['message' => 'Item adicionado ao carrinho.']);
}

function updateItem(int $userId): void {
    $body      = getBody();
    $productId = (int)($body['product_id'] ?? 0);
    $qty       = (int)($body['quantity'] ?? 0);

    if ($qty <= 0) {
        $stmt = getDB()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        jsonResponse(['message' => 'Item removido.']);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT stock FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if ($product && $product['stock'] < $qty) {
        jsonResponse(['error' => 'Estoque insuficiente. Disponível: ' . $product['stock']], 400);
    }

    $stmt = $db->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$qty, $userId, $productId]);
    jsonResponse(['message' => 'Carrinho atualizado.']);
}

function removeItem(int $userId): void {
    $productId = (int)($_GET['product_id'] ?? 0);
    $stmt = getDB()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
    jsonResponse(['message' => 'Item removido do carrinho.']);
}

function clearCart(int $userId): void {
    $stmt = getDB()->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    jsonResponse(['message' => 'Carrinho limpo.']);
}
