<?php
// ============================================
// MUSICSTORE - API de Pedidos
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();

$user   = requireAuth();
$userId = (int)$user['id'];
$isAdmin = $user['role'] === 'admin';
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

match (true) {
    $method === 'GET'  && !$id => listOrders($userId, $isAdmin),
    $method === 'GET'  && $id  => getOrder($id, $userId, $isAdmin),
    $method === 'POST'          => createOrder($userId),
    $method === 'PUT'  && $id  => updateOrderStatus($id),
    default => jsonResponse(['error' => 'Rota nÃ£o encontrada'], 404)
};

function listOrders(int $userId, bool $isAdmin): void {
    $db = getDB();
    if ($isAdmin) {
        $stmt = $db->query("SELECT o.*, u.name as customer_name, u.email as customer_email,
                                   COUNT(oi.id) as item_count
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            LEFT JOIN order_items oi ON o.id = oi.order_id
                            GROUP BY o.id
                            ORDER BY o.created_at DESC");
    } else {
        $stmt = $db->prepare("SELECT o.*, COUNT(oi.id) as item_count
                              FROM orders o
                              LEFT JOIN order_items oi ON o.id = oi.order_id
                              WHERE o.user_id = ?
                              GROUP BY o.id
                              ORDER BY o.created_at DESC");
        $stmt->execute([$userId]);
    }
    $orders = $stmt->fetchAll();
    foreach ($orders as &$o) {
        $o['id']         = (int)$o['id'];
        $o['user_id']    = (int)$o['user_id'];
        $o['total']      = (float)$o['total'];
        $o['item_count'] = (int)$o['item_count'];
    }
    jsonResponse($orders);
}

function getOrder(int $id, int $userId, bool $isAdmin): void {
    $db   = getDB();
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email
                          FROM orders o JOIN users u ON o.user_id = u.id
                          WHERE o.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) jsonResponse(['error' => 'Pedido nÃ£o encontrado.'], 404);
    if (!$isAdmin && (int)$order['user_id'] !== $userId) {
        jsonResponse(['error' => 'Acesso negado.'], 403);
    }

    $stmt = $db->prepare("SELECT oi.*, p.name as product_name, p.image_url
                          FROM order_items oi JOIN products p ON oi.product_id = p.id
                          WHERE oi.order_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    foreach ($items as &$item) {
        $item['id']         = (int)$item['id'];
        $item['quantity']   = (int)$item['quantity'];
        $item['price']      = (float)$item['price'];
        $item['product_id'] = (int)$item['product_id'];
    }
    $order['id']    = (int)$order['id'];
    $order['total'] = (float)$order['total'];
    $order['items'] = $items;
    jsonResponse($order);
}

function createOrder(int $userId): void {
    $body    = getBody();
    $address = trim($body['shipping_address'] ?? '');
    $notes   = trim($body['notes'] ?? '');

    if (!$address) jsonResponse(['error' => 'EndereÃ§o de entrega Ã© obrigatÃ³rio.'], 400);

    $db = getDB();

    // Busca os itens no carrinho do cliente
    $stmt = $db->prepare("SELECT ci.quantity, p.id as product_id, p.price, p.stock, p.name
                          FROM cart_items ci JOIN products p ON ci.product_id = p.id
                          WHERE ci.user_id = ? AND p.active = 1");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();

    if (!$cartItems) jsonResponse(['error' => 'Carrinho vazio.'], 400);

    // Confere se ainda ha estoque suficiente
    foreach ($cartItems as $item) {
        if ($item['stock'] < $item['quantity']) {
            jsonResponse(['error' => "Estoque insuficiente para: {$item['name']}"], 400);
        }
    }

    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

    $db->beginTransaction();
    try {
        // Cria o pedido principal
        $stmt = $db->prepare("INSERT INTO orders (user_id, total, shipping_address, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $total, $address, $notes]);
        $orderId = (int)$db->lastInsertId();

        // Salva os itens e atualiza o estoque
        foreach ($cartItems as $item) {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

            $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Limpa o carrinho apos finalizar o pedido
        $stmt = $db->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);

        $db->commit();
        jsonResponse(['message' => 'Pedido realizado com sucesso!', 'order_id' => $orderId, 'total' => $total], 201);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Erro ao criar pedido: ' . $e->getMessage()], 500);
    }
}

function updateOrderStatus(int $id): void {
    requireAdmin();
    $body   = getBody();
    $status = $body['status'] ?? '';
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    if (!in_array($status, $allowed)) {
        jsonResponse(['error' => 'Status invÃ¡lido.'], 400);
    }
    $db   = getDB();
    $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Pedido nÃ£o encontrado.'], 404);
    jsonResponse(['message' => 'Status atualizado com sucesso.']);
}

