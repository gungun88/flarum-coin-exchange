# 验证货币字段配置

## 你的货币插件

**插件名称**: `antoinefr/flarum-ext-money`

## 验证步骤

### 方法 1: 查看数据库 (推荐)

1. 登录你的 Flarum 数据库 (MySQL/MariaDB)
2. 运行以下 SQL 查询:

```sql
-- 查看 users 表结构
DESCRIBE users;

-- 或者
SHOW COLUMNS FROM users LIKE '%money%';
```

3. 查找包含 "money" 或 "coin" 相关的字段名

### 方法 2: 查看用户资料

1. 登录 Flarum 论坛
2. 查看任意用户的资料页面
3. 查看硬币余额显示的属性名

### 方法 3: 检查插件代码

查看插件的数据库迁移文件,通常在:
```
vendor/antoinefr/flarum-ext-money/migrations/
```

## 常见字段名

`antoinefr/flarum-ext-money` 插件通常使用:
- ✅ **`money`** (最常见,默认字段名)
- 或 `balance`
- 或 `coins`

## 当前配置

我们的插件目前配置使用的字段名是: **`money`**

### PHP 后端 (3处)
- 文件: `src/Controller/ExchangeController.php`
  - 第 69 行: `$userMoney = $actor->money ?? 0;`
  - 第 86 行: `$actor->money = $userMoney - $coinAmount;`
  - 第 98 行: `'remainingCoins' => $actor->money,`

### JavaScript 前端 (2处)
- 文件: `js/src/forum/components/CoinExchangeModal.js`
  - 第 19 行: `this.userMoney = app.session.user.data.attributes.money || 0;`
  - 第 149 行: `app.session.user.data.attributes.money = response.data.remainingCoins;`

## 如果字段名不是 `money`

如果你的字段名不同,请告诉我正确的字段名,我会修改以下文件:

1. `src/Controller/ExchangeController.php` (后端 PHP)
2. `js/src/forum/components/CoinExchangeModal.js` (前端 JavaScript)

然后重新构建前端:
```bash
cd C:\Users\ATZ\Desktop\flarum-coin-exchange
npm run build
```

## 验证结果

请运行上述 SQL 查询后,告诉我:
- [ ] 字段名是 `money` (无需修改)
- [ ] 字段名是其他: __________ (需要修改)

## 参考文档

- antoinefr/flarum-ext-money GitHub: https://github.com/AntoineFr/flarum-ext-money
- Flarum 扩展文档: https://docs.flarum.org/extend/
