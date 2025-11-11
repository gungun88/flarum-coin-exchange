# Flarum 硬币兑换扩展 - 安装指南

## ✅ 步骤 1: 构建前端资源 (已完成)

```bash
cd c:\Users\ATZ\Desktop\flarum-coin-exchange
npm install
npm run build
```

**状态**: ✅ 已完成
- forum.js: 4.13 KiB
- admin.js: 1.54 KiB

---

## 📦 步骤 2: 安装到 Flarum 论坛

你有两种安装方法,推荐使用**方法 A**:

### 方法 A: Composer 本地路径安装 (推荐)

#### 2.1 修改 Flarum 的 composer.json

在你的 Flarum 根目录找到 `composer.json` 文件,添加本地仓库配置:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "C:/Users/ATZ/Desktop/flarum-coin-exchange",
      "options": {
        "symlink": false
      }
    }
  ]
}
```

**注意**:
- 路径使用正斜杠 `/` 而不是反斜杠 `\`
- `"symlink": false` 表示复制文件而不是创建符号链接

#### 2.2 运行 Composer 安装

```bash
cd 你的Flarum目录
composer require doingfb/flarum-coin-exchange:*
```

#### 2.3 清除缓存

```bash
php flarum cache:clear
```

---

### 方法 B: 手动复制安装 (备选)

如果 Composer 安装有问题,可以手动复制:

#### 2.1 复制扩展文件

```bash
# Windows 命令提示符
xcopy /E /I "c:\Users\ATZ\Desktop\flarum-coin-exchange" "你的Flarum目录\vendor\doingfb\flarum-coin-exchange"
```

或者手动复制:
1. 打开 `c:\Users\ATZ\Desktop\flarum-coin-exchange`
2. 复制整个文件夹到 `你的Flarum目录\vendor\doingfb\flarum-coin-exchange`

#### 2.2 修改 composer.json

在 Flarum 根目录的 `composer.json` 中,找到 `"require"` 部分,添加:

```json
{
  "require": {
    "doingfb/flarum-coin-exchange": "*"
  }
}
```

#### 2.3 更新 Composer 自动加载

```bash
cd 你的Flarum目录
composer dump-autoload
php flarum cache:clear
```

---

## ⚙️ 步骤 3: 在 Flarum 后台启用扩展

1. 登录 Flarum 管理后台
2. 进入 **管理后台 > 扩展** (Extensions)
3. 找到 **"硬币兑换积分"** (Coin Exchange) 扩展
4. 点击 **启用** (Enable)

---

## 🔧 步骤 4: 配置扩展

启用后,进入扩展设置:

### 4.1 进入设置页面

**管理后台 > 扩展 > 硬币兑换积分 > 设置**

### 4.2 填写配置信息

| 配置项 | 值 | 说明 |
|--------|-----|------|
| **启用功能** | ✅ 勾选 | 开启硬币兑换功能 |
| **API 地址** | `http://localhost:3000/api/exchange/coins-to-points` | 商家平台 API 地址<br/>生产环境改为你的域名 |
| **API 密钥** | `E483D0FCDCA7D2A900F679BFBE149BB34FE518A149BB8B7529EB0FCA6773BF45` | 与商家平台一致的密钥 |
| **每日限额** | `1000` | 每个用户每天最多兑换 1000 硬币 |

### 4.3 保存设置

点击 **保存设置** 按钮

---

## ✅ 步骤 5: 验证安装

### 5.1 检查用户菜单

1. 以普通用户身份登录论坛
2. 点击右上角的 **用户名/头像**
3. 下拉菜单中应该出现 **"兑换积分"** 选项

### 5.2 测试兑换功能

1. 点击 **"兑换积分"**
2. 弹出兑换对话框
3. 应该显示:
   - ✅ 当前硬币余额
   - ✅ 兑换比例说明 (1积分 = 10硬币)
   - ✅ 每日限额提示
   - ✅ 输入框和兑换按钮

### 5.3 完整兑换测试

**前提条件**:
- ✅ 用户在论坛有硬币余额 (至少 10 硬币)
- ✅ 用户已在商家平台注册 (使用相同邮箱)
- ✅ 商家平台开发服务器正在运行 (http://localhost:3000)

**测试步骤**:
1. 输入兑换数量,如 `100` (必须是 10 的倍数)
2. 点击 **"立即兑换"**
3. 等待兑换完成
4. 检查结果:
   - ✅ 论坛硬币减少 100
   - ✅ 商家平台积分增加 10
   - ✅ 显示成功提示消息

---

## 🐛 故障排查

### 问题 1: 找不到扩展

**可能原因**:
- Composer 安装失败
- 路径配置错误

**解决方法**:
```bash
cd 你的Flarum目录
composer dump-autoload
php flarum cache:clear
php flarum info
```

检查输出中是否列出 `doingfb/flarum-coin-exchange`

### 问题 2: 启用失败

**可能原因**:
- 前端资源未构建
- 文件权限问题

**解决方法**:
```bash
# 重新构建前端
cd c:\Users\ATZ\Desktop\flarum-coin-exchange
npm run build

# 清除 Flarum 缓存
cd 你的Flarum目录
php flarum cache:clear
```

### 问题 3: 没有"兑换积分"菜单

**可能原因**:
- 前端资源未正确加载
- 浏览器缓存

**解决方法**:
1. 清除浏览器缓存 (Ctrl+Shift+Delete)
2. 强制刷新页面 (Ctrl+F5)
3. 检查浏览器控制台是否有 JavaScript 错误

### 问题 4: 兑换时提示错误

| 错误提示 | 原因 | 解决方法 |
|----------|------|----------|
| "API 配置不完整" | 未配置 API 地址或密钥 | 检查扩展设置页面 |
| "用户不存在" | 商家平台没有该用户 | 使用相同邮箱注册商家平台 |
| "签名验证失败" | API 密钥不一致 | 确保两边密钥完全一致 |
| "硬币余额不足" | 论坛硬币不够 | 检查用户硬币余额 |
| "网络请求失败" | 无法连接商家平台 | 检查商家平台是否运行 |

---

## 📋 安装检查清单

完成安装后,逐项检查:

- [ ] **前端资源已构建** (forum.js 和 admin.js 存在)
- [ ] **扩展已通过 Composer 安装**
- [ ] **在管理后台看到扩展**
- [ ] **扩展已启用** (绿色勾选标记)
- [ ] **已配置 API 地址和密钥**
- [ ] **用户菜单中出现"兑换积分"**
- [ ] **点击后弹出对话框**
- [ ] **能成功兑换** (论坛硬币减少,商家积分增加)

全部打勾后,安装完成! 🎉

---

## 📞 需要帮助?

如果遇到问题:
1. 检查 Flarum 日志: `storage/logs/flarum.log`
2. 检查浏览器控制台 (F12 > Console)
3. 检查商家平台日志 (开发服务器终端输出)

---

## 🔗 相关文件

- **扩展源码**: `c:\Users\ATZ\Desktop\flarum-coin-exchange\`
- **安装文档**: `c:\Users\ATZ\Desktop\flarum-coin-exchange\INSTALL.md`
- **商家平台**: `c:\Users\ATZ\Desktop\商家展示\`
- **API 密钥文档**: `c:\Users\ATZ\Desktop\商家展示\.env.local`

---

祝安装顺利! 🚀
