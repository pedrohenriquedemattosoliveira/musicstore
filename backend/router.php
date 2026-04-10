<?php
// ============================================
// MUSICSTORE - Roteador para deploy
// O servidor built-in do PHP nÃ£o suporta .htaccess,
// entÃ£o este arquivo resolve as rotas manualmente
// e repassa o header Authorization.
// ============================================

// Em produÃ§Ã£o, evita vazar warnings/stack traces em HTML.
ini_set('display_errors', '0');

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    error_log('Unhandled exception: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro interno no servidor.']);
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    // Converte erros PHP em exceÃ§Ãµes para cair no handler JSON acima.
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Permite chamadas do frontend hospedado.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Repassar Authorization para $_SERVER quando necessÃ¡rio
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // jÃ¡ disponÃ­vel
} elseif (function_exists('getallheaders')) {
    $h = getallheaders();
    if (isset($h['Authorization'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $h['Authorization'];
    }
}

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = __DIR__;

// Health check simples â€” sem banco de dados
if ($uri === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

// Resposta bÃ¡sica na raiz para facilitar testes no Railway.
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

// Servir arquivos estÃ¡ticos que existam (ex.: favicon)
if ($uri !== '/' && file_exists($base . $uri) && !is_dir($base . $uri)) {
    return false;
}

// Roteamento da API - aceita mÃºltiplos formatos de URL.
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
echo json_encode(['error' => 'Rota nÃ£o encontrada']);

