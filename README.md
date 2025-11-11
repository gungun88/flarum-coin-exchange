# Flarum Coin Exchange Extension

Exchange Flarum forum coins for merchant platform points.

## ✅ Production Ready

**Version 1.0.1** is ready for production use with:
- Database transaction protection
- Complete daily limit checking
- Error logging and monitoring
- Automatic rollback on failures

**See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment guide.**

---

## Features

- ✅ One-click exchange of forum coins for merchant platform points
- ✅ Exchange rate: 1 point = 10 coins
- ✅ Daily exchange limit control (default 1000 coins/day)
- ✅ Real-time balance display
- ✅ Secure SHA256 signature verification
- ✅ Anti-replay attack mechanism
- ✅ User-friendly interface
- ✅ Bilingual support (English & Chinese)
- ✅ **Database transaction protection**
- ✅ **Complete audit logging**
- ✅ **Automatic rollback on failures**

---

## Installation

### Quick Start

```bash
composer require doingfb/flarum-coin-exchange
php flarum cache:clear
php flarum migrate
```

Then enable the extension in admin panel and configure settings.

**⚠️ Important:** Before deploying to production, read [DEPLOYMENT.md](DEPLOYMENT.md) for:
- Database migration verification
- Coin field name confirmation
- Security checklist
- Monitoring setup

### Manual Installation

1. Copy this extension folder to Flarum's `extensions` directory
2. Enable the extension in Flarum admin panel
3. Run database migrations

---

## Configuration

### 1. Flarum Admin Panel

Go to **Admin > Extensions > Coin Exchange** and configure:

- **Enable Feature**: Turn on coin exchange functionality
- **API URL**: Merchant platform API endpoint
  ```
  https://your-merchant-platform.com/api/exchange/coins-to-points
  ```
- **API Secret**: Secret key matching merchant platform (64-char SHA256 string)
  ```
  E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45
  ```
- **Daily Limit**: Maximum coins per user per day (default: 1000)

### 2. Merchant Platform Requirements

The merchant platform must have:
- ✅ Deployed `/api/exchange/coins-to-points` endpoint
- ✅ Configured same API secret key
- ✅ Users registered with same email addresses

---

## Usage

### 用户端

1. 点击用户菜单（头像下拉菜单）
2. 选择 **兑换积分**
3. 输入要兑换的硬币数量（最少 10 硬币，必须是 10 的倍数）
4. 点击 **立即兑换**
5. 等待兑换完成

### 兑换规则

- **兑换比例**：1 积分 = 10 硬币
- **最小兑换**：10 硬币（= 1 积分）
- **数量要求**：必须是 10 的倍数
- **每日限额**：1000 硬币/用户/天（= 100 积分/天）
- **账户关联**：需在两个平台使用相同邮箱

## 技术说明

### API 签名算法

使用 SHA256 算法生成签名：

```php
// 1. 按 key 排序
ksort($data);

// 2. 拼接字符串
$signString = implode('&', array_map(fn($k, $v) => "$k=$v", array_keys($data), $data));

// 3. 添加密钥
$stringToSign = $signString . '&secret=' . $apiSecret;

// 4. SHA256 哈希
$signature = hash('sha256', $stringToSign);
```

### 安全机制

1. **签名验证**：每个请求都需要携带有效签名
2. **时间戳验证**：请求有效期 5 分钟
3. **交易ID唯一性**：防止重复提交
4. **每日限额**：防止滥用
5. **余额检查**：确保用户有足够硬币

## 开发说明

### 构建前端资源

```bash
# 安装依赖
npm install

# 开发模式（自动监听）
npm run dev

# 生产构建
npm run build
```

### 项目结构

```
flarum-coin-exchange/
├── composer.json           # PHP 包配置
├── extend.php             # 扩展入口
├── package.json           # npm 配置
├── webpack.config.js      # Webpack 配置
├── src/
│   └── Controller/
│       └── ExchangeController.php   # 兑换逻辑
├── js/
│   └── src/
│       ├── admin/
│       │   └── index.js           # 管理后台
│       └── forum/
│           ├── index.js           # 论坛前端
│           └── components/
│               └── CoinExchangeModal.js  # 兑换弹窗
├── less/
│   ├── admin.less         # 管理后台样式
│   └── forum.less         # 论坛样式
├── locale/
│   ├── en.yml            # 英文语言包
│   └── zh-CN.yml         # 中文语言包
└── README.md
```

## 故障排查

### 兑换失败

**问题：提示"API 配置不完整"**
- 检查管理后台是否已配置 API 地址和密钥

**问题：提示"用户不存在"**
- 确保用户已在商家平台注册
- 确保两个平台使用相同的邮箱地址

**问题：提示"签名验证失败"**
- 检查 API 密钥是否与商家平台一致
- 确保密钥复制完整，没有多余空格

**问题：提示"硬币余额不足"**
- 检查用户当前硬币余额
- 确保兑换数量不超过余额

### 时间戳问题

**问题：提示"时间戳过期"**
- 检查服务器时间是否正确
- 时间差超过 5 分钟会导致验证失败

## 依赖要求

- Flarum ^1.0.0
- PHP ^7.4 | ^8.0
- cURL extension
- 商家平台 API 已部署

## 许可证

MIT License

## 支持

- 📧 Email: info@doingfb.com
- 🌐 Website: https://doingfb.com
- 📖 商家平台 API 文档：见商家平台项目的 `docs/coin-exchange-api.md`

## 更新日志

### v1.0.0 (2025-01-10)

- 🎉 首次发布
- ✅ 基础兑换功能
- ✅ 管理后台配置
- ✅ 中英文支持
- ✅ 安全签名验证
- ✅ 每日限额控制
