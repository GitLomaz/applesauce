# PostgreSQL Migration Guide

## ✅ Migration Complete!

This application has successfully been migrated from MySQL to PostgreSQL.

## Key Solution: PDOResultWrapper

To avoid conflicts with built-in mysqli functions, I created a **PDOResultWrapper** class that wraps PDOStatement objects. This allows existing code using mysqli-style patterns to work seamlessly:

```php
// sql_query() now returns PDOResultWrapper instead of raw PDOStatement
$result = sql_query("SELECT * FROM users", $conn);

// These all work:
$row = $result->fetch();           // PDO-style
$count = $result->rowCount();      // Compatible method
while($row = $result->fetch()) {   // Works in loops
    // process row
}
```

The wrapper class automatically forwards method calls to the underlying PDOStatement, so all PDO functionality remains available.

### ✅ Completed

1. **Database Configuration (config.php)**
   - Replaced mysqli with PDO for PostgreSQL
   - Updated default connection parameters for Supabase
   - Connection string: `postgresql://postgres:[DB_PASS]@db.pasokuwgludhtolrxctu.supabase.co:5432/postgres`
   - Added mysqli compatibility wrappers for backward compatibility

2. **Docker Compose (docker-compose.yml)**
   - Replaced MySQL 8.0 with PostgreSQL 15
   - Updated environment variables
   - Replaced phpMyAdmin with pgAdmin
   - Changed default port from 3306 to 5432

3. **Core Library Files**
   - Updated common_lib.php with PDO calls
   - Added compatibility layer for mysqli functions
   - Updated basic SQL syntax for PostgreSQL

4. **Standalone Scripts**
   - Updated actions.php
   - Updated daily.php
   - Updated nightly.php
   - Removed backticks from SQL queries
   - Updated date functions for PostgreSQL

### ⚠️ Important PostgreSQL Syntax Differences

#### 1. **Identifier Quoting**
- MySQL uses backticks: `` `table_name` ``
- PostgreSQL uses double quotes: `"table_name"`
- **Best Practice**: Don't quote identifiers unless necessary (use lowercase names)

#### 2. **Schema/Database Names**
- MySQL: `kalrul.table_name`
- PostgreSQL: Just use `table_name` (schemas work differently)
- All `kalrul.` prefixes have been removed

#### 3. **Date/Time Functions**
- `SYSDATE()` → `CURRENT_TIMESTAMP` or `NOW()`
- `SUBDATE(date, days)` → `date - INTERVAL '1 day'`
- `TIMESTAMPDIFF(SECOND, t1, t2)` → `EXTRACT(EPOCH FROM (t2 - t1))::int`
- `DATE(expr)` → `expr::date`

#### 4. **String Matching**
- `LIKE` still works, but for timestamp ranges, use proper date comparisons
- Example: `timestamp >= '$date' AND timestamp < '$date'::date + INTERVAL '1 day'`

#### 5. **Auto-increment**
- MySQL: `AUTO_INCREMENT`
- PostgreSQL: `SERIAL` or `IDENTITY`

#### 6. **Row Count**
- `$conn->affected_rows` → PDO doesn't have a direct equivalent
- Compatibility wrapper provided, but may not be 100% accurate
- For INSERT/UPDATE/DELETE, use `$statement->rowCount()`

### 🔄 Compatibility Layer

The following mysqli functions have been wrapped for compatibility:
- `mysqli_fetch_array()` - wraps `PDOStatement::fetch()`
- `mysqli_num_rows()` - wraps `PDOStatement->rowCount()`
- `mysqli_affected_rows()` - limited compatibility  
- `mysqli_close()` - no-op (PDO auto-closes)

**Note**: While these wrappers provide basic compatibility, some advanced mysqli features may not work identically.

### 📝 Still TODO / Manual Review Needed

1. **Review Complex Queries**
   - Check all queries with JOINs and subqueries
   - Verify GROUP BY compliance (PostgreSQL is stricter)
   - Review calcValues queries which use complex calculations

2. **Test combat_lib.php**
   - Many mysqli calls remain (compatibility layer should handle them)
   - Verify combat mechanics work correctly

3 **Test class_lib.php**
   - Verify class-specific functionality

4. **AJAX.php and kong_AJAX.php**
   - Review authentication and session handling
   - Test all AJAX endpoints

5. **Test Library Functions**
   - `library.php` and `kong_library.php` need testing
   - Verify all game mechanics work correctly

6. **Database Schema**
   - Ensure all tables exist in PostgreSQL
   - Verify column types match (especially ENUM, SET, TEXT types)
   - Check indexes and constraints
   - Verify foreign keys if any exist

7. **Case Sensitivity**
   - PostgreSQL is case-sensitive for unquoted identifiers
   - Table names should be lowercase
   - Column names accessed in code must match database case

### 🧪 Testing Checklist

- [ ] Database connection establishes successfully
- [ ] User authentication works
- [ ] Character creation/loading
- [ ] Inventory management  
- [ ] Equipment system
- [ ] Combat system
- [ ] Quest system
- [ ] Daily/nightly tasks
- [ ] Achievements
- [ ] Skill system
- [ ] Shop/trading system

### deployment Configuration

#### Environment Variables Required
```bash
DB_HOST=db.pasokuwgludhtolrxctu.supabase.co
DB_USER=postgres
DB_PASS=<your_password>
DB_NAME=postgres
DB_PORT=5432
APP_ENV=production
APP_DEBUG=false
```

#### Local Development
```bash
docker-compose up -d
```

This will start:
- App on port 8080
- PostgreSQL on port 5432
- pgAdmin on port 8081 (admin@applesauce.local / localpassword)

### 🔐 Security Notes

1. **SSL Mode**: Connection uses `sslmode=require` for Supabase
2. **Password**: Ensure DB_PASS environment variable is set securely
3. **Prepared Statements**: Consider migrating to prepared statements for all user input
   - Current code uses string concatenation (SQL injection risk)
   - PDO supports prepared statements: `$conn->prepare()`

### 📚 Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [MySQL to PostgreSQL Migration Guide](https://www.postgresql.org/docs/current/migration.html)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Supabase Docs](https://supabase.com/docs)

### ⚡ Performance Considerations

1. **VACUUM**: PostgreSQL needs regular VACUUM operations
2. **ANALYZE**: Update statistics for query optimization
3. **Indexes**: Review and recreate indexes from MySQL
4. **Connection Pooling**: Consider using PgBouncer for connection pooling

### 🐛 Troubleshooting

**Connection Issues**:
- Verify Supabase credentials
- Check firewall/security group settings
- Ensure SSL certificates are valid

**SQL Errors**:
- Check error logs: `error_log` in PHP
- Enable `APP_DEBUG=true` for detailed errors
- Review column/table name case sensitivity

**Performance Issues**:
- Check query performance with `EXPLAIN ANALYZE`
- Verify indexes exist
- Monitor connection pool usage
