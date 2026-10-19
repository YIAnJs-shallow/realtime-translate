# ROADMAP — 实时翻译打字工具 TypeTrans

> 项目提案（用户 2026-10-19）：打中文实时显示英文，可自主选择各种语言，PC 和手机双端都能用。
> **重要补充（用户 2026-10-19）**：主用方向是**手机**，要"下载下来就能用"的体验，且需**随时随地**可用（不限同 WiFi）。
> 本文件为全局规划唯一来源，STATUS.md 记录此时此刻。

## 关键决策记录

| 日期 | 决策 | 状态 |
|---|---|---|
| 2026-10-19 | 环境：Laragon 8.7.0 @ E:\e2\laragon（PHP 8.3.33 / MySQL 8.4.3），代码 @ E:\e2\myproject | ✅ |
| 2026-10-19 | MVP 引擎：MyMemory 免 Key API（实测国内可达 + CORS 全开）；Google 系国内不可达弃用 | ✅ |
| 2026-10-19 | 开源调研：无 MIT/Apache 同类可引入（pot-desktop/Easydict 均 GPL-3.0 踩红线），Web 端"打字即译"无对口高星项目 → 自研 | ✅ |
| 2026-10-19 | **部署方案：用户拍板 B（零成本静态托管，前端直调 MyMemory）**；A（云服务器+PHP 后端）留作升级路径 | ✅ |
| 2026-10-19 | 核查结论：hmyx.shop 是 GitHub 私有仓库名（惠蒙备份用），**不存在服务器**；随时随地需静态托管解决 | ✅ |

## 架构（B 方案）

- **生产形态**：纯静态站（`dist/`：index.html + css/js），部署到 Cloudflare Pages（自动 HTTPS + 全球 CDN），手机"添加到主屏幕"→ PWA 体验（P1 加 manifest + Service Worker 后完整）。
- **本地形态**：PHP 版（index.php + api/ + lib/）作为"后端完整版"保留：有服务端限流/校验，是将来升级 A 方案（云服务器）的现成代码；集成测试跑在 PHP 版上。
- 两个版本共享同一 UI 交互口径（防抖 500ms、竞态保护、auto=中文、480 字节上限）。

## P0 基础设施与核心链路

| # | 任务 | 状态 | 说明 |
|---|---|---|---|
| 1 | 项目骨架（指针文件/PROJECT-LEAD/ROADMAP/.gitignore） | ✅ | 2026-10-19 |
| 2 | 翻译适配层 lib/translate.php + 私有密钥目录约定（E:\e2\private\myproject\secrets.php 预留） | ✅ | 2026-10-19 |
| 3 | 核心页面（响应式双栏/移动端堆叠、语言选择、防抖、复制/清空/交换） | ✅ | 2026-10-19 |
| 4 | API api/translate.php（限流/长度上限/错误分级/no-cache） | ✅ | 2026-10-19 |
| 5 | 集成测试（基线 16 断言）+ 测试基建修复（随机端口 + taskkill 强杀，根治孤儿服务器污染） | ✅ | 2026-10-19 |
| 6 | B 方案静态版 dist/（index.html + 直调 app.js，CORS 已实测放行） | ✅ | node --check + 本地起服验证 |
| 7 | Laragon 站点绑定（Apache 8080 避让 phpStudy）本地正式入口 | ⏳ | 需用户在 Laragon 界面确认端口 |
| 8 | **用户侧上线**：新建 GitHub 私有仓库 → push dist → Cloudflare Pages 连仓库 → 手机验证"添加到主屏幕" | ⏳ | 步骤见部署手册 docs/deploy-cloudflare.md |

## P1（手机优先重排）

1. **PWA 完整化**：manifest + 图标 + Service Worker 离线壳 + iOS meta ✅ 2026-10-19（scripts/gen_icons.php 可再生图标）
2. **翻译缓存**（localStorage 同文本+语言对秒出、省额度）
3. 语言互换已有；补充：常用语言收藏置顶、TTS 朗读、深色模式
4. 历史记录（需后端 → 顺延到 A 方案或改 localStorage 轻量版）

## P2 增强

- 升级 A 方案：云服务器 + PHP 后端（限流/额度保护/历史/多用户），PHP 代码已就绪
- 备用引擎切换（腾讯云免费 500 万字符/月，需实名注册；Key 只进私有目录）
- 全局快捷键

## 验收口径（全局）

- `php tests/integration.php` 断言基线只增不减（当前 16）。
- 模板混排文件 `php -l` 通过；静态版 JS `node --check` 通过。
- 每模块一提交，中文提交信息；CSS/JS 带 `?v=N` 改动递增。

## 负面清单（全局，防扩散）

- 不加用户系统/登录注册；不加后台管理页面；不发邮件/短信
- 不引入 composer/npm 运行时依赖；不做 Google 系翻译
- 不动惠蒙优选项目；部署/推送远端一律由用户执行
