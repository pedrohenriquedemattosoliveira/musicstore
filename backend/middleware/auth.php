<?php
// ============================================
// MUSICSTORE - JWT & Auth Middleware
// ============================================

require_once __DIR__ . '/../config/database.php';

// Headers CORS e JSON
function setCorsHeaders(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// JWT simples sem biblioteca externa
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function generateJWT(array $payload): string {
    $header  = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $pay     = base64UrlEncode(json_encode($payload));
    $sig     = base64UrlEncode(hash_hmac('sha256', "$header.$pay", JWT_SECRET, true));
    return "$header.$pay.$sig";
}

function verifyJWT(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $signature] = $parts;
    $expected = base64UrlEncode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $signature)) return null;
    $data = json_decode(base64UrlDecode($payload), true);
    if (!$data || $data['exp'] < time()) return null;
    return $data;
}

function getAuthUser(): ?array {
    $auth = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? $_SERVER['Authorization']
        ?? '';

    // Alguns ambientes (Apache/FastCGI) só expõem Authorization via getallheaders().
    if (!$auth && function_exists('getallheaders')) {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!$auth && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    $auth = trim((string)$auth);
    $token = '';

    if ($auth !== '') {
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            $token = trim($matches[1]);
        } else {
            // Fallback: permite receber o token cru sem prefixo Bearer.
            $token = $auth;
        }
    }

    if ($token === '') {
        $token = trim((string)($_SERVER['HTTP_X_AUTH_TOKEN'] ?? ''));
    }

    if ($token === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $token = trim((string)($headers['X-Auth-Token'] ?? $headers['x-auth-token'] ?? ''));
    }

    if ($token === '') {
        return null;
    }

    return verifyJWT($token);
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        http_response_code(401);
        die(json_encode(['error' => 'Não autorizado. Faça login.']));
    }
    return $user;

}

function requireCustomer(): array {
    $user = requireAuth();
    if (($user['role'] ?? '') !== 'customer') {
        http_response_code(403);
        die(json_encode(['error' => 'Acesso restrito a clientes.']));
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Acesso restrito a administradores.']));
    }
    return $user;
}

function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
