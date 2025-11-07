import os
import re

# Folder containing your PHP files
SOURCE_DIR = r"C:\xampp\htdocs\newpayroll"

# Regex patterns for old MySQL functions
patterns = {
    r"mysql_connect\s*\(.*?\)\s*;?": "// Removed old mysql_connect() – handled by PDO\n",
    r"mysql_select_db\s*\(.*?\)\s*;?": "// Removed old mysql_select_db() – handled by PDO\n",
    r"mysql_query\s*\((.*?)\)": r"$stmt = $pdo->query(\1)",
    r"mysql_fetch_assoc\s*\((.*?)\)": r"\1->fetch(PDO::FETCH_ASSOC)",
    r"mysql_fetch_array\s*\((.*?)\)": r"\1->fetch(PDO::FETCH_BOTH)",
    r"mysql_fetch_row\s*\((.*?)\)": r"\1->fetch(PDO::FETCH_NUM)",
    r"mysql_num_rows\s*\((.*?)\)": r"\1->rowCount()",
    r"mysql_error\s*\(\)": r"$pdo->errorInfo()",
}

# PDO Connection snippet to prepend
pdo_header = """<?php
try {
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=newsalary", "postgres", "your_password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
"""

def convert_php_file(file_path):
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Apply regex replacements
    for old, new in patterns.items():
        content = re.sub(old, new, content, flags=re.IGNORECASE)

    # Optional: insert PDO header if file uses old mysql_ functions
    if any(func in content for func in ["mysql_query", "mysql_fetch", "mysql_num_rows"]):
        content = pdo_header + "\n" + content

    # Write updated file
    output_path = file_path.replace(".php", "_pdo.php")
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"✅ Converted: {os.path.basename(file_path)} → {os.path.basename(output_path)}")

def scan_directory(path):
    for root, _, files in os.walk(path):
        for file in files:
            if file.endswith(".php"):
                convert_php_file(os.path.join(root, file))

if __name__ == "__main__":
    print("🚀 Starting MySQL → PDO PostgreSQL migration...")
    scan_directory(SOURCE_DIR)
    print("✅ All done! Converted files saved with '_pdo.php' suffix.")
