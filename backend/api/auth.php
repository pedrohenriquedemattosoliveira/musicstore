<?php
// ============================================
// MUSICSTORE - API de Autenticação
// ============================================

require_once __DIR__ . '/../middleware/auth.php';
setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

match (true) {
    $method === 'POST' && $action === 'register' => handleRegister(),
    $method === 'POST' && $action === 'login'    => handleLogin(),
    $method === 'GET'  && $action === 'me'       => handleMe(),
    default => jsonResponse(['error' => 'Rota não encontrada'], 404)
};

function handleRegister(): void {
    $body = getBody();
    $name  = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';
    $phone = trim($body['phone'] ?? '');

    if (!$name || !$email || !$pass) {
        jsonResponse(['error' => 'Nome, e-mail e senha são obrigatórios.'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'E-mail inválido.'], 400);
    }
    if (strlen($pass) < 6) {
        jsonResponse(['error' => 'A senha deve ter pelo menos 6 caracteres.'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'E-mail já cadastrado.'], 409);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, $phone]);
    $id = $db->lastInsertId();

    $token = generateJWT(['id' => (int)$id, 'email' => $email, 'role' => 'customer', 'name' => $name]);
    jsonResponse(['token' => $token, 'user' => ['id' => (int)$id, 'name' => $name, 'email' => $email, 'role' => 'customer']], 201);
}

function handleLogin(): void {
    $body  = getBody();
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) {
        jsonResponse(['error' => 'E-mail e senha são obrigatórios.'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonResponse(['error' => 'Credenciais inválidas.'], 401);
    }

    $token = generateJWT(['id' => (int)$user['id'], 'email' => $user['email'], 'role' => $user['role'], 'name' => $user['name']]);
    unset($user['password']);
    $user['id'] = (int)$user['id'];
    jsonResponse(['token' => $token, 'user' => $user]);
}

function handleMe(): void {
    $auth = requireAuth();
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, role, phone, address, created_at FROM users WHERE id = ?');
    $stmt->execute([$auth['id']]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['error' => 'Usuário não encontrado.'], 404);
    $user['id'] = (int)$user['id'];
    jsonResponse($user);
}
