# Flarum 硬币兑换插件 - 快速开始指南

## 📦 项目已创建

插件已成功创建在：`C:\Users\ATZ\Desktop\flarum-coin-exchange\`

---

## 🚀 安装步骤

### 步骤 1：构建前端资源

```bash
cd C:\Users\ATZ\Desktop\flarum-coin-exchange

# 安装依赖
npm install

# 构建前端（生产模式）
npm run build
```

### 步骤 2：安装到 Flarum

**方法 A：Composer 本地安装**

在 Flarum 根目录的 `composer.json` 中添加本地仓库：

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "C:/Users/ATZ/Desktop/flarum-coin-exchange"
    }
  ]
}
```

然后运行：

```bash
composer require doingfb/flarum-coin-exchange
```

**方法 B：直接复制（开发模式）**

```bash
# 复制插件到 Flarum 的 vendor 目录
xcopy /E /I "C:\Users\ATZ\Desktop\flarum-coin-exchange" "你的Flarum目录\vendor\doingfb\flarum-coin-exchange"
```

### 步骤 3：在 Flarum 管理后台启用

1. 登录 Flarum 管理后台
2. 进入 **扩展** 页面
3. 找到 **硬币兑换积分** 扩展
4. 点击 **启用**

---

## ⚙️ 配置说明

### 在 Flarum 管理后台配置

进入 **管理后台 > 扩展 > 硬币兑换积分设置**：

1. **启用功能**：✅ 勾选

2. **API 地址**（必填）：
   ```
   https://your-domain.com/api/exchange/coins-to-points
   ```
   - 开发环境：`http://localhost:3001/api/exchange/coins-to-points`
   - 生产环境：你的商家平台域名

3. **API 密钥**（必填）：
   ```
   E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45
   ```
   - ⚠️ 必须与商家平台配置的密钥完全一致

4. **每日限额**（可选）：
   ```
   1000
   ```
   - 默认值：1000 硬币/天
   - 可根据需要调整

5. 点击 **保存设置**

---

## 👥 用户使用说明

### 如何兑换

1. 用户点击自己的 **头像/用户名**
2. 在下拉菜单中选择 **兑换积分**
3. 弹出兑换对话框，显示：
   - 当前硬币余额
   - 兑换比例（1积分 = 10硬币）
   - 每日限额
4. 输入要兑换的硬币数量（必须 ≥10 且为 10 的倍数）
5. 点击 **立即兑换**
6. 等待兑换完成

### 兑换规则

- 兑换比例：**1 积分 = 10 硬币**
- 最小兑换：**10 硬币**
- 数量要求：必须是 **10 的倍数**
- 每日限额：**1000 硬币/天**（可配置）
- 账户要求：论坛和商家平台必须使用**相同邮箱**

---

## 🔧 开发说明

### 项目结构

```
flarum-coin-exchange/
├── composer.json                    # PHP 包配置
├── extend.php                       # 扩展入口
├── package.json                     # npm 配置
├── webpack.config.js                # Webpack 配置
│
├── src/
│   └── Controller/
│       └── ExchangeController.php   # 后端：兑换逻辑和 API 调用
│
├── js/
│   └── src/
│       ├── admin/
│       │   └── index.js             # 管理后台配置界面
│       └── forum/
│           ├── index.js             # 论坛前端入口
│           └── components/
│               └── CoinExchangeModal.js  # 兑换弹窗组件
│
├── less/
│   ├── admin.less                   # 管理后台样式
│   └── forum.less                   # 论坛样式
│
├── locale/
│   ├── en.yml                       # 英文翻译
│   └── zh-CN.yml                    # 中文翻译
│
├── README.md                        # 项目说明
├── LICENSE                          # MIT 许可证
└── .gitignore                       # Git 忽略文件
```

### 前端开发

```bash
# 开发模式（自动监听文件变化）
npm run dev

# 生产构建
npm run build
```

### 核心文件说明

- **ExchangeController.php**：处理兑换请求，调用商家平台 API
- **CoinExchangeModal.js**：用户兑换界面（弹窗）
- **index.js (admin)**：管理后台配置页面
- **zh-CN.yml / en.yml**：多语言翻译

---

## 🐛 常见问题

### 1. "API 配置不完整"

**原因：** 未配置 API 地址或密钥

**解决：**
- 检查管理后台是否已填写 API 地址和密钥
- 确保没有多余的空格

### 2. "用户不存在"

**原因：** 用户未在商家平台注册，或邮箱不匹配

**解决：**
- 确保用户已在商家平台注册
- 确保论坛和商家平台使用相同的邮箱

### 3. "签名验证失败"

**原因：** API 密钥不一致

**解决：**
- 检查 Flarum 和商家平台的 API 密钥是否完全一致
- 重新复制密钥，确保没有遗漏字符

### 4. "硬币余额不足"

**原因：** 用户硬币不够

**解决：**
- 检查用户当前硬币余额
- 减少兑换数量

### 5. "时间戳过期"

**原因：** 服务器时间不同步

**解决：**
- 检查服务器系统时间是否正确
- 同步服务器时间

---

## 📋 测试清单

安装完成后，按以下步骤测试：

- [ ] 管理后台能看到"硬币兑换积分"扩展
- [ ] 启用扩展成功
- [ ] 配置页面能正常保存设置
- [ ] 用户菜单中出现"兑换积分"选项
- [ ] 点击后弹出兑换对话框
- [ ] 对话框显示当前硬币余额
- [ ] 输入有效数量能成功兑换
- [ ] 兑换后硬币余额减少
- [ ] 商家平台积分增加
- [ ] 数据库有兑换记录

---

## 🔗 相关链接

- **商家平台项目**：`C:\Users\ATZ\Desktop\商家展示\`
- **API 文档**：`商家展示\docs\coin-exchange-api.md`
- **商家平台 API 密钥**：
  ```
  E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45
  ```

---

## 📞 支持

- 📧 Email: info@doingfb.com
- 🌐 Website: https://doingfb.com

---

## 📝 注意事项

1. **API 密钥安全**：
   - ⚠️ 不要将密钥提交到公开的 Git 仓库
   - ⚠️ 生产环境使用 HTTPS

2. **用户邮箱匹配**：
   - 用户必须在两个平台使用相同的邮箱
   - 建议提示用户先在商家平台注册

3. **硬币字段名**：
   - 当前假设硬币存储在 `users.money` 字段
   - 如果不同，需要修改 `ExchangeController.php` 中的字段名

4. **每日限额**：
   - 当前限额检查较简单
   - 生产环境建议添加数据库记录

---

祝使用愉快！🎉
