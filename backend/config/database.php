<?php
// ============================================
// MUSICSTORE - ConfiguraÃ§Ã£o do Banco de Dados
// ============================================

// Suporte a variÃ¡veis de ambiente do Railway (ou .env local)
// Essas variaveis definem acesso ao banco.
define('DB_HOST',    getenv('MYSQLHOST')     ?: (getenv('DB_HOST')     ?: 'localhost'));
define('DB_PORT',    getenv('MYSQLPORT')     ?: (getenv('DB_PORT')     ?: '3306'));
define('DB_USER',    getenv('MYSQLUSER')     ?: (getenv('DB_USER')     ?: 'root'));
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS')     ?: ''));
define('DB_NAME',    getenv('MYSQLDATABASE') ?: (getenv('DB_NAME')     ?: 'musicstore'));
define('DB_CHARSET', 'utf8mb4');
define('JWT_SECRET', getenv('JWT_SECRET')    ?: 'musicstore_secret_key_2024_change_in_production');
define('JWT_EXPIRY', 86400); // 24 horas em segundos

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Erro de conexÃ£o com o banco de dados: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

