<?php
// ============================================
// MUSICSTORE - Router para Railway (php -S)
// O servidor built-in do PHP não suporta .htaccess,
// então este arquivo resolve as rotas manualmente
// e repassa o header Authorization.
// ============================================

// CORS global para garantir preflight em qualquer rota.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Repassar Authorization para $_SERVER quando necessário
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // já disponível
} elseif (function_exists('getallheaders')) {
    $h = getallheaders();
    if (isset($h['Authorization'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $h['Authorization'];
    }
}

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = __DIR__;

// Health check simples — sem banco de dados
if ($uri === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

// Resposta básica na raiz para facilitar testes no Railway.
if ($uri === '/' || $uri === '') {
    header('Content-Type: application/json');
    echo json_encode([
        'name' => 'MusicStore API',
        'status' => 'ok',
        'endpoints' => [
            '/health',
            '/api/categories.php',
            '/api/products.php',
            '/api/auth.php?action=login',
        ],
    ]);
    exit;
}

// Servir arquivos estáticos que existam (ex.: favicon)
if ($uri !== '/' && file_exists($base . $uri) && !is_dir($base . $uri)) {
    return false;
}

// Roteamento da API - aceita múltiplos formatos de URL.
$routeMap = [
    'auth.php'        => $base . '/api/auth.php',
    'products.php'    => $base . '/api/products.php',
    'cart.php'        => $base . '/api/cart.php',
    'orders.php'      => $base . '/api/orders.php',
    'categories.php'  => $base . '/api/categories.php',
    'admin_stats.php' => $base . '/api/admin_stats.php',
];

$normalizedUri = preg_replace('#^/backend#', '', $uri);
$normalizedUri = preg_replace('#^/api/#', '', $normalizedUri);
$normalizedUri = ltrim($normalizedUri, '/');

if (isset($routeMap[$normalizedUri])) {
    require $routeMap[$normalizedUri];
    exit;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Rota não encontrada']);
