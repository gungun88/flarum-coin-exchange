# 生产环境部署指南

## 重要提醒

**在生产环境部署前，请务必完成以下检查：**

### 1. 硬币字段确认 ⚠️

本扩展假设用户硬币存储在 `users` 表的 `money` 字段中。

**请确认你使用的货币/积分扩展：**

- **如果使用 `antoinefr/flarum-ext-money`**：默认使用 `money` 字段 ✅
- **如果使用其他扩展**：需要修改 [ExchangeController.php:68](src/Controller/ExchangeController.php#L68) 和 [ExchangeController.php:121](src/Controller/ExchangeController.php#L121) 中的字段名

检查方法：
```bash
# 查看 users 表结构
mysql -u 用户名 -p 数据库名
DESCRIBE users;
```

如果字段名不是 `money`，请修改代码中的 `$actor->money`。

---

## 安装步骤

### 步骤 1: 安装扩展

```bash
cd /你的Flarum目录

# 使用 Composer 安装
composer require doingfb/flarum-coin-exchange
```

### 步骤 2: 运行数据库迁移

```bash
# 清除缓存
php flarum cache:clear

# 迁移会自动运行，检查是否成功
php flarum migrate:status
```

**检查数据库表是否创建成功：**
```bash
mysql -u 用户名 -p 数据库名
SHOW TABLES LIKE 'coin_exchange_records';
DESCRIBE coin_exchange_records;
```

### 步骤 3: 启用扩展

1. 登录 Flarum 管理后台
2. 进入 **扩展** 页面
3. 找到 **Coin Exchange** 扩展
4. 点击 **启用**

### 步骤 4: 配置扩展

进入扩展设置页面，配置以下信息：

- **启用功能**：勾选启用
- **API 地址**：商家平台的 API 端点
  ```
  https://your-merchant-platform.com/api/exchange/coins-to-points
  ```
- **API 密钥**：与商家平台配置一致的密钥（64位SHA256字符串）
- **每日限额**：建议设置 500-1000 硬币/天

### 步骤 5: 测试功能

**测试前准备：**
1. 确保测试用户在论坛有硬币余额
2. 确保测试用户在商家平台已注册（使用相同邮箱）
3. 确保商家平台 API 已部署并可访问

**测试流程：**
1. 以普通用户身份登录论坛
2. 点击右上角用户菜单
3. 选择 **Exchange Points**
4. 输入少量硬币（如 10 或 20）
5. 点击兑换
6. 检查是否成功

**如果失败，查看日志：**
```bash
tail -f storage/logs/flarum.log
```

---

## 数据库结构

### coin_exchange_records 表

| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | int | 用户ID（外键） |
| transaction_id | string | 交易ID（唯一） |
| coin_amount | int | 硬币数量 |
| points_amount | int | 积分数量 |
| status | string | 状态：pending/success/failed |
| error_message | text | 错误信息 |
| merchant_response | string | 商家平台响应 |
| created_at | timestamp | 创建时间 |
| completed_at | timestamp | 完成时间 |

---

## 安全机制

### 1. 数据库事务
- 所有操作在事务中执行
- API 失败时自动回滚，不会扣除硬币

### 2. 每日限额控制
- 从数据库实时查询今日已兑换数量
- 防止用户绕过限制

### 3. 完整日志记录
- 记录每次兑换的开始、成功、失败
- 便于追踪问题和审计

### 4. API 签名验证
- 使用 SHA256 签名
- 时间戳验证防止重放攻击

---

## 监控与维护

### 查看兑换记录

```sql
-- 查看所有兑换记录
SELECT * FROM coin_exchange_records ORDER BY created_at DESC LIMIT 20;

-- 查看失败记录
SELECT * FROM coin_exchange_records WHERE status = 'failed' ORDER BY created_at DESC;

-- 统计今日兑换
SELECT
    COUNT(*) as total_exchanges,
    SUM(coin_amount) as total_coins,
    SUM(points_amount) as total_points
FROM coin_exchange_records
WHERE DATE(created_at) = CURDATE() AND status = 'success';

-- 查看用户今日兑换情况
SELECT user_id, SUM(coin_amount) as today_exchanged
FROM coin_exchange_records
WHERE DATE(created_at) = CURDATE() AND status = 'success'
GROUP BY user_id
ORDER BY today_exchanged DESC;
```

### 查看日志

```bash
# 实时查看日志
tail -f storage/logs/flarum.log | grep "Coin exchange"

# 查看最近的兑换日志
grep "Coin exchange" storage/logs/flarum.log | tail -n 50

# 查看失败记录
grep "Coin exchange.*failed" storage/logs/flarum.log
```

### 性能优化

兑换记录表会不断增长，建议定期归档：

```sql
-- 归档 6 个月前的记录到备份表
CREATE TABLE coin_exchange_records_archive LIKE coin_exchange_records;

INSERT INTO coin_exchange_records_archive
SELECT * FROM coin_exchange_records
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

-- 删除已归档的记录
DELETE FROM coin_exchange_records
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

---

## 故障排查

### 问题 1: 提示"功能未启用"
- 检查管理后台是否勾选了"启用功能"
- 运行 `php flarum cache:clear`

### 问题 2: 提示"API 配置不完整"
- 检查是否配置了 API 地址和密钥
- 确保密钥是完整的 64 位字符串

### 问题 3: 提示"硬币余额不足"
- 检查用户的 `money` 字段值
- 确认字段名是否正确

### 问题 4: 提示"签名验证失败"
- 检查 API 密钥是否与商家平台一致
- 确保密钥没有多余的空格或换行

### 问题 5: 数据库表不存在
```bash
# 手动运行迁移
php flarum migrate

# 检查迁移状态
php flarum migrate:status
```

### 问题 6: 每日限额不生效
- 检查 `coin_exchange_records` 表是否存在
- 运行测试 SQL：
  ```sql
  SELECT SUM(coin_amount) FROM coin_exchange_records
  WHERE user_id = 你的用户ID
  AND status = 'success'
  AND DATE(created_at) = CURDATE();
  ```

---

## 回滚方案

如果需要卸载扩展：

```bash
cd /你的Flarum目录

# 1. 禁用扩展（在管理后台）

# 2. 卸载扩展
composer remove doingfb/flarum-coin-exchange

# 3. 备份兑换记录（可选）
mysqldump -u 用户名 -p 数据库名 coin_exchange_records > coin_exchange_records_backup.sql

# 4. 删除数据表（可选）
mysql -u 用户名 -p 数据库名
DROP TABLE coin_exchange_records;

# 5. 清除缓存
php flarum cache:clear
```

---

## 技术支持

- **GitHub Issues**: https://github.com/gungun88/flarum-coin-exchange/issues
- **Email**: noreply@github.com
- **商家平台 API 文档**: 见商家平台项目的 `docs/coin-exchange-api.md`

---

## 更新日志

### v1.0.1 (2025-01-12) - 生产环境就绪版本

**新增功能：**
- ✅ 数据库迁移支持
- ✅ 完整的每日限额检查
- ✅ 数据库事务保护
- ✅ 详细的日志记录
- ✅ 错误处理和回滚机制

**安全改进：**
- ✅ 事务保证数据一致性
- ✅ API 调用失败时不扣除硬币
- ✅ 完整的审计日志

**已知限制：**
- 假设硬币字段为 `users.money`，使用其他扩展需要修改代码

### v1.0.0 (2025-01-10)

- 🎉 首次发布
- ⚠️ 不建议用于生产环境（缺少数据库表和事务保护）
