<?php
// ============================================
// MUSICSTORE - API de Categorias
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];

match ($method) {
    'GET'  => listCategories(),
    default => jsonResponse(['error' => 'Método não permitido'], 405)
};

function listCategories(): void {
    $db   = getDB();
    $stmt = $db->query("SELECT c.*, COUNT(p.id) as product_count
                        FROM categories c
                        LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
                        GROUP BY c.id ORDER BY c.name");
    $cats = $stmt->fetchAll();
    foreach ($cats as &$c) {
        $c['id'] = (int)$c['id'];
        $c['product_count'] = (int)$c['product_count'];
    }
    jsonResponse($cats);
}
