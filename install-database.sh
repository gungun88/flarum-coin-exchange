#!/bin/bash

# Coin Exchange Extension - Database Setup Script
# This script creates the required database table for the extension

echo "======================================"
echo "Coin Exchange - Database Setup"
echo "======================================"
echo ""

# Check if we're in Flarum directory
if [ ! -f "flarum" ]; then
    echo "❌ Error: Not in Flarum directory"
    echo "Please run this script from your Flarum root directory"
    exit 1
fi

echo "📦 Creating database table..."
echo ""

# Create the table using PHP
php -r "
\$config = include 'config.php';

try {
    \$pdo = new PDO(
        'mysql:host='.\$config['database']['host'].';dbname='.\$config['database']['database'],
        \$config['database']['username'],
        \$config['database']['password']
    );

    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    echo \"✅ Table 'coin_exchange_records' created successfully!\n\n\";

    // Verify table structure
    \$stmt = \$pdo->query(\"DESCRIBE coin_exchange_records\");
    \$columns = \$stmt->fetchAll(PDO::FETCH_COLUMN);
    echo \"📋 Table structure:\n\";
    foreach (\$columns as \$column) {
        echo \"   - \$column\n\";
    }
    echo \"\n\";

    echo \"✅ Database setup completed successfully!\n\";
    exit(0);

} catch (PDOException \$e) {
    echo \"❌ Error: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

RESULT=$?

if [ $RESULT -eq 0 ]; then
    echo ""
    echo "======================================"
    echo "Next steps:"
    echo "======================================"
    echo "1. Clear Flarum cache:"
    echo "   php flarum cache:clear"
    echo ""
    echo "2. Enable the extension in Admin Panel:"
    echo "   https://your-forum.com/admin"
    echo ""
    echo "3. Configure extension settings"
    echo ""
else
    echo ""
    echo "❌ Database setup failed!"
    echo "Please check the error message above"
    exit 1
fi
