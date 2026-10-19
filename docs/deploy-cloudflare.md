# 部署手册 — Cloudflare Pages（B 方案，用户操作步骤）

> 代码侧已就绪：`dist/` 为纯静态站（index.html + assets），MyMemory 直调已实测 CORS 放行。
> 以下步骤需要用户账号操作（注册/建仓库/授权），Claude 不代操作。

## 1. 建 GitHub 私有仓库（2 分钟）

1. 打开 github.com → 右上角 + → New repository
2. 名称：`realtime-translate`（用户已建仓 ✅）→ Create
3. 本地推送（在 E:\e2\myproject 下执行）：
   ```
   git remote add origin git@github.com:YIAnJs-shallow/realtime-translate.git
   git push -u origin main
   ```

## 2. Cloudflare Pages 连仓库（3 分钟）

1. 注册/登录 dash.cloudflare.com（免费套餐即可）
2. Workers & Pages → Create → Pages → Connect to Git → 选刚才的仓库
3. 构建设置：
   - Framework preset: **None**
   - Build command: **留空**
   - Build output directory: **dist**
4. Save and Deploy → 约 1 分钟后得到 `https://<项目名>.pages.dev`

## 3. 手机验证（2 分钟）

1. 手机浏览器（推荐 Chrome / iOS Safari）打开 pages.dev 地址
2. 安卓：菜单 → "添加到主屏幕"；iOS：Safari 分享 → "添加到主屏幕"
3. 桌面图标点开 → 全屏使用。此时无离线壳（P1-1 完成后秒开 + 断网可用界面）

## 4. 后续更新

改完代码 push 到 main → Pages 自动重新部署（约 1 分钟），手机图标无需重装。

## 注意事项

- pages.dev 域名国内直连速度一般；若慢，P2 可绑自有域名（Cloudflare 免费提供 CDN）
- MyMemory 免 Key 匿名额度按调用方 IP 计（手机流量/各自 WiFi 天然分散），约 5000 词/天/IP
- 若将来上 A 方案（云服务器），PHP 版代码现成，迁移即可
