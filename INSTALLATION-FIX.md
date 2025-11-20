# Installation Fix for Flarum 1.x

If you encounter the error:
```
Fatal error: Uncaught Error: Class "Flarum\Extend\Migration" not found
```

This means your Flarum version doesn't support the `Extend\Migration` API. Follow these steps:

## Quick Fix

### Step 1: Run the database setup script

```bash
cd /path/to/your/flarum
bash vendor/doingfb/flarum-coin-exchange/install-database.sh
```

### Step 2: Clear cache

```bash
php flarum cache:clear
php flarum assets:publish
```

### Step 3: Enable extension

Go to Admin Panel > Extensions > Enable "Coin Exchange"

---

## Manual Installation (if script doesn't work)

### 1. Create database table manually

```bash
cd /path/to/your/flarum

php -r "
\$config = include 'config.php';
\$pdo = new PDO(
    'mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['database'],
    \$config['database']['username'],
    \$config['database']['password']
);

\$sql = \"
CREATE TABLE IF NOT EXISTS coin_exchange_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    coin_amount INT NOT NULL,
    points_amount INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    merchant_response VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
\";

\$pdo->exec(\$sql);
echo \"✅ Table created successfully!\n\";
"
```

### 2. Verify table creation

```bash
php -r "
\$config = include 'config.php';
\$pdo = new PDO(
    'mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['database'],
    \$config['database']['username'],
    \$config['database']['password']
);
\$stmt = \$pdo->query(\"SHOW TABLES LIKE 'coin_exchange_records'\");
\$result = \$stmt->fetch();
echo \$result ? '✅ Table exists!' : '❌ Table not found!';
echo PHP_EOL;
"
```

### 3. Clear cache and enable

```bash
php flarum cache:clear
php flarum assets:publish
```

Then enable the extension in Admin Panel.

---

## Why This Happens

The `Flarum\Extend\Migration` class was introduced in Flarum v1.8+. If you're using an earlier version (v1.0-1.7), you need to create the database table manually.

## Compatibility

- ✅ Flarum 1.0+
- ✅ PHP 7.4+
- ✅ MySQL 5.7+ / MariaDB 10.2+

## Need Help?

- **GitHub Issues**: https://github.com/gungun88/flarum-coin-exchange/issues
- **Documentation**: See DEPLOYMENT.md for detailed guide
