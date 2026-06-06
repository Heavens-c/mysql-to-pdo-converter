# Database Migration Guide: Legacy PHP MySQL to PostgreSQL PDO

Welcome! This guide is designed for computer science and web development students. It covers the core concepts of migrating legacy PHP applications from the deprecated `mysql_*` extension to modern PHP Data Objects (PDO) with PostgreSQL compatibility.

---

## 📚 Table of Contents
1. [The Evolution of PHP Database APIs](#1-the-evolution-of-php-database-apis)
2. [Security 101: Preventing SQL Injection](#2-security-101-preventing-sql-injection)
3. [Database Dialect Differences: MySQL vs. PostgreSQL](#3-database-dialect-differences-mysql-vs-postgresql)
4. [How the PHP Converter Engine Works](#4-how-the-php-converter-engine-works)
5. [How the Python CLI Refactoring Script Works](#5-how-the-python-cli-refactoring-script-works)
6. [Hands-On Lab: Refactoring a Legacy File](#6-hands-on-lab-refactoring-a-legacy-file)

---

## 1. The Evolution of PHP Database APIs

Historically, PHP developers used the **original MySQL extension** (`mysql_*` functions) to interact with databases. As the language evolved, this API became outdated, insecure, and was officially **removed in PHP 7.0**.

Today, we use **PHP Data Objects (PDO)**. Here is why:

### Comparison Table

| Feature | Legacy `mysql_*` Extension | Modern PHP Data Objects (PDO) |
|---|---|---|
| **Programming Style** | Procedural (functions) | Object-Oriented (classes & objects) |
| **Database Support** | Only MySQL | 12+ database drivers (MySQL, PostgreSQL, SQLite, etc.) |
| **Security (SQLi)** | Manual escaping (`mysql_real_escape_string`) | Native Prepared Statements (extremely secure) |
| **Error Handling** | Returns error codes/strings (manual check) | Supports Object-Oriented Exceptions (`PDOException`) |
| **Status in PHP** | **Removed** since PHP 7.0 | **Active** and recommended standard |

### Code Comparison

**Legacy MySQL (Insecure & Obsolete):**
```php
$conn = mysql_connect("localhost", "user", "password");
mysql_select_db("school_db", $conn);

$id = $_GET['id'];
$query = "SELECT * FROM students WHERE student_id = " . $id; 
$result = mysql_query($query);
$row = mysql_fetch_assoc($result);
```

**Modern PDO (Secure & Portable):**
```php
$dsn = "pgsql:host=localhost;dbname=school_db";
$pdo = new PDO($dsn, "postgres", "password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$_GET['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

---

## 2. Security 101: Preventing SQL Injection

One of the main reasons for moving away from legacy `mysql_*` functions is the threat of **SQL Injection (SQLi)**.

### What is SQL Injection?
SQL Injection occurs when user input is directly concatenated into an SQL query string. This allows attackers to manipulate the query structure.

#### Vulnerable Code Example:
```php
$username = $_POST['username']; // User enters: ' OR '1'='1
$password = $_POST['password']; // User enters: ' OR '1'='1

$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// The query evaluates to:
// SELECT * FROM users WHERE username = '' OR '1'='1' AND password = '' OR '1'='1'
```
Since `'1'='1'` is always true, the attacker successfully logs in without credentials.

### How Prepared Statements Prevent SQLi
When using PDO Prepared Statements:
1. The SQL query structure is sent to the database server **first** with placeholders (`?` or `:name`).
2. The database parses and compiles the query execution plan.
3. The database binds the parameter values separately. 
4. The database treats the inputs **strictly as literal values**, never as executable SQL commands. Even if an attacker passes `' OR '1'='1`, the database searches for a literal username equal to the string `"' OR '1'='1"`.

---

## 3. Database Dialect Differences: MySQL vs. PostgreSQL

SQL is a standard language, but different database management systems (DBMS) implement custom variants called **dialects**.

| Feature / Syntax | MySQL | PostgreSQL | Why it matters |
|---|---|---|---|
| **Identifier Quoting** | Backticks: `` `users` `` | Double quotes: `"users"` | Used to escape reserved keywords or case-sensitive tables/columns. |
| **Auto-Increment Key** | `INT AUTO_INCREMENT` | `SERIAL` | PostgreSQL handles auto-increment values through background Sequence objects. |
| **String Aggregation** | `GROUP_CONCAT(name)` | `STRING_AGG(name, ',')` | Merges multiple rows into a single concatenated string. |
| **Null Fallback** | `IFNULL(val, default)` | `COALESCE(val, default)` | Returns the first non-null parameter in a list. |
| **Random Number** | `RAND()` | `RANDOM()` | Returns a random decimal value between 0 and 1. |
| **Query Limit** | `LIMIT offset, count` | `LIMIT count OFFSET offset` | Used for pagination. PostgreSQL uses explicit offset keyword syntax. |

---

## 4. How the PHP Converter Engine Works

The file [MysqlToPdoConverter.php](file:///c:/Users/dhudz/Documents/MYSQL-TO-PDO-/MysqlToPdoConverter.php) is the engine that converts MySQL syntax to PostgreSQL PDO queries. It performs these operations in stages:

### Step 1: Quoting Identifiers
Replaces all backticks with double quotes using string replacement:
```php
str_replace('`', '"', $query);
```

### Step 2: Positional to Named Placeholders
Converts raw `?` positional parameters to named placeholders (`:param0`, `:param1`) so that PDO query binding is explicit and readable:
```php
// If input is: SELECT * FROM users WHERE id = ? AND email = ?
// Output query: SELECT * FROM users WHERE id = :param0 AND email = :param1
// Output params: [':param0' => $val1, ':param1' => $val2]
```

### Step 3: Regular Expression (Regex) Replacements
It matches and replaces MySQL-specific data types and functions using `preg_replace` with word boundary anchors (`\b`):
```php
'/\bDATETIME/i' => 'TIMESTAMP'
'/\bIFNULL\s*\(/i' => 'COALESCE('
'/\bLIMIT\s+(\d+)\s*,\s*(\d+)/i' => 'LIMIT $2 OFFSET $1'
```

---

## 5. How the Python CLI Refactoring Script Works

The utility [convert_mysql_to_pdo.py](file:///c:/Users/dhudz/Documents/MYSQL-TO-PDO-/convert_mysql_to_pdo.py) scans your files locally to automatically modernize old procedural code.

### The Conversion Logic:
1. **Search**: It scans the directory recursively looking for files ending with `.php`.
2. **Safety Check**: It ignores any file ending in `_pdo.php` so it does not run in a loop or double-convert files.
3. **Replacement**: It searches for legacy function signatures using Python's `re` module:
   * `mysql_query(...)` $\rightarrow$ `$stmt = $pdo->query(...)`
   * `mysql_fetch_assoc(...)` $\rightarrow$ `...->fetch(PDO::FETCH_ASSOC)`
   * `mysql_num_rows(...)` $\rightarrow$ `...->rowCount()`
4. **PDO Injection**: If the file contained old `mysql_` functions, the script prepends a standard PDO connection block at the top of the file.
5. **Output**: It writes the changes to a new file (e.g. `login_pdo.php`), keeping your original `login.php` safe and untouched.

---

## 6. Hands-On Lab: Refactoring a Legacy File

Let's run a simulation of refactoring a legacy PHP file.

### Step A: Write a Dummy Legacy File
Create a new file named `student_legacy.php` in your workspace with this legacy content:

```php
<?php
// student_legacy.php - Legacy procedural file
$db = mysql_connect("localhost", "admin", "password");
mysql_select_db("university", $db);

$search = $_GET['q'];
$query = mysql_query("SELECT `id`, `name`, IFNULL(`grade`, 'N/A') FROM `grades` WHERE `course` = '$search' LIMIT 0, 5");

while ($row = mysql_fetch_assoc($query)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . "<br>";
}
?>
```

### Step B: Run the Python Refactor Script
Open your terminal and run the migration script targeting the file:

```bash
python convert_mysql_to_pdo.py
```

### Step C: Inspect the Refactored File
Open the newly created `student_legacy_pdo.php` file. It should look like this:

```php
<?php
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=newsalary", "postgres", "your_password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<?php
// student_legacy.php - Legacy procedural file
// Removed old mysql_connect() – handled by PDO
// Removed old mysql_select_db() – handled by PDO

$search = $_GET['q'];
$query = $stmt = $pdo->query("SELECT `id`, `name`, IFNULL(`grade`, 'N/A') FROM `grades` WHERE `course` = '$search' LIMIT 0, 5");

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . "<br>";
}
?>
```

### Step D: Convert the SQL Syntax
Now use the `MysqlToPdoConverter` PHP class to make the SQL query compatible with PostgreSQL:

```php
require_once 'MysqlToPdoConverter.php';
$converter = new MysqlToPdoConverter();

$mysqlSQL = "SELECT `id`, `name`, IFNULL(`grade`, 'N/A') FROM `grades` WHERE `course` = ? LIMIT 0, 5";
$result = $converter->convert($mysqlSQL, ['Computer Science']);

echo "PostgreSQL SQL:\n" . $result['query'];
// Output: SELECT "id", "name", COALESCE("grade", 'N/A') FROM "grades" WHERE "course" = :param0 LIMIT 5 OFFSET 0
```

---

## 💡 Key Takeaways
1. **Never concatenate user input** directly into query strings (use prepared statements to block SQL Injection).
2. **PDO abstraction** enables switching database drivers without completely rewriting your database query functions.
3. Keep track of differences in database **dialects** (PostgreSQL uses double quotes for identifiers and strict standard syntax).
