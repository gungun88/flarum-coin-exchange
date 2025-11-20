# Testing Guide

## Pre-deployment Testing Checklist

Before deploying to production, test these scenarios:

### Setup

1. **Test Environment**
   - [ ] Fresh Flarum installation or staging environment
   - [ ] Extension installed and enabled
   - [ ] Database migration completed
   - [ ] Settings configured

2. **Test User**
   - [ ] Create test user with coins (e.g., 1000 coins)
   - [ ] User registered on merchant platform with same email
   - [ ] Merchant platform API is accessible

---

## Test Cases

### Test 1: Successful Exchange ✅

**Steps:**
1. Login as test user
2. Open user menu
3. Click "Exchange Points"
4. Enter valid amount (e.g., 100 coins)
5. Click exchange

**Expected Result:**
- ✅ Success message displayed
- ✅ Coins deducted from user balance
- ✅ Points added to merchant platform
- ✅ Record created in `coin_exchange_records` with status='success'
- ✅ Log entry: "Coin exchange completed successfully"

**Verify:**
```sql
SELECT * FROM coin_exchange_records ORDER BY id DESC LIMIT 1;
SELECT money FROM users WHERE id = YOUR_TEST_USER_ID;
```

---

### Test 2: Insufficient Balance ❌

**Steps:**
1. Try to exchange more coins than user has

**Expected Result:**
- ❌ Error: "硬币余额不足"
- ✅ No coins deducted
- ✅ No record created

---

### Test 3: Daily Limit Exceeded ❌

**Steps:**
1. Exchange coins up to daily limit (default: 1000)
2. Try to exchange more

**Expected Result:**
- ❌ Error: "超出每日限额"
- ✅ No additional exchange allowed

**Verify:**
```sql
SELECT SUM(coin_amount) FROM coin_exchange_records
WHERE user_id = YOUR_USER_ID
AND DATE(created_at) = CURDATE()
AND status = 'success';
```

---

### Test 4: Invalid Amount ❌

**Steps:**
1. Try to exchange 5 coins (less than minimum)
2. Try to exchange 15 coins (not multiple of 10)

**Expected Result:**
- ❌ Error: "最少需要兑换 10 硬币"
- ❌ Error: "硬币数量必须是 10 的倍数"

---

### Test 5: API Failure (Transaction Rollback) 🔄

**Steps:**
1. Temporarily break merchant API (change URL or secret)
2. Try to exchange coins

**Expected Result:**
- ❌ Error message from API
- ✅ Coins NOT deducted (transaction rolled back)
- ✅ Record created with status='failed'
- ✅ Log entry: "Coin exchange API failed"

**Verify:**
```sql
SELECT * FROM coin_exchange_records WHERE status = 'failed' ORDER BY id DESC LIMIT 1;
SELECT money FROM users WHERE id = YOUR_TEST_USER_ID;  -- Balance should be unchanged
```

**Then restore API settings and verify next exchange works.**

---

### Test 6: Extension Disabled ❌

**Steps:**
1. Disable extension in settings
2. Try to exchange

**Expected Result:**
- ❌ Error: "硬币兑换功能未启用"

---

### Test 7: Guest User ❌

**Steps:**
1. Logout
2. Try to access exchange (if possible)

**Expected Result:**
- ❌ Error: "请先登录" or access denied

---

### Test 8: Concurrent Exchanges 🔀

**Steps:**
1. Open two browser tabs
2. Login as same user
3. Try to exchange simultaneously

**Expected Result:**
- ✅ Both should succeed OR one should fail with proper error
- ✅ Database transaction should prevent race conditions
- ✅ Total deducted coins should match total exchanges

**Verify:**
```sql
SELECT * FROM coin_exchange_records WHERE user_id = YOUR_USER_ID ORDER BY created_at DESC;
-- Check that coin amounts add up correctly
```

---

### Test 9: Large Exchange

**Steps:**
1. Try to exchange maximum allowed (daily limit)
2. Verify handling of large numbers

**Expected Result:**
- ✅ Exchange succeeds if within limit
- ✅ Correct points calculated
- ✅ No overflow errors

---

### Test 10: Special Characters in Response

**Steps:**
1. Ensure merchant API returns special characters in messages
2. Try exchange

**Expected Result:**
- ✅ Response properly stored in database
- ✅ No SQL injection or XSS vulnerabilities

---

## Load Testing (Optional)

For production environments expecting high volume:

```bash
# Using Apache Bench
ab -n 100 -c 10 -H "Cookie: your_session_cookie" \
   -p exchange.json \
   https://your-forum.com/api/coin-exchange/convert

# exchange.json content:
# {"coinAmount": 10}
```

**Monitor:**
- Database connection pool
- Transaction deadlocks
- Response times
- Error rates

---

## Monitoring After Deployment

### Check Logs
```bash
# Real-time monitoring
tail -f storage/logs/flarum.log | grep "Coin exchange"

# Success rate
grep "Coin exchange completed successfully" storage/logs/flarum.log | wc -l
grep "Coin exchange.*failed" storage/logs/flarum.log | wc -l
```

### Database Queries
```sql
-- Daily statistics
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_exchanges,
    SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN status='success' THEN coin_amount ELSE 0 END) as total_coins
FROM coin_exchange_records
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 7;

-- Top users
SELECT user_id, COUNT(*) as exchanges, SUM(coin_amount) as total_coins
FROM coin_exchange_records
WHERE status='success'
GROUP BY user_id
ORDER BY total_coins DESC
LIMIT 10;

-- Failure analysis
SELECT error_message, COUNT(*) as count
FROM coin_exchange_records
WHERE status='failed'
GROUP BY error_message
ORDER BY count DESC;
```

---

## Rollback Test

**Before production deployment, verify rollback procedure:**

1. Note current state (user coins, exchange records count)
2. Perform test exchange
3. Disable extension
4. Uninstall extension
5. Verify data integrity

```bash
# Backup before test
mysqldump -u USER -p DATABASE coin_exchange_records > backup.sql

# After rollback
mysql -u USER -p DATABASE < backup.sql
```

---

## Sign-off Checklist

Before going to production:

- [ ] All 10 test cases passed
- [ ] Database migration successful
- [ ] Transactions work correctly (Test 5)
- [ ] Daily limits enforced (Test 3)
- [ ] Logs are being written
- [ ] Error handling works (Tests 2-7)
- [ ] Rollback procedure tested
- [ ] Monitoring queries work
- [ ] Backup strategy in place
- [ ] Team trained on troubleshooting

---

## Emergency Contacts

If issues occur in production:

1. **Check logs first**: `storage/logs/flarum.log`
2. **Check database**: Look for failed records
3. **Disable if needed**: Admin Panel > Extensions > Disable
4. **Contact support**: GitHub Issues or email

**Remember:** All exchanges use transactions, so partial failures won't cause data loss.
