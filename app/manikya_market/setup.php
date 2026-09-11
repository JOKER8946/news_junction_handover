<?php

declare(strict_types=1);

$basePath = '/';

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function post_string(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function write_config(string $path, array $config): void
{
    $export = var_export($config, true);
    $php = "<?php\n\nreturn {$export};\n";
    file_put_contents($path, $php);
}

function import_schema(PDO $pdo, string $schemaPath): void
{
    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new RuntimeException('Schema not found');
    }

    $pdo->exec($sql);
}

function ensure_column(PDO $pdo, string $dbName, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $stmt->execute(['db' => $dbName, 't' => $table, 'c' => $column]);
    $row = $stmt->fetch();
    $exists = (int)($row['c'] ?? 0) > 0;
    if ($exists) {
        return;
    }

    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}

function ensure_index(PDO $pdo, string $dbName, string $table, string $indexName, string $createSql): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t AND INDEX_NAME = :i');
    $stmt->execute(['db' => $dbName, 't' => $table, 'i' => $indexName]);
    $row = $stmt->fetch();
    $exists = (int)($row['c'] ?? 0) > 0;
    if ($exists) {
        return;
    }

    $pdo->exec($createSql);
}

$error = null;
$success = null;

$queryError = $_GET['error'] ?? null;
if (is_string($queryError) && $queryError !== '') {
    $error = $queryError;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $dbHost = post_string('db_host');
    $dbPort = (int)(post_string('db_port') ?: '3306');
    $dbName = post_string('db_name');
    $dbUser = post_string('db_user');
    $dbPass = post_string('db_pass');

    $merchantName = post_string('merchant_name');
    $merchantEmail = post_string('merchant_email');
    $merchantPhone = post_string('merchant_phone');
    $merchantPassword = post_string('merchant_password');

    try {
        if ($dbName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            throw new RuntimeException('Invalid database name');
        }

        $serverDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $serverPdo = new PDO($serverDsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        import_schema($pdo, __DIR__ . '/database/schema.sql');

        ensure_column($pdo, $dbName, 'merchant_profile', 'business_gstin', 'VARCHAR(20) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_pan', 'VARCHAR(20) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_state', 'VARCHAR(80) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_pincode', 'VARCHAR(12) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_bank_name', 'VARCHAR(120) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_bank_account', 'VARCHAR(50) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_bank_ifsc', 'VARCHAR(20) NULL');
        ensure_column($pdo, $dbName, 'merchant_profile', 'business_logo_path', 'VARCHAR(255) NULL');

        ensure_column($pdo, $dbName, 'products', 'product_type', 'VARCHAR(80) NULL');
        ensure_column($pdo, $dbName, 'products', 'part_code', 'VARCHAR(30) NULL');
        ensure_index($pdo, $dbName, 'products', 'idx_products_part_code', 'CREATE UNIQUE INDEX idx_products_part_code ON products(part_code)');

        $pdo->exec('CREATE TABLE IF NOT EXISTS inventory (
          id INT AUTO_INCREMENT PRIMARY KEY,
          product_id INT NOT NULL UNIQUE,
          qty_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
          updated_at DATETIME NOT NULL,
          CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $pdo->exec('CREATE TABLE IF NOT EXISTS inventory_movements (
          id INT AUTO_INCREMENT PRIMARY KEY,
          product_id INT NOT NULL,
          movement_type ENUM(\'inward\',\'sale\',\'adjustment\') NOT NULL,
          qty_kg DECIMAL(10,2) NOT NULL,
          ref_type VARCHAR(30) NULL,
          ref_id INT NULL,
          notes VARCHAR(255) NULL,
          created_at DATETIME NOT NULL,
          CONSTRAINT fk_inv_mov_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
          INDEX idx_inv_mov_ref (ref_type, ref_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $hash = password_hash($merchantPassword, PASSWORD_DEFAULT);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => mb_strtolower($merchantEmail)]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO users (role, full_name, phone, email, password_hash, status, created_at) VALUES ('merchant', :full_name, :phone, :email, :password_hash, 'active', NOW())");
            $stmt->execute([
                'full_name' => $merchantName,
                'phone' => $merchantPhone,
                'email' => mb_strtolower($merchantEmail),
                'password_hash' => $hash,
            ]);
            $merchantId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO merchant_profile (merchant_user_id, business_name, created_at) VALUES (:merchant_user_id, :business_name, NOW())");
            $stmt->execute([
                'merchant_user_id' => $merchantId,
                'business_name' => $merchantName,
            ]);
        }

        $pdo->commit();

        $config = [
            'db' => [
                'host' => $dbHost,
                'port' => $dbPort,
                'name' => $dbName,
                'user' => $dbUser,
                'pass' => $dbPass,
                'charset' => 'utf8mb4',
            ],
            'app' => [
                'base_path' => $basePath,
            ],
        ];

        write_config(__DIR__ . '/app/config.php', $config);

        $success = 'Setup completed. You can login as merchant now.';
    } catch (Throwable $t) {
        $error = $t->getMessage();
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manikya Market Setup</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4" style="max-width: 720px;">
    <div class="bg-white border rounded-4 p-4">
      <h1 class="h4">Manikya Market Setup</h1>
      <div class="text-muted small">Creates DB tables and an initial merchant account. Razorpay/Twilio can be linked later in settings.</div>

      <?php if ($error): ?>
        <div class="alert alert-danger mt-3"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success mt-3">
          <?= e($success) ?>
          <div class="mt-2">
            <a class="btn btn-success" href="<?= e($basePath) ?>/?p=merchant/login">Go to Merchant Login</a>
          </div>
        </div>
      <?php endif; ?>

      <form method="post" class="row g-3 mt-1">
        <div class="col-12"><div class="fw-semibold">Database</div></div>
        <div class="col-md-6">
          <label class="form-label">DB Host</label>
          <input class="form-control" name="db_host" value="127.0.0.1" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">DB Port</label>
          <input class="form-control" name="db_port" value="3306" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">DB Name</label>
          <input class="form-control" name="db_name" value="mango_mama" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">DB User</label>
          <input class="form-control" name="db_user" value="root" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">DB Password</label>
          <input class="form-control" name="db_pass" value="">
        </div>

        <div class="col-12"><hr></div>
        <div class="col-12"><div class="fw-semibold">Initial Merchant Account</div></div>

        <div class="col-md-6">
          <label class="form-label">Merchant Name</label>
          <input class="form-control" name="merchant_name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input class="form-control" name="merchant_phone" placeholder="+91...">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="merchant_email" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password</label>
          <input class="form-control" type="password" name="merchant_password" required>
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-success" type="submit">Run Setup</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
