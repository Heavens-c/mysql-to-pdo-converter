# MySQL to PDO Converter (PostgreSQL Compatible)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-blue.svg)](https://php.net)
[![Python Version](https://img.shields.io/badge/python-%3E%3D%203.x-blue.svg)](https://python.org)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A lightweight PHP utility and Python CLI migration tool designed to facilitate seamless transitions from legacy MySQL setups to PostgreSQL-compatible PHP PDO database transactions.

---

## 🚀 Key Features

*   🔄 **Automatic PHP SQL Conversion**: Translates MySQL queries to PostgreSQL-compatible PDO format on-the-fly.
*   🐍 **Python CLI Migration Utility**: Bulk-scans directories of legacy PHP source files and converts deprecated `mysql_*` functions into modern, secure PDO queries.
*   🐘 **PostgreSQL Compatible DDL/DML**: Handles identifier conversions, AUTO_INCREMENT columns, limit syntaxes, and standard PostgreSQL schema types.
*   🔒 **Prepared Statement Optimization**: Converts raw query arguments and positional `?` placeholders into secure named parameters (`:param0`, `:param1`, etc.) to mitigate SQL Injection.
*   🔨 **Function Mapping**: Automatically translates MySQL-specific SQL functions to their PostgreSQL counterparts.

---

## 📁 Repository Structure

```text
.
├── MysqlToPdoConverter.php     # Main PHP query converter engine class
├── convert_mysql_to_pdo.py     # Python CLI migration and translation utility
├── examples.php                # Practical examples of conversions
├── migration-example.php       # Real-world user-migration scenarios
├── tests.php                   # Diagnostic unit test suite
└── README.md                   # Project documentation
```

---

## 📦 Setup & Installation

### PHP Engine
Simply include `MysqlToPdoConverter.php` in your codebase:
```php
require_once 'MysqlToPdoConverter.php';
```

### Python CLI Migration Utility
No external dependencies are required. Just ensure Python 3.x is installed on your local environment.

---

## 💡 Usage Guide

### 1. On-the-fly Query Conversion (PHP)

#### Basic Query Translation
```php
$converter = new MysqlToPdoConverter();

$mysqlQuery = "SELECT `id`, `name` FROM `users` WHERE `status` = ?";
$result = $converter->convert($mysqlQuery, [1]);

echo $result['query'];  // Output: SELECT "id", "name" FROM "users" WHERE "status" = :param0
print_r($result['params']);  // Output: [':param0' => 1]
```

#### Establishing a PostgreSQL PDO Connection
```php
$pdo = $converter->createPdoConnection(
    'localhost',  // Host
    'mydb',       // Database Name
    'username',   // Username
    'password',   // Password
    'pgsql'       // Driver (default: pgsql)
);
```

#### Executing Converted Queries Directly
```php
$stmt = $converter->executeQuery(
    $pdo, 
    "SELECT * FROM `users` WHERE `id` = ?", 
    [1]
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

#### Schema (DDL) Conversions
```php
$mysqlDDL = "CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100),
    `created_at` DATETIME
)";

$result = $converter->convert($mysqlDDL);
echo $result['query'];
/*
Output:
CREATE TABLE "users" (
    "id" SERIAL PRIMARY KEY,
    "username" VARCHAR(100),
    "created_at" TIMESTAMP
)
*/
```

---

### 2. Bulk Codebase Migration (Python CLI)

The `convert_mysql_to_pdo.py` script helps scan entire PHP codebases to refactor legacy `mysql_*` functions into PDO syntax.

#### Running the Utility
By default, the script scans the current working directory. You can also supply a custom path as an argument:

```bash
# Scan the current directory
python convert_mysql_to_pdo.py

# Scan a specific directory
python convert_mysql_to_pdo.py "C:\xampp\htdocs\my_project"
```

#### How it refactors code:
1.  **Refactors Functions**: Translates legacy methods like `mysql_query()`, `mysql_fetch_assoc()`, and `mysql_num_rows()` into their PDO counterparts.
2.  **Applies File Suffixes**: Saves the refactored output into a separate `<filename>_pdo.php` file, ensuring your original legacy code is never overwritten.
3.  **Appends Connection Header**: Automatically injects a database connection template file header containing the PDO instantiation snippet for database calls.
4.  **Ignores Suffix Files**: Ignores already-converted `*_pdo.php` files to prevent double-conversion cycles.

---

## 📊 Conversion Rules Reference

### Identifiers
*   MySQL Backticks (`` ` ``) $\rightarrow$ PostgreSQL Double Quotes (`"`)
*   *Example*: `` `users` `` $\rightarrow$ `"users"`

### SQL Functions
| MySQL Function | PostgreSQL Equivalent | Description |
|---|---|---|
| `IFNULL()` | `COALESCE()` | Returns first non-null argument |
| `NOW()` | `CURRENT_TIMESTAMP` | Returns current date and time |
| `CURDATE()` | `CURRENT_DATE` | Returns current date |
| `CURTIME()` | `CURRENT_TIME` | Returns current time |
| `RAND()` | `RANDOM()` | Returns random decimal value |
| `GROUP_CONCAT()` | `STRING_AGG()` | Concatenates database rows into strings |

### Data Types
| MySQL DataType | PostgreSQL DataType |
|---|---|
| `INT(n)` / `INT` | `INTEGER` |
| `TINYINT` / `TINYINT(n)` | `SMALLINT` |
| `DATETIME` | `TIMESTAMP` |
| `DOUBLE` | `DOUBLE PRECISION` |
| `BLOB` | `BYTEA` |
| `MEDIUMTEXT` / `LONGTEXT` | `TEXT` |
| `ENUM(...)` | `VARCHAR(255)` *(simplified replacement)* |
| `AUTO_INCREMENT` | `SERIAL` *(when combined with integer key)* |

### Limits
*   MySQL Limit: `LIMIT offset, count` $\rightarrow$ PostgreSQL: `LIMIT count OFFSET offset`
*   *Example*: `LIMIT 5, 10` $\rightarrow$ `LIMIT 10 OFFSET 5`

---

## 🧪 Testing

The codebase comes package-ready with a test runner verifying all SQL conversion edge cases.

To run the diagnostics suite:
```bash
php tests.php
```

To view the functional example runner:
```bash
php examples.php
```

---

## ⚠️ Known Limitations

*   **ENUM Types**: Converted simple replacements to `VARCHAR(255)`. Native PostgreSQL enums or `CHECK` constraints are recommended for production environments.
*   **Literal Placeholders**: Single `?` symbols embedded within SQL string literal values (e.g. `status = 'Is it ready?'`) might be parsed by regex as parameter placeholders. Use named binding arguments where possible.
*   **Stored Procedures**: Complex MySQL triggers, procedures, or functions require manual translation to PL/pgSQL.

---

## 📄 License

This project is open-source and licensed under the **MIT License**.