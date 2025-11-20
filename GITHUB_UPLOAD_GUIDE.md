# 如何将 Flarum 扩展上传到 GitHub

## 前置准备

### 1. 安装 Git
- 下载: https://git-scm.com/download/win
- 安装后,打开 **Git Bash**

### 2. 配置 Git (首次使用)

打开 Git Bash,运行以下命令:

```bash
# 设置用户名和邮箱
git config --global user.name "你的GitHub用户名"
git config --global user.email "你的GitHub邮箱"

# 验证配置
git config --global --list
```

---

## 上传步骤

### 步骤 1: 在 GitHub 创建仓库

1. 访问 https://github.com
2. 登录你的账户
3. 点击右上角 `+` → `New repository`
4. 填写信息:
   - **Repository name**: `flarum-coin-exchange`
   - **Description**: `Flarum extension to exchange forum coins for merchant platform points`
   - **Public** (推荐) 或 **Private**
   - ⚠️ **不要勾选** "Initialize this repository with a README"
5. 点击 **Create repository**
6. **复制仓库 URL**,类似:
   ```
   https://github.com/你的用户名/flarum-coin-exchange.git
   ```

---

### 步骤 2: 初始化本地仓库

打开 **Git Bash**,执行以下命令:

```bash
# 进入扩展目录
cd /c/Users/ATZ/Desktop/flarum-coin-exchange

# 初始化 Git 仓库
git init

# 查看状态
git status
```

你应该看到很多红色的未跟踪文件。

---

### 步骤 3: 添加文件到暂存区

```bash
# 添加所有文件
git add .

# 查看暂存状态
git status
```

现在文件应该变成绿色(已暂存)。

---

### 步骤 4: 创建首次提交

```bash
# 创建提交
git commit -m "Initial commit: Flarum coin exchange extension v1.0.0

- Exchange forum coins to merchant platform points
- Ratio: 1 point = 10 coins
- SHA256 signature verification
- Daily limit control
- Admin configuration panel
- Chinese and English support"

# 查看提交历史
git log --oneline
```

---

### 步骤 5: 关联远程仓库

```bash
# 添加远程仓库 (替换成你的仓库地址)
git remote add origin https://github.com/你的用户名/flarum-coin-exchange.git

# 验证远程仓库
git remote -v
```

应该显示:
```
origin  https://github.com/你的用户名/flarum-coin-exchange.git (fetch)
origin  https://github.com/你的用户名/flarum-coin-exchange.git (push)
```

---

### 步骤 6: 推送到 GitHub

```bash
# 设置默认分支为 main
git branch -M main

# 推送到 GitHub
git push -u origin main
```

**首次推送可能需要登录**:
- 输入你的 GitHub 用户名
- 输入你的 GitHub 密码或 Personal Access Token

---

### 步骤 7: 验证上传成功

1. 访问 `https://github.com/你的用户名/flarum-coin-exchange`
2. 检查文件是否都已上传
3. 应该看到所有项目文件

---

## 后续更新推送

当你修改了代码后,使用以下命令更新:

```bash
# 1. 查看修改
git status

# 2. 添加修改的文件
git add .

# 3. 提交修改
git commit -m "描述你的修改内容"

# 4. 推送到 GitHub
git push
```

---

## 完整命令速查表

```bash
# === 初次上传 ===
cd /c/Users/ATZ/Desktop/flarum-coin-exchange
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/你的用户名/flarum-coin-exchange.git
git branch -M main
git push -u origin main

# === 后续更新 ===
git add .
git commit -m "Update: 修改描述"
git push

# === 常用查看命令 ===
git status          # 查看文件状态
git log --oneline   # 查看提交历史
git remote -v       # 查看远程仓库
git branch          # 查看分支
```

---

## 故障排查

### 问题 1: 推送时要求登录

**方法 A: 使用 Personal Access Token (推荐)**

1. 访问 GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. 点击 **Generate new token (classic)**
3. 选择权限: `repo` (完整仓库权限)
4. 复制生成的 token (只显示一次!)
5. 推送时,用户名输入 GitHub 用户名,密码输入 token

**方法 B: 使用 SSH (更安全)**

```bash
# 1. 生成 SSH 密钥
ssh-keygen -t ed25519 -C "你的邮箱"

# 2. 查看公钥
cat ~/.ssh/id_ed25519.pub

# 3. 复制公钥,添加到 GitHub
# GitHub → Settings → SSH and GPG keys → New SSH key

# 4. 修改远程仓库地址为 SSH
git remote set-url origin git@github.com:你的用户名/flarum-coin-exchange.git
```

---

### 问题 2: "fatal: remote origin already exists"

```bash
# 删除旧的远程仓库
git remote remove origin

# 重新添加
git remote add origin https://github.com/你的用户名/flarum-coin-exchange.git
```

---

### 问题 3: 推送被拒绝

```bash
# 先拉取远程更改
git pull origin main --allow-unrelated-histories

# 再推送
git push -u origin main
```

---

## 从 GitHub 安装扩展

上传到 GitHub 后,安装方式更简单:

### 方法 1: Composer 直接安装 (公开仓库)

```bash
cd 你的Flarum目录
composer require 你的用户名/flarum-coin-exchange
```

### 方法 2: Composer VCS 安装

在 Flarum 的 `composer.json` 中添加:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/你的用户名/flarum-coin-exchange.git"
    }
  ]
}
```

然后运行:
```bash
composer require doingfb/flarum-coin-exchange:dev-main
```

---

## 重要文件检查

上传前确认以下文件存在:

- [x] `composer.json` - PHP 包配置
- [x] `extend.php` - Flarum 扩展入口
- [x] `package.json` - npm 配置
- [x] `README.md` - 项目说明
- [x] `LICENSE` - 开源许可证
- [x] `.gitignore` - Git 忽略文件
- [x] `js/dist/forum.js` - 前端构建文件 ✅
- [x] `js/dist/admin.js` - 后台构建文件 ✅
- [x] `locale/zh-CN.yml` - 中文翻译
- [x] `locale/en.yml` - 英文翻译
- [x] `src/Controller/ExchangeController.php` - 核心逻辑

---

## 下一步

1. ✅ 上传到 GitHub
2. 📦 从 GitHub 安装到 Flarum
3. ⚙️ 在 Flarum 后台配置
4. 🧪 测试兑换功能

祝上传顺利! 🚀
