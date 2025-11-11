#!/bin/bash

# Flarum Coin Exchange Extension - Installation Check Script
# Run this script after installation to verify everything is set up correctly

echo "======================================"
echo "Coin Exchange Extension - Health Check"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in Flarum directory
if [ ! -f "flarum" ]; then
    echo -e "${RED}❌ Error: Not in Flarum directory${NC}"
    echo "Please run this script from your Flarum root directory"
    exit 1
fi

echo "📋 Checking extension installation..."
echo ""

# 1. Check if extension is installed
echo "1. Extension Package:"
if composer show doingfb/flarum-coin-exchange &>/dev/null; then
    VERSION=$(composer show doingfb/flarum-coin-exchange | grep versions | awk '{print $3}')
    echo -e "   ${GREEN}✅ Installed (version: $VERSION)${NC}"
else
    echo -e "   ${RED}❌ Not installed${NC}"
    echo "   Run: composer require doingfb/flarum-coin-exchange"
    exit 1
fi
echo ""

# 2. Check database table
echo "2. Database Table:"
TABLE_CHECK=$(php flarum info 2>&1)
if command -v mysql &>/dev/null; then
    # Get database credentials from config.php
    DB_NAME=$(php -r "include 'config.php'; echo \$config['database']['database'];")
    DB_USER=$(php -r "include 'config.php'; echo \$config['database']['username'];")
    DB_PASS=$(php -r "include 'config.php'; echo \$config['database']['password'];")
    DB_HOST=$(php -r "include 'config.php'; echo \$config['database']['host'];")

    TABLE_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'coin_exchange_records';" 2>/dev/null | grep coin_exchange_records)

    if [ -n "$TABLE_EXISTS" ]; then
        RECORD_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM coin_exchange_records;" 2>/dev/null | tail -n 1)
        echo -e "   ${GREEN}✅ Table exists ($RECORD_COUNT records)${NC}"
    else
        echo -e "   ${RED}❌ Table does not exist${NC}"
        echo "   Run: php flarum migrate"
    fi
else
    echo -e "   ${YELLOW}⚠️  Cannot check (mysql command not found)${NC}"
    echo "   Please verify manually: SHOW TABLES LIKE 'coin_exchange_records';"
fi
echo ""

# 3. Check extension enabled
echo "3. Extension Status:"
ENABLED=$(php flarum info 2>&1 | grep -i "coin")
if echo "$ENABLED" | grep -q "doingfb-coin-exchange"; then
    echo -e "   ${GREEN}✅ Extension is enabled${NC}"
else
    echo -e "   ${YELLOW}⚠️  Extension may not be enabled${NC}"
    echo "   Enable it in Admin Panel > Extensions"
fi
echo ""

# 4. Check settings
echo "4. Extension Settings:"
if [ -f "config.php" ]; then
    echo "   Checking configuration..."
    # This would require database access to check settings table
    echo -e "   ${YELLOW}⚠️  Please verify in Admin Panel:${NC}"
    echo "      - API URL is configured"
    echo "      - API Secret is set"
    echo "      - Feature is enabled"
else
    echo -e "   ${RED}❌ config.php not found${NC}"
fi
echo ""

# 5. Check logs
echo "5. Recent Logs:"
if [ -f "storage/logs/flarum.log" ]; then
    COIN_LOGS=$(grep -i "coin exchange" storage/logs/flarum.log 2>/dev/null | tail -n 5)
    if [ -n "$COIN_LOGS" ]; then
        echo -e "   ${GREEN}✅ Found exchange logs:${NC}"
        echo "$COIN_LOGS" | while read line; do
            echo "      $line"
        done
    else
        echo -e "   ${YELLOW}⚠️  No exchange logs found (normal if no exchanges yet)${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠️  Log file not found${NC}"
fi
echo ""

# Summary
echo "======================================"
echo "Summary"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Enable extension in Admin Panel (if not enabled)"
echo "2. Configure API URL and Secret in extension settings"
echo "3. Set daily limit (default: 1000 coins)"
echo "4. Test exchange with a small amount (10-20 coins)"
echo ""
echo "For detailed deployment guide, see:"
echo "  vendor/doingfb/flarum-coin-exchange/DEPLOYMENT.md"
echo ""
echo "To monitor exchanges:"
echo "  tail -f storage/logs/flarum.log | grep 'Coin exchange'"
echo ""
