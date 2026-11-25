<?php
declare(strict_types=1);
$host = 'localhost';
$db   = 'feira_livre';
$user = 'root';
$pass = ''; // padrão do XAMPP; ajuste se seu MySQL tiver senha
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco. Confira o arquivo config.php e importe database.sql.');
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function dinheiro($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
function dataBR($data): string {
    return $data ? date('d/m/Y', strtotime($data)) : '-';
}
function flash(string $mensagem, string $tipo='success'): void {
    $_SESSION['flash'] = ['mensagem'=>$mensagem, 'tipo'=>$tipo];
}
function redirecionar(): never {
    header('Location: index.php');
    exit;
}
