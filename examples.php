<?php
/**
 * Example usage of MySQL to PDO Converter
 */

require_once 'MysqlToPdoConverter.php';

// Create converter instance
$converter = new MysqlToPdoConverter();

echo "=== MySQL to PDO Converter Examples ===\n\n";

// Example 1: Simple SELECT query with backticks
echo "Example 1: Convert identifiers (backticks to double quotes)\n";
$mysqlQuery1 = "SELECT `id`, `name`, `email` FROM `users` WHERE `status` = ?";
$result1 = $converter->convert($mysqlQuery1, [1]);
echo "MySQL:  $mysqlQuery1\n";
echo "PDO:    {$result1['query']}\n";
echo "Params: " . json_encode($result1['params']) . "\n\n";

// Example 2: INSERT query with AUTO_INCREMENT
echo "Example 2: Convert CREATE TABLE with AUTO_INCREMENT\n";
$mysqlQuery2 = "CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100),
    `email` VARCHAR(100),
    `created_at` DATETIME
)";
$result2 = $converter->convert($mysqlQuery2);
echo "MySQL:\n$mysqlQuery2\n";
echo "PDO:\n{$result2['query']}\n\n";

// Example 3: MySQL functions to PostgreSQL
echo "Example 3: Convert MySQL functions to PostgreSQL\n";
$mysqlQuery3 = "SELECT IFNULL(name, 'Unknown'), NOW(), RAND() FROM `users`";
$result3 = $converter->convert($mysqlQuery3);
echo "MySQL:  $mysqlQuery3\n";
echo "PDO:    {$result3['query']}\n\n";

// Example 4: LIMIT with offset
echo "Example 4: Convert LIMIT syntax\n";
$mysqlQuery4 = "SELECT * FROM `users` LIMIT 10, 20";
$result4 = $converter->convert($mysqlQuery4);
echo "MySQL:  $mysqlQuery4\n";
echo "PDO:    {$result4['query']}\n\n";

// Example 5: Complex query with multiple features
echo "Example 5: Complex query with multiple conversions\n";
$mysqlQuery5 = "SELECT `u`.`id`, `u`.`name`, IFNULL(`u`.`email`, 'no-email'), 
    NOW() as `created` 
    FROM `users` `u` 
    WHERE `u`.`status` = ? AND `u`.`created_at` > CURDATE() 
    LIMIT 0, 10";
$result5 = $converter->convert($mysqlQuery5, ['active']);
echo "MySQL:\n$mysqlQuery5\n";
echo "PDO:\n{$result5['query']}\n";
echo "Params: " . json_encode($result5['params']) . "\n\n";

// Example 6: Data type conversions
echo "Example 6: Convert data types\n";
$mysqlQuery6 = "CREATE TABLE `products` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255),
    `price` DOUBLE,
    `description` MEDIUMTEXT,
    `stock` TINYINT(1),
    `created_at` DATETIME
)";
$result6 = $converter->convert($mysqlQuery6);
echo "MySQL:\n$mysqlQuery6\n";
echo "PDO:\n{$result6['query']}\n\n";

// Example 7: Named parameters (already in PDO format)
echo "Example 7: Named parameters (no conversion needed)\n";
$mysqlQuery7 = "SELECT * FROM `users` WHERE `email` = :email AND `status` = :status";
$result7 = $converter->convert($mysqlQuery7, [':email' => 'test@example.com', ':status' => 1]);
echo "MySQL:  $mysqlQuery7\n";
echo "PDO:    {$result7['query']}\n";
echo "Params: " . json_encode($result7['params']) . "\n\n";

echo "=== Connection Example ===\n";
echo "To create a PostgreSQL PDO connection:\n";
echo '$pdo = $converter->createPdoConnection("localhost", "mydb", "user", "password");' . "\n\n";

echo "=== Execute Query Example ===\n";
echo "To execute a converted query:\n";
echo '$stmt = $converter->executeQuery($pdo, "SELECT * FROM `users` WHERE `id` = ?", [1]);' . "\n";
echo '$result = $stmt->fetchAll();' . "\n";
