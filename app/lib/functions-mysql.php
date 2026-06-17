<?php
// Database configuration constants
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '@dmin1234S';
const DB_NAME = 'db4apks_webapp';
const DB_CHAR = 'utf8mb4';
const DB_PORT = 3306;

/**
 * Establish a PDO database connection.
 *
 * @return PDO
 */
function db_connected(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch as associative array
            PDO::ATTR_EMULATE_PREPARES => false,                  // use real prepared statements
        ]);

        return $pdo;
    } catch (PDOException $e) {
        // You can log error to file instead of die() in production
        die('❌ Database Connection Failed: ' . $e->getMessage());
    }
}

/**
 * Insert record into MySQL table using PDO and flexible array structure.
 *
 * @param PDO    $pdo     Database connection (PDO)
 * @param string $table   Table name
 * @param array  $fields  Array of ["column" => string, "value" => mixed, "type" => string]
 * @return bool  True on success, false on failure
 */
function insertRecord(PDO $pdo, string $table, array $fields): bool
{
    if (empty($fields)) {
        throw new InvalidArgumentException("No fields provided for insert");
    }

    $columns = [];
    $placeholders = [];
    $params = [];

    foreach ($fields as $i => $f) {
        if (!isset($f['column'], $f['value'])) {
            throw new InvalidArgumentException("Invalid field structure at index $i");
        }

        $columns[] = "`" . $f['column'] . "`";
        $placeholder = ":param_" . $i;
        $placeholders[] = $placeholder;
        $params[$placeholder] = $f['value'];
    }

    $sql = sprintf(
        "INSERT INTO `%s` (%s) VALUES (%s)",
        $table,
        implode(", ", $columns),
        implode(", ", $placeholders)
    );

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}
