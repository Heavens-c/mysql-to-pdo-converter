<?php
/**
 * Real-world migration example from MySQL to PostgreSQL using PDO
 * This demonstrates a complete migration scenario
 */

require_once 'MysqlToPdoConverter.php';

echo "=== Real-World Migration Example ===\n\n";
echo "This example shows how to migrate from MySQL to PostgreSQL PDO\n\n";

// Initialize converter
$converter = new MysqlToPdoConverter();

// ===== SCENARIO: User Management System =====
echo "--- Scenario: User Management System ---\n\n";

// 1. CREATE TABLE
echo "1. Creating Users Table\n";
$createTableMySQL = "CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT NOW(),
    `last_login` DATETIME,
    `is_active` TINYINT(1) DEFAULT 1,
    `profile_data` MEDIUMTEXT,
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
)";

$createTablePDO = $converter->convert($createTableMySQL);
echo "PostgreSQL DDL:\n";
echo $createTablePDO['query'] . "\n\n";

// 2. INSERT
echo "2. Inserting New User\n";
$insertMySQL = "INSERT INTO `users` (`username`, `email`, `password_hash`, `profile_data`) 
                VALUES (?, ?, ?, ?)";
$insertParams = ['johndoe', 'john@example.com', password_hash('secret123', PASSWORD_DEFAULT), '{"bio": "Developer"}'];
$insertPDO = $converter->convert($insertMySQL, $insertParams);
echo "Query: {$insertPDO['query']}\n";
echo "Params: " . json_encode($insertPDO['params']) . "\n\n";

// 3. SELECT with JOIN
echo "3. Fetching Active Users with Login Stats\n";
$selectMySQL = "SELECT `u`.`user_id`, `u`.`username`, `u`.`email`, 
                IFNULL(`u`.`last_login`, 'Never') as `last_login_display`,
                NOW() as `query_time`
                FROM `users` `u`
                WHERE `u`.`is_active` = ? 
                ORDER BY `u`.`created_at` DESC
                LIMIT 0, 10";
$selectParams = [1];
$selectPDO = $converter->convert($selectMySQL, $selectParams);
echo "Query:\n{$selectPDO['query']}\n";
echo "Params: " . json_encode($selectPDO['params']) . "\n\n";

// 4. UPDATE
echo "4. Updating User Last Login\n";
$updateMySQL = "UPDATE `users` SET `last_login` = NOW() WHERE `user_id` = ?";
$updateParams = [1];
$updatePDO = $converter->convert($updateMySQL, $updateParams);
echo "Query: {$updatePDO['query']}\n";
echo "Params: " . json_encode($updatePDO['params']) . "\n\n";

// 5. Complex SELECT with aggregation
echo "5. User Statistics Query\n";
$statsMySQL = "SELECT 
    DATE(`created_at`) as `signup_date`,
    COUNT(*) as `user_count`,
    RAND() as `random_sample`
    FROM `users`
    WHERE `created_at` >= CURDATE() - INTERVAL 30 DAY
    GROUP BY DATE(`created_at`)
    ORDER BY `signup_date` DESC
    LIMIT 10, 20";
$statsPDO = $converter->convert($statsMySQL);
echo "Query:\n{$statsPDO['query']}\n\n";

// 6. DELETE
echo "6. Soft Delete Inactive Users\n";
$deleteMySQL = "UPDATE `users` SET `is_active` = ? WHERE `last_login` < CURDATE() - INTERVAL 365 DAY";
$deleteParams = [0];
$deletePDO = $converter->convert($deleteMySQL, $deleteParams);
echo "Query: {$deletePDO['query']}\n";
echo "Params: " . json_encode($deletePDO['params']) . "\n\n";

// ===== CONNECTION EXAMPLE =====
echo "--- Connection Setup ---\n\n";
echo "// MySQL (old way)\n";
echo '$mysql_conn = mysqli_connect("localhost", "user", "pass", "mydb");' . "\n\n";

echo "// PostgreSQL with PDO (new way)\n";
echo '$converter = new MysqlToPdoConverter();' . "\n";
echo '$pdo = $converter->createPdoConnection("localhost", "mydb", "user", "pass");' . "\n\n";

// ===== EXECUTION EXAMPLE =====
echo "--- Query Execution Example ---\n\n";
echo "// Execute and fetch results\n";
echo '$stmt = $converter->executeQuery($pdo, ' . "\n";
echo '    "SELECT `user_id`, `username` FROM `users` WHERE `is_active` = ?",' . "\n";
echo '    [1]' . "\n";
echo ');' . "\n";
echo '$users = $stmt->fetchAll(PDO::FETCH_ASSOC);' . "\n\n";

// ===== SUMMARY =====
echo "=== Migration Summary ===\n\n";
echo "Key Changes:\n";
echo "✓ Backticks (`) → Double quotes (\")\n";
echo "✓ ? placeholders → Named parameters (:param0, :param1, ...)\n";
echo "✓ AUTO_INCREMENT → SERIAL\n";
echo "✓ INT(n) → INTEGER\n";
echo "✓ DATETIME → TIMESTAMP\n";
echo "✓ TINYINT → SMALLINT\n";
echo "✓ MEDIUMTEXT → TEXT\n";
echo "✓ NOW() → CURRENT_TIMESTAMP\n";
echo "✓ CURDATE() → CURRENT_DATE\n";
echo "✓ IFNULL() → COALESCE()\n";
echo "✓ RAND() → RANDOM()\n";
echo "✓ LIMIT offset, count → LIMIT count OFFSET offset\n\n";

echo "Benefits of PDO:\n";
echo "• Database abstraction (can switch between MySQL and PostgreSQL)\n";
echo "• Prepared statements (SQL injection protection)\n";
echo "• Named parameters (better readability)\n";
echo "• Exception handling\n";
echo "• Better error reporting\n\n";

echo "Note: Some MySQL-specific features like INTERVAL syntax may need\n";
echo "      manual adjustment for PostgreSQL. This converter handles the\n";
echo "      most common conversions automatically.\n";
