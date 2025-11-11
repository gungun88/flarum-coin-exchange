# Flarum 扩展故障诊断指南

## 当前问题
在扩展管理后台看到 500 错误,显示访问 `https://doingfb.com/admin` 失败

## 可能的原因

这个错误很可能**不是扩展代码的问题**,而是:
1. ✅ 浏览器开发者工具的预加载功能
2. ✅ 某些浏览器扩展的链接验证
3. ✅ 网络工具的自动检查

## 排查步骤

### 步骤 1: 检查扩展是否真的有问题

**请回答以下问题**:

1. ❓ 扩展能否正常启用? (在扩展列表中点击"启用"按钮)
2. ❓ 启用后能否看到扩展的设置按钮?
3. ❓ 点击设置按钮后,页面是否能正常显示? (忽略控制台的那个 500 错误)
4. ❓ 能否保存设置?

**如果以上都能正常操作,那这个 500 错误只是浏览器的"噪音",可以忽略!**

---

### 步骤 2: 确认扩展安装状态

SSH 到你的服务器,运行:

```bash
cd /你的Flarum目录

# 检查扩展是否已安装
php flarum info

# 查看扩展列表
ls -la vendor/doingfb/

# 检查扩展目录是否存在
ls -la vendor/doingfb/flarum-coin-exchange/
```

---

### 步骤 3: 查看 Flarum 日志

```bash
cd /你的Flarum目录

# 查看最近的错误日志
tail -n 50 storage/logs/flarum.log

# 或者查看完整日志
cat storage/logs/flarum.log
```

**请把日志内容发给我**,这样我能看到真正的错误!

---

### 步骤 4: 完全重装扩展

```bash
cd /你的Flarum目录

# 1. 卸载
composer remove doingfb/flarum-coin-exchange

# 2. 清除所有缓存
php flarum cache:clear
rm -rf storage/cache/*
rm -rf storage/views/*
rm -rf assets/*

# 3. 重新安装
composer require doingfb/flarum-coin-exchange:dev-main --with-all-dependencies

# 4. 清除缓存
php flarum cache:clear

# 5. 查看扩展状态
php flarum info
```

---

### 步骤 5: 检查 composer.json

确保你的 Flarum `composer.json` 中有:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/gungun88/flarum-coin-exchange.git"
    }
  ],
  "require": {
    "doingfb/flarum-coin-exchange": "dev-main"
  }
}
```

---

### 步骤 6: 测试扩展功能

1. 登录论坛前台
2. 点击右上角用户菜单
3. 查看是否有 **"兑换积分"** 选项

如果有这个选项,说明扩展已经正常工作了!

---

## 关于那个 500 错误

从截图来看,这个错误是:
```
GET https://doingfb.com/admin
ERR_HTTP_RESPONSE_CODE_FAILURE 500 (Internal Server Error)
```

这个请求**不是扩展发出的**,可能是:

### 可能性 1: 浏览器链接预加载
Chrome 开发者工具会自动检测页面中的链接,包括:
- `<a href="...">` 标签
- JavaScript 中的 URL 字符串
- 作者的 homepage 链接

### 可能性 2: 某个 Flarum 扩展的链接检查
有些 Flarum 扩展会检查其他扩展的链接是否有效

### 可能性 3: README 或描述中的链接
扩展的 README 或 composer.json 中可能还有这个链接

---

## 验证方法

**请按以下步骤操作**:

1. **清除浏览器缓存并关闭开发者工具**
2. **重新打开浏览器,不要打开开发者工具**
3. **访问扩展管理页面**
4. **尝试启用扩展并配置**

**如果不打开开发者工具就能正常使用,那这个 500 错误就是无害的!**

---

## 需要的信息

为了更好地帮助你,请提供:

1. ✅ 运行 `php flarum info` 的输出
2. ✅ 运行 `composer show doingfb/flarum-coin-exchange` 的输出
3. ✅ Flarum 日志中的错误信息 (`storage/logs/flarum.log`)
4. ✅ 扩展是否能正常启用和使用?
5. ✅ 前台用户菜单中是否有"兑换积分"选项?

---

## 快速测试命令

复制以下命令一键执行:

```bash
cd /你的Flarum目录
echo "=== Flarum 信息 ==="
php flarum info
echo ""
echo "=== 扩展信息 ==="
composer show doingfb/flarum-coin-exchange
echo ""
echo "=== 扩展文件 ==="
ls -la vendor/doingfb/flarum-coin-exchange/
echo ""
echo "=== 最近错误 ==="
tail -n 20 storage/logs/flarum.log
```

把输出结果发给我!
