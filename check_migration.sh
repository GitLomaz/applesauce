#!/bin/bash
# PostgreSQL Migration Helper Script
# This script helps identify potential MySQL-specific code that needs updating

echo "=== PostgreSQL Migration Helper ==="
echo ""

echo "Checking for MySQL-specific syntax..."
echo ""

echo "1. Backticks in SQL queries:"
grep -rn '`' --include="*.php" . | grep -v ".git" | grep -v "POSTGRES_MIGRATION" | head -20
echo ""

echo "2. MySQL date functions:"
grep -rn "SYSDATE\|SUBDATE\|TIMESTAMPDIFF\|CURDATE\|NOW()" --include="*.php" . | grep -v ".git" | head -10
echo ""

echo "3. Schema prefixes (kalrul.):"
grep -rn "kalrul\." --include="*.php" . | grep -v ".git" | head -10
echo ""

echo "4. MySQL-specific functions:"
grep -rn "IFNULL\|GROUP_CONCAT\|CONCAT_WS" --include="*.php" . | grep -v ".git" | head -10
echo ""

echo "5. LIMIT with OFFSET syntax:"
grep -rn "LIMIT.*," --include="*.php" . | grep -v ".git" | head -10
echo ""

echo "=== Recommendations ==="
echo ""
echo "1. Replace backticks with double quotes or remove them"
echo "2. Update date functions to PostgreSQL equivalents"
echo "3. Remove schema prefixes (kalrul.)"
echo "4. Convert MySQL functions to PostgreSQL equivalents:"
echo "   - IFNULL() → COALESCE()"
echo "   - GROUP_CONCAT() → STRING_AGG()"
echo "   - CONCAT_WS() → CONCAT() with ||"
echo "5. LIMIT syntax: 'LIMIT 10, 20' → 'LIMIT 20 OFFSET 10'"
echo ""

echo "Run this script periodically to check progress."
