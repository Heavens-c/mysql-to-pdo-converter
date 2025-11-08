# MySQL to PDO Converter (PostgreSQL Compatible)

A PHP library that automatically converts MySQL query syntax to PDO (PHP Data Objects) format with PostgreSQL compatibility.

## Features

- 🔄 **Automatic Conversion**: Converts MySQL queries to PDO format
- 🐘 **PostgreSQL Compatible**: All conversions are compatible with PostgreSQL
- 🔧 **Identifier Conversion**: Converts MySQL backticks (`) to PostgreSQL double quotes (")
- 📝 **Placeholder Conversion**: Converts `?` placeholders to named parameters (`:param0`, `:param1`, etc.)
- 🔨 **Function Mapping**: Converts MySQL-specific functions to PostgreSQL equivalents
- 📊 **Data Type Conversion**: Converts MySQL data types to PostgreSQL types
- ⚡ **AUTO_INCREMENT Support**: Converts AUTO_INCREMENT to PostgreSQL SERIAL
- 🎯 **LIMIT Syntax**: Converts MySQL LIMIT syntax to PostgreSQL format

## Conversions Supported

### Identifiers
- MySQL backticks (`) → PostgreSQL double quotes (")
- Example: `` `users` `` → `"users"`

### Placeholders
- MySQL `?` → PDO named placeholders (`:param0`, `:param1`, etc.)
- Named parameters (`:name`) are preserved as-is

### Functions
| MySQL Function | PostgreSQL Equivalent |
|----------------|----------------------|
| `IFNULL()` | `COALESCE()` |
| `NOW()` | `CURRENT_TIMESTAMP` |
| `CURDATE()` | `CURRENT_DATE` |
| `CURTIME()` | `CURRENT_TIME` |
| `RAND()` | `RANDOM()` |
| `GROUP_CONCAT()` | `STRING_AGG()` |

### Data Types
| MySQL Type | PostgreSQL Type |
|-----------|----------------|
| `INT(n)` | `INTEGER` |
| `TINYINT` | `SMALLINT` |
| `DATETIME` | `TIMESTAMP` |
| `DOUBLE` | `DOUBLE PRECISION` |
| `BLOB` | `BYTEA` |
| `MEDIUMTEXT` / `LONGTEXT` | `TEXT` |
| `ENUM(...)` | `VARCHAR(255)` |

### Special Features
- `AUTO_INCREMENT` → `SERIAL`
- `LIMIT offset, count` → `LIMIT count OFFSET offset`

## Installation

Simply include the `MysqlToPdoConverter.php` file in your project:

```php
require_once 'MysqlToPdoConverter.php';
```

## Usage

### Basic Query Conversion

```php
require_once 'MysqlToPdoConverter.php';

$converter = new MysqlToPdoConverter();

// Convert a simple query
$mysqlQuery = "SELECT `id`, `name` FROM `users` WHERE `status` = ?";
$result = $converter->convert($mysqlQuery, [1]);

echo $result['query'];  // SELECT "id", "name" FROM "users" WHERE "status" = :param0
print_r($result['params']);  // [':param0' => 1]
```

### Create PostgreSQL Connection

```php
$pdo = $converter->createPdoConnection(
    'localhost',  // host
    'mydb',       // database name
    'username',   // username
    'password',   // password
    'pgsql'       // driver (default: pgsql)
);
```

### Execute Converted Query

```php
// Execute a MySQL query with automatic conversion
$stmt = $converter->executeQuery(
    $pdo, 
    "SELECT * FROM `users` WHERE `id` = ?", 
    [1]
);

$result = $stmt->fetchAll();
```

### CREATE TABLE Example

```php
$mysqlCreateTable = "CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100),
    `email` VARCHAR(100),
    `created_at` DATETIME
)";

$result = $converter->convert($mysqlCreateTable);
echo $result['query'];

// Output:
// CREATE TABLE "users" (
//     "id" SERIAL PRIMARY KEY,
//     "name" VARCHAR(100),
//     "email" VARCHAR(100),
//     "created_at" TIMESTAMP
// )
```

### Complex Query Example

```php
$mysqlQuery = "SELECT `u`.`id`, IFNULL(`u`.`name`, 'Guest'), NOW() 
               FROM `users` `u` 
               WHERE `u`.`status` = ? 
               LIMIT 5, 10";

$result = $converter->convert($mysqlQuery, ['active']);

// Converts to:
// SELECT "u"."id", COALESCE("u"."name", 'Guest'), CURRENT_TIMESTAMP 
// FROM "users" "u" 
// WHERE "u"."status" = :param0 
// LIMIT 10 OFFSET 5
```

## Running Examples

Run the examples file to see various conversion scenarios:

```bash
php examples.php
```

## Running Tests

Run the test suite to verify all conversions work correctly:

```bash
php tests.php
```

All 22 tests should pass with PostgreSQL-compatible output.

## API Reference

### `convert($query, $params = [])`
Converts a MySQL query to PDO format compatible with PostgreSQL.

**Parameters:**
- `$query` (string): MySQL query string
- `$params` (array): Optional parameters array

**Returns:** Array with keys:
- `query`: Converted query string
- `params`: Parameters array (converted if needed)

### `createPdoConnection($host, $dbname, $username, $password, $driver = 'pgsql')`
Creates a PDO connection to PostgreSQL.

**Parameters:**
- `$host` (string): Database host
- `$dbname` (string): Database name
- `$username` (string): Username
- `$password` (string): Password
- `$driver` (string): PDO driver (default: 'pgsql')

**Returns:** PDO connection object

### `executeQuery($pdo, $query, $params = [])`
Executes a MySQL query using PDO with automatic conversion.

**Parameters:**
- `$pdo` (PDO): PDO connection object
- `$query` (string): MySQL query
- `$params` (array): Query parameters

**Returns:** PDOStatement object

## Migration Guide

### From MySQL to PostgreSQL

1. **Update your connection:**
   ```php
   // Old MySQL
   $conn = mysqli_connect($host, $user, $pass, $db);
   
   // New PDO PostgreSQL
   $converter = new MysqlToPdoConverter();
   $pdo = $converter->createPdoConnection($host, $db, $user, $pass);
   ```

2. **Convert your queries:**
   ```php
   // Old MySQL query
   $query = "SELECT `id` FROM `users` WHERE `status` = ?";
   
   // Convert and execute
   $result = $converter->convert($query, [1]);
   $stmt = $pdo->prepare($result['query']);
   $stmt->execute($result['params']);
   ```

3. **Or use the helper method:**
   ```php
   $stmt = $converter->executeQuery($pdo, $query, [1]);
   ```

## Limitations

- `ENUM` types are converted to `VARCHAR(255)` (you may want to use PostgreSQL's native ENUM or CHECK constraints)
- Some MySQL-specific features may require manual conversion
- Complex stored procedures may need additional handling

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues for bugs and feature requests.

## License

This project is open source and available under the MIT License.

## Requirements

- PHP 7.0 or higher
- PDO extension with PostgreSQL driver (pdo_pgsql)

## Support

For issues, questions, or contributions, please visit the GitHub repository.