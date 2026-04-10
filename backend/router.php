<?php
// ============================================
// MUSICSTORE - Router para Railway (php -S)
// O servidor built-in do PHP não suporta .htaccess,
// então este arquivo resolve as rotas manualmente
// e repassa o header Authorization.
// ============================================

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

// Servir arquivos estáticos que existam (ex.: favicon)
if ($uri !== '/' && file_exists($base . $uri) && !is_dir($base . $uri)) {
    return false;
}

// Roteamento da API
$map = [
    '/api/auth.php'        => $base . '/api/auth.php',
    '/api/products.php'    => $base . '/api/products.php',
    '/api/cart.php'        => $base . '/api/cart.php',
    '/api/orders.php'      => $base . '/api/orders.php',
    '/api/categories.php'  => $base . '/api/categories.php',
    '/api/admin_stats.php' => $base . '/api/admin_stats.php',
];

// Match por prefixo para suportar query string
foreach ($map as $prefix => $file) {
    if (str_starts_with($uri, $prefix)) {
        require $file;
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Rota não encontrada']);
