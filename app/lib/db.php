<?php
function db() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = getenv('DB_HOST') ;
    $port = getenv('DB_PORT') ;
    $dbname = getenv('MYSQL_DATABASE'); 
    $user = getenv('MYSQL_USER') ;
    $pass = getenv('MYSQL_PASSWORD');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    return $pdo;
}
