<?php
ini_set('memory_limit', '4M');

$dsn = "mysql:host=127.0.0.1;dbname=company;charset=utf8mb4";
$username = "root";
$password = ""; 

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(50) NOT NULL
    )
");

$filePath = 'employee.csv';

if (!file_exists($filePath)) {
    die("CSV file not found.");
}

$handle = fopen($filePath, 'r');
if (!$handle) {
    die("Cannot open file.");
}

$logHandle = fopen('conflicts.log', 'a');

$inserted = 0;
$updated = 0;
$conflicts = 0;
$skipped = 0;

$selectStmt = $pdo->prepare("SELECT name, phone FROM employees WHERE email = ?");
$insertStmt = $pdo->prepare("INSERT INTO employees (name, email, phone) VALUES (?, ?, ?)");
$updateStmt = $pdo->prepare("UPDATE employees SET name = ?, phone = ? WHERE email = ?");

$header = fgetcsv($handle, 0, ",", '"', "\\");
if ($header === false) {
    die("Empty CSV.");
}

while (($row = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {

    if (count(array_filter($row)) === 0) {
        continue;
    }

    if (count($row) < 3) {
        continue; 
    }

    [$name, $email, $phone] = array_map('trim', $row);

    if (empty($email)) {
        continue;
    }

    $selectStmt->execute([$email]);
    $existing = $selectStmt->fetch();

    if (!$existing) {
        $insertStmt->execute([$name, $email, $phone]);
        $inserted++;
        continue;
    }

    if ($existing['name'] === $name && $existing['phone'] === $phone) {
        $skipped++;
        continue;
    }

    $conflicts++;
    fwrite($logHandle, sprintf(
        "[%s] Conflict for %s | OLD: (%s, %s) | NEW: (%s, %s)\n",
        date('Y-m-d H:i:s'),
        $email,
        $existing['name'],
        $existing['phone'],
        $name,
        $phone
    ));

    $updateStmt->execute([$name, $phone, $email]);
    $updated++;
}

fclose($handle);
fclose($logHandle);

echo "Inserted: $inserted | Updated: $updated | Conflicts Logged: $conflicts | Skipped: $skipped\n";