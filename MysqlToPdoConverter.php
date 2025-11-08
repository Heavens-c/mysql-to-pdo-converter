<?php
/**
 * MySQL to PDO Converter
 * Converts MySQL queries to PDO syntax compatible with PostgreSQL
 */
class MysqlToPdoConverter {
    
    /**
     * Convert MySQL query to PDO format compatible with PostgreSQL
     * 
     * @param string $query MySQL query string
     * @param array $params Parameters to bind (optional)
     * @return array ['query' => converted query, 'params' => parameters array]
     */
    public function convert($query, $params = []) {
        $convertedQuery = $query;
        
        // Convert MySQL backticks to PostgreSQL double quotes for identifiers
        $convertedQuery = $this->convertIdentifiers($convertedQuery);
        
        // Convert MySQL placeholders to PDO named placeholders
        $convertedQuery = $this->convertPlaceholders($convertedQuery, $params);
        
        // Convert MySQL-specific functions to PostgreSQL-compatible equivalents
        $convertedQuery = $this->convertFunctions($convertedQuery);
        
        // Convert MySQL data types to PostgreSQL equivalents
        $convertedQuery = $this->convertDataTypes($convertedQuery);
        
        // Convert AUTO_INCREMENT to PostgreSQL SERIAL
        $convertedQuery = $this->convertAutoIncrement($convertedQuery);
        
        // Convert LIMIT syntax
        $convertedQuery = $this->convertLimit($convertedQuery);
        
        return [
            'query' => $convertedQuery,
            'params' => $params
        ];
    }
    
    /**
     * Convert MySQL backticks to PostgreSQL double quotes
     */
    private function convertIdentifiers($query) {
        // Replace backticks with double quotes for identifiers
        return str_replace('`', '"', $query);
    }
    
    /**
     * Convert MySQL ? placeholders to PDO named placeholders
     */
    private function convertPlaceholders($query, &$params) {
        // If params is associative array, assume named placeholders already exist
        if ($this->isAssoc($params)) {
            return $query;
        }
        
        // Convert ? placeholders to :param0, :param1, etc.
        $index = 0;
        $namedParams = [];
        $convertedQuery = preg_replace_callback('/\?/', function($matches) use (&$index, &$namedParams, &$params) {
            $paramName = 'param' . $index;
            if (isset($params[$index])) {
                $namedParams[':' . $paramName] = $params[$index];
            }
            $index++;
            return ':' . $paramName;
        }, $query);
        
        if (!empty($namedParams)) {
            $params = $namedParams;
        }
        
        return $convertedQuery;
    }
    
    /**
     * Convert MySQL-specific functions to PostgreSQL equivalents
     */
    private function convertFunctions($query) {
        $conversions = [
            // String functions
            '/\bGROUP_CONCAT\s*\(/i' => 'STRING_AGG(',
            '/\bIFNULL\s*\(/i' => 'COALESCE(',
            
            // Date functions
            '/\bNOW\s*\(\)/i' => 'CURRENT_TIMESTAMP',
            '/\bCURDATE\s*\(\)/i' => 'CURRENT_DATE',
            '/\bCURTIME\s*\(\)/i' => 'CURRENT_TIME',
            
            // Other functions
            '/\bRAND\s*\(\)/i' => 'RANDOM()',
        ];
        
        foreach ($conversions as $pattern => $replacement) {
            $query = preg_replace($pattern, $replacement, $query);
        }
        
        return $query;
    }
    
    /**
     * Convert MySQL data types to PostgreSQL equivalents
     */
    private function convertDataTypes($query) {
        $conversions = [
            '/\bINT\s*\(\d+\)/i' => 'INTEGER',
            '/\bTINYINT\s*\(\d+\)/i' => 'SMALLINT',
            '/\bTINYINT/i' => 'SMALLINT',
            '/\bDATETIME/i' => 'TIMESTAMP',
            '/\bDOUBLE/i' => 'DOUBLE PRECISION',
            '/\bBLOB/i' => 'BYTEA',
            '/\bMEDIUMTEXT/i' => 'TEXT',
            '/\bLONGTEXT/i' => 'TEXT',
            '/\bENUM\s*\([^)]+\)/i' => 'VARCHAR(255)',  // Simplified conversion
        ];
        
        foreach ($conversions as $pattern => $replacement) {
            $query = preg_replace($pattern, $replacement, $query);
        }
        
        return $query;
    }
    
    /**
     * Convert MySQL AUTO_INCREMENT to PostgreSQL SERIAL
     */
    private function convertAutoIncrement($query) {
        // Convert INT/INTEGER AUTO_INCREMENT to SERIAL
        // This handles the most common cases where AUTO_INCREMENT is used with INT types
        $query = preg_replace('/\bINTEGER\s+AUTO_INCREMENT/i', 'SERIAL', $query);
        $query = preg_replace('/\bINT\s+AUTO_INCREMENT/i', 'SERIAL', $query);
        
        // Remove any remaining standalone AUTO_INCREMENT keywords
        // (in case it appears in other contexts, though this is rare)
        $query = preg_replace('/\s+AUTO_INCREMENT\b/i', '', $query);
        
        return $query;
    }
    
    /**
     * Convert MySQL LIMIT syntax to PostgreSQL compatible format
     */
    private function convertLimit($query) {
        // PostgreSQL supports LIMIT, but let's ensure it's properly formatted
        // MySQL: LIMIT offset, count -> PostgreSQL: LIMIT count OFFSET offset
        $query = preg_replace('/\bLIMIT\s+(\d+)\s*,\s*(\d+)/i', 'LIMIT $2 OFFSET $1', $query);
        
        return $query;
    }
    
    /**
     * Check if array is associative
     */
    private function isAssoc($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Convert a complete MySQL connection to PDO
     * 
     * @param string $host Database host
     * @param string $dbname Database name
     * @param string $username Database username
     * @param string $password Database password
     * @param string $driver PDO driver (default: pgsql for PostgreSQL)
     * @return PDO PDO connection object
     */
    public function createPdoConnection($host, $dbname, $username, $password, $driver = 'pgsql') {
        try {
            $dsn = "$driver:host=$host;dbname=$dbname";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }
    
    /**
     * Execute a MySQL query using PDO with automatic conversion
     * 
     * @param PDO $pdo PDO connection
     * @param string $query MySQL query
     * @param array $params Query parameters
     * @return PDOStatement Executed statement
     */
    public function executeQuery($pdo, $query, $params = []) {
        $converted = $this->convert($query, $params);
        
        $stmt = $pdo->prepare($converted['query']);
        $stmt->execute($converted['params']);
        
        return $stmt;
    }
}
