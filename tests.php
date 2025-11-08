<?php
/**
 * Test suite for MySQL to PDO Converter
 */

require_once 'MysqlToPdoConverter.php';

class MysqlToPdoConverterTest {
    private $converter;
    private $passed = 0;
    private $failed = 0;
    
    public function __construct() {
        $this->converter = new MysqlToPdoConverter();
    }
    
    public function run() {
        echo "=== MySQL to PDO Converter Test Suite ===\n\n";
        
        $this->testIdentifierConversion();
        $this->testPlaceholderConversion();
        $this->testFunctionConversion();
        $this->testDataTypeConversion();
        $this->testAutoIncrementConversion();
        $this->testLimitConversion();
        $this->testNamedParameters();
        $this->testComplexQuery();
        
        echo "\n=== Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
    
    private function assert($condition, $message) {
        if ($condition) {
            $this->passed++;
            echo "✓ $message\n";
        } else {
            $this->failed++;
            echo "✗ $message\n";
        }
    }
    
    private function testIdentifierConversion() {
        echo "Test: Identifier Conversion (backticks to double quotes)\n";
        
        $query = "SELECT `id`, `name` FROM `users`";
        $result = $this->converter->convert($query);
        $expected = 'SELECT "id", "name" FROM "users"';
        
        $this->assert(
            $result['query'] === $expected,
            "Backticks should be converted to double quotes"
        );
    }
    
    private function testPlaceholderConversion() {
        echo "\nTest: Placeholder Conversion (? to named)\n";
        
        $query = "SELECT * FROM users WHERE id = ? AND status = ?";
        $params = [1, 'active'];
        $result = $this->converter->convert($query, $params);
        
        $this->assert(
            strpos($result['query'], ':param0') !== false,
            "First placeholder should be converted to :param0"
        );
        
        $this->assert(
            strpos($result['query'], ':param1') !== false,
            "Second placeholder should be converted to :param1"
        );
        
        $this->assert(
            isset($result['params'][':param0']) && $result['params'][':param0'] === 1,
            "First parameter should be mapped correctly"
        );
        
        $this->assert(
            isset($result['params'][':param1']) && $result['params'][':param1'] === 'active',
            "Second parameter should be mapped correctly"
        );
    }
    
    private function testFunctionConversion() {
        echo "\nTest: Function Conversion\n";
        
        // Test IFNULL
        $query1 = "SELECT IFNULL(name, 'default')";
        $result1 = $this->converter->convert($query1);
        $this->assert(
            strpos($result1['query'], 'COALESCE') !== false,
            "IFNULL should be converted to COALESCE"
        );
        
        // Test NOW()
        $query2 = "SELECT NOW()";
        $result2 = $this->converter->convert($query2);
        $this->assert(
            strpos($result2['query'], 'CURRENT_TIMESTAMP') !== false,
            "NOW() should be converted to CURRENT_TIMESTAMP"
        );
        
        // Test RAND()
        $query3 = "SELECT RAND()";
        $result3 = $this->converter->convert($query3);
        $this->assert(
            strpos($result3['query'], 'RANDOM()') !== false,
            "RAND() should be converted to RANDOM()"
        );
        
        // Test CURDATE()
        $query4 = "SELECT CURDATE()";
        $result4 = $this->converter->convert($query4);
        $this->assert(
            strpos($result4['query'], 'CURRENT_DATE') !== false,
            "CURDATE() should be converted to CURRENT_DATE"
        );
    }
    
    private function testDataTypeConversion() {
        echo "\nTest: Data Type Conversion\n";
        
        // Test INT(11)
        $query1 = "CREATE TABLE test (id INT(11))";
        $result1 = $this->converter->convert($query1);
        $this->assert(
            strpos($result1['query'], 'INTEGER') !== false && strpos($result1['query'], 'INT(11)') === false,
            "INT(11) should be converted to INTEGER"
        );
        
        // Test DATETIME
        $query2 = "CREATE TABLE test (created DATETIME)";
        $result2 = $this->converter->convert($query2);
        $this->assert(
            strpos($result2['query'], 'TIMESTAMP') !== false,
            "DATETIME should be converted to TIMESTAMP"
        );
        
        // Test TINYINT
        $query3 = "CREATE TABLE test (flag TINYINT)";
        $result3 = $this->converter->convert($query3);
        $this->assert(
            strpos($result3['query'], 'SMALLINT') !== false,
            "TINYINT should be converted to SMALLINT"
        );
        
        // Test MEDIUMTEXT
        $query4 = "CREATE TABLE test (content MEDIUMTEXT)";
        $result4 = $this->converter->convert($query4);
        $this->assert(
            strpos($result4['query'], 'TEXT') !== false && strpos($result4['query'], 'MEDIUMTEXT') === false,
            "MEDIUMTEXT should be converted to TEXT"
        );
    }
    
    private function testAutoIncrementConversion() {
        echo "\nTest: AUTO_INCREMENT Conversion\n";
        
        $query = "CREATE TABLE test (id INT AUTO_INCREMENT PRIMARY KEY)";
        $result = $this->converter->convert($query);
        
        $this->assert(
            strpos($result['query'], 'SERIAL') !== false,
            "INT AUTO_INCREMENT should be converted to SERIAL"
        );
        
        $this->assert(
            strpos($result['query'], 'AUTO_INCREMENT') === false,
            "AUTO_INCREMENT keyword should be removed"
        );
    }
    
    private function testLimitConversion() {
        echo "\nTest: LIMIT Conversion\n";
        
        $query = "SELECT * FROM users LIMIT 10, 20";
        $result = $this->converter->convert($query);
        
        $this->assert(
            strpos($result['query'], 'LIMIT 20 OFFSET 10') !== false,
            "LIMIT 10, 20 should be converted to LIMIT 20 OFFSET 10"
        );
    }
    
    private function testNamedParameters() {
        echo "\nTest: Named Parameters (should remain unchanged)\n";
        
        $query = "SELECT * FROM users WHERE email = :email";
        $params = [':email' => 'test@example.com'];
        $result = $this->converter->convert($query, $params);
        
        $this->assert(
            $result['params'][':email'] === 'test@example.com',
            "Named parameters should be preserved"
        );
    }
    
    private function testComplexQuery() {
        echo "\nTest: Complex Query with Multiple Conversions\n";
        
        $query = "SELECT `u`.`id`, IFNULL(`u`.`name`, 'Guest'), NOW() 
                  FROM `users` `u` 
                  WHERE `u`.`status` = ? 
                  LIMIT 5, 10";
        $params = ['active'];
        $result = $this->converter->convert($query, $params);
        
        $this->assert(
            strpos($result['query'], '"u"."id"') !== false,
            "Complex query should convert identifiers"
        );
        
        $this->assert(
            strpos($result['query'], 'COALESCE') !== false,
            "Complex query should convert functions"
        );
        
        $this->assert(
            strpos($result['query'], 'CURRENT_TIMESTAMP') !== false,
            "Complex query should convert NOW()"
        );
        
        $this->assert(
            strpos($result['query'], ':param0') !== false,
            "Complex query should convert placeholders"
        );
        
        $this->assert(
            strpos($result['query'], 'LIMIT 10 OFFSET 5') !== false,
            "Complex query should convert LIMIT syntax"
        );
    }
}

// Run tests
$test = new MysqlToPdoConverterTest();
$success = $test->run();

exit($success ? 0 : 1);
