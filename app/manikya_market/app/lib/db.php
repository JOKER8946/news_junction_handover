<?php

declare(strict_types=1);

function db_connect(array $config): PDO
{
    $db = $config['db'] ?? [];

    $host = (string)($db['host'] ?? '127.0.0.1');
    $port = (int)($db['port'] ?? 3306);
    $name = (string)($db['name'] ?? 'king_mango');
    $user = (string)($db['user'] ?? 'root');
    $pass = (string)($db['pass'] ?? 'Temp@1752025!');
    $charset = (string)($db['charset'] ?? 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function db_fetch_one(PDO $db, string $sql, array $params = []): ?array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function db_fetch_all(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function db_exec(PDO $db, string $sql, array $params = []): int
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}
