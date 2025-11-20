# 商家平台 API 开发指南

## 概述

如果你还没有独立的商家平台，需要先开发一个 API 接口来接收 Flarum 的硬币兑换请求。

---

## 方案选择

### 方案 1: 独立商家平台（推荐）

如果你有独立的电商系统、会员系统等：

**优点：**
- ✅ 积分系统独立，不依赖论坛
- ✅ 可以有自己的积分规则
- ✅ 用户数据分离

**你需要：**
1. 开发 API 接口（见下方示例）
2. 生成 API 密钥
3. 配置到 Flarum 扩展

---

### 方案 2: Flarum 内部积分（简化版）

如果你只想在 Flarum 内部使用积分：

**说明：** 可以修改扩展，直接在 Flarum 内部转换积分类型，不需要外部 API。

**例如：**
- 用户有 `money` 字段（硬币）
- 用户有 `points` 字段（积分）
- 兑换时：`money -= 100`, `points += 10`

**是否需要这个方案？** 如果需要，我可以帮你修改代码。

---

## API 开发指南（方案 1）

### 步骤 1: 创建 API 端点

在你的商家平台创建一个接口文件，例如：

```
https://your-shop.com/api/exchange/coins-to-points
```

### 步骤 2: 实现接口逻辑

参考 [`examples/merchant-api-example.php`](examples/merchant-api-example.php) 中的完整示例代码。

**核心功能：**
1. ✅ 接收 POST 请求
2. ✅ 验证签名（防止伪造请求）
3. ✅ 验证时间戳（防止重放攻击）
4. ✅ 检查用户是否存在
5. ✅ 检查交易ID唯一性
6. ✅ 增加用户积分
7. ✅ 记录兑换历史
8. ✅ 返回结果

### 步骤 3: 生成 API 密钥

使用以下方法生成 64 位 SHA256 密钥：

**方法 1: 命令行（Linux/Mac）**
```bash
echo -n "your-random-secret-string-$(date +%s)" | sha256sum
```

**方法 2: PHP**
```php
echo hash('sha256', 'your-random-secret-string-' . time());
```

**方法 3: 在线工具**
访问: https://emn178.github.io/online-tools/sha256.html
输入任意字符串，生成 SHA256 哈希

**示例密钥：**
```
E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45
```

---

## 数据库表结构（商家平台）

### 用户表（如果还没有）
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100),
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 兑换记录表（推荐创建）
```sql
CREATE TABLE coin_exchange_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    forum_user_id INT NOT NULL COMMENT 'Flarum 用户ID',
    forum_transaction_id VARCHAR(100) UNIQUE NOT NULL COMMENT '交易ID',
    user_email VARCHAR(255) NOT NULL COMMENT '用户邮箱',
    coin_amount INT NOT NULL COMMENT '硬币数量',
    points_amount INT NOT NULL COMMENT '积分数量',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_transaction (forum_transaction_id)
);
```

---

## 签名验证算法详解

### Flarum 端生成签名

```php
// 在 ExchangeController.php 中
protected function generateSignature($data, $secret)
{
    // 1. 按 key 排序
    ksort($data);

    // 2. 拼接字符串 (key1=value1&key2=value2)
    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[] = "$key=$value";
    }
    $signString = implode('&', $pairs);

    // 3. 添加密钥
    $stringToSign = $signString . '&secret=' . $secret;

    // 4. SHA256 哈希
    return hash('sha256', $stringToSign);
}
```

### 示例计算过程

**原始数据：**
```json
{
    "coin_amount": 100,
    "forum_user_id": "123",
    "forum_transaction_id": "tx_20250112_123_5678",
    "timestamp": 1641980000000,
    "user_email": "user@example.com"
}
```

**步骤 1: 按 key 排序**
```
coin_amount
forum_transaction_id
forum_user_id
timestamp
user_email
```

**步骤 2: 拼接字符串**
```
coin_amount=100&forum_transaction_id=tx_20250112_123_5678&forum_user_id=123&timestamp=1641980000000&user_email=user@example.com
```

**步骤 3: 添加密钥**
```
coin_amount=100&forum_transaction_id=tx_20250112_123_5678&forum_user_id=123&timestamp=1641980000000&user_email=user@example.com&secret=E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45
```

**步骤 4: SHA256 哈希**
```
得到签名: a1b2c3d4e5f6...（64位字符串）
```

**商家平台用相同算法计算，如果结果一致，则验证通过。**

---

## API 测试

### 使用 cURL 测试

```bash
# 1. 准备测试数据
cat > test_data.json <<EOF
{
    "forum_user_id": "123",
    "forum_transaction_id": "tx_test_$(date +%s)",
    "user_email": "test@example.com",
    "coin_amount": 100,
    "timestamp": $(date +%s)000
}
EOF

# 2. 生成签名（需要用你的密钥）
# 这里需要用 PHP 或其他语言生成

# 3. 发送请求
curl -X POST https://your-shop.com/api/exchange/coins-to-points \
  -H "Content-Type: application/json" \
  -H "X-Signature: 你生成的签名" \
  -d @test_data.json
```

---

## 配置到 Flarum

### 步骤 1: 登录 Flarum 管理后台

访问: `https://your-forum.com/admin`

### 步骤 2: 进入扩展设置

1. 点击左侧菜单 **扩展**
2. 找到 **Coin Exchange** 扩展
3. 点击 **设置** 按钮

### 步骤 3: 填写配置

- **启用功能**: ✅ 勾选
- **API 地址**: `https://your-shop.com/api/exchange/coins-to-points`
- **API 密钥**: `E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45`
- **每日限额**: `1000`（或你想设置的值）

### 步骤 4: 保存并测试

1. 点击 **保存设置**
2. 前往论坛前台
3. 登录用户
4. 点击用户菜单 > **Exchange Points**
5. 输入 10 或 20 硬币测试

---

## 常见问题

### Q1: 我没有独立的商家平台，可以用这个扩展吗？

**A:** 有两个选择：

1. **简化版**：修改扩展，直接在 Flarum 内部转换积分，不需要外部 API
2. **开发版**：创建一个简单的 API 接口（可以是一个单独的 PHP 文件）

### Q2: API 必须用 PHP 开发吗？

**A:** 不是！可以用任何语言：
- ✅ PHP
- ✅ Node.js
- ✅ Python
- ✅ Java
- ✅ Go

只要实现相同的签名验证算法即可。

### Q3: 可以把 API 放在 Flarum 同一个服务器吗？

**A:** 可以！例如：
- Flarum: `https://forum.example.com`
- API: `https://forum.example.com/shop-api/exchange`

或者：
- Flarum: `https://example.com`
- API: `https://example.com/api/exchange/coins-to-points`

### Q4: 如何确保安全性？

**A:** 扩展已实现：
- ✅ SHA256 签名验证（防止伪造）
- ✅ 时间戳验证（防止重放攻击，5分钟有效期）
- ✅ 交易ID唯一性检查
- ✅ HTTPS 传输（建议）

### Q5: 用户必须在两个平台都注册吗？

**A:** 是的。扩展通过 **邮箱** 关联用户：
- Flarum 用户邮箱：`user@example.com`
- 商家平台用户邮箱：`user@example.com`（必须相同）

---

## 快速开始（无独立平台）

如果你只想在 Flarum 内部使用，不需要外部平台：

**告诉我你的需求：**
1. 积分字段名称是什么？（例如 `points`）
2. 是否需要兑换记录？
3. 是否需要每日限额？

我可以帮你修改扩展，移除 API 调用，直接在 Flarum 内部转换积分。

---

## 参考文件

- **完整 API 示例**: [`examples/merchant-api-example.php`](examples/merchant-api-example.php)
- **部署指南**: [`DEPLOYMENT.md`](DEPLOYMENT.md)
- **测试指南**: [`TESTING.md`](TESTING.md)

---

需要我帮你：
1. 开发一个简单的 API 示例吗？
2. 或者修改扩展为 Flarum 内部积分转换（不需要外部 API）？

请告诉我你的需求！
