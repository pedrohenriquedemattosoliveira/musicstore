<?php
// ============================================
// MUSICSTORE - API de Indicadores Admin
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();
requireAdmin();

$db = getDB();

$totalRevenue = $db->query("SELECT COALESCE(SUM(total),0) as v FROM orders WHERE status != 'cancelled'")->fetch()['v'];
$totalOrders  = $db->query("SELECT COUNT(*) as v FROM orders")->fetch()['v'];
$totalProducts= $db->query("SELECT COUNT(*) as v FROM products WHERE active = 1")->fetch()['v'];
$totalUsers   = $db->query("SELECT COUNT(*) as v FROM users WHERE role = 'customer'")->fetch()['v'];
$lowStock     = $db->query("SELECT COUNT(*) as v FROM products WHERE stock <= 3 AND active = 1")->fetch()['v'];

$recentOrders = $db->query("SELECT o.id, o.total, o.status, o.created_at, u.name as customer_name
                             FROM orders o JOIN users u ON o.user_id = u.id
                             ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
foreach ($recentOrders as &$o) {
    $o['id'] = (int)$o['id'];
    $o['total'] = (float)$o['total'];
}

$topProducts = $db->query("SELECT p.name, p.price, SUM(oi.quantity) as sold
                            FROM order_items oi JOIN products p ON oi.product_id = p.id
                            GROUP BY p.id ORDER BY sold DESC LIMIT 5")->fetchAll();
foreach ($topProducts as &$tp) {
    $tp['price'] = (float)$tp['price'];
    $tp['sold']  = (int)$tp['sold'];
}

$ordersByStatus = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status")->fetchAll();

jsonResponse([
    'revenue'        => (float)$totalRevenue,
    'orders'         => (int)$totalOrders,
    'products'       => (int)$totalProducts,
    'customers'      => (int)$totalUsers,
    'low_stock'      => (int)$lowStock,
    'recent_orders'  => $recentOrders,
    'top_products'   => $topProducts,
    'orders_by_status' => $ordersByStatus,
]);

