# ROADMAP — 实时翻译打字工具（暂名）

> 项目提案（用户 2026-10-19，一句话）：打中文实时显示英文，可自主选择各种语言，PC 和手机双端都能用。
> 负责人分析后定名待用户拍板；本文件为全局规划唯一来源，STATUS.md 记录此时此刻。

## 产品定位（负责人分析）

- **核心场景**：一边打字一边获得实时译文，主打"中文→任意语言"的即时反馈，类似"边写边译"的输入体验。
- **形态**：响应式 Web 应用（一个地址 PC/手机浏览器都能用），原生 PHP 8.3 + MySQL 8.4（Laragon 环境）+ 原生 JS，不引入前端框架。
- **翻译引擎**：MVP 用 MyMemory 免 Key HTTP API（2026-10-19 实测国内可达，翻译成功）；引擎做成适配层，后续可切换。
- **开源调研结论（合规记录）**：Stichoza/google-translate-php（MIT）仅封装 Google 接口——国内不可达，不引入；翻译调用用原生 curl 自写适配层（集成成本≈0），不引入任何 composer 依赖。

## P0 基础设施与核心链路

| # | 任务 | 状态 | 说明 |
|---|---|---|---|
| 1 | 项目骨架：AGENTS/CLAUDE/PROJECT-LEAD 指针 + ROADMAP + .gitignore + 首提交 | ✅ 完成 | 2026-10-19 |
| 2 | 翻译适配层：`lib/translate.php` provider 抽象（mymemory 起步），私有权密钥目录 `E:\e2\private\myproject\secrets.php` 预留（本仓库只放 `config/secrets.example.php` 模板） | ✅ 完成 | 密钥不入库不入 Web 根 |
| 3 | 核心页面 `index.php`：左输入右输出（移动端上下堆叠）、源语言选择（auto=默认中文）+ 目标语言下拉（十余种常用语）、500ms 防抖实时请求、一键复制、清空 | ✅ 完成 | CSS/JS 带 `?v=N` |
| 4 | API `api/translate.php`：POST `{text, source, target}` → 引擎适配层 → JSON；长度上限 5000 字符、请求频率限制、错误分级（不暴露技术细节）、全站 no-cache 头 | ✅ 完成 | 沿用惠蒙 no-cache 踩坑经验 |
| 5 | 集成测试 `tests/integration.php`：curl 打本地服务断言（页面 200/含关键元素、API 合法调用/空输入/超长/非法语言码），断言基线只增不减 | ✅ 完成 | 基线 15 |
| 6 | Laragon 站点绑定：Apache 改 8080 避让 phpStudy 的 80/443，vhost 指向本项目，本地域名可访问 | ⏳ 待做 | 需用户在 Laragon 界面确认端口 |
| 7 | 手机端局域网访问：同 WiFi 下 `http://<PC局域网IP>:8080` 可用，Windows 防火墙放行 8080 | ⏳ 待做 | 防火墙命令需管理员权限 |

## P1 功能完善

- 翻译历史（MySQL `myproject_db`，migrate 幂等 DDL，个人记录可删除）
- 语言互换按钮（⇄ 一键交换源/目标）
- 常用语言收藏置顶
- 译文 TTS 朗读（浏览器 SpeechSynthesis，零依赖）
- 深色模式

## P2 增强

- 引擎切换：腾讯云机器翻译（免费 500 万字符/月，需用户实名注册拿 Key，Key 只进私有目录）；MyMemory 免 Key 匿名额度有限（约 5000 词/天/IP），重度使用前必须切
- PWA（添加到主屏幕、离线壳）
- 全局快捷键（如 Ctrl+Enter 强制翻译）
- 翻译缓存（同文本+语言对命中缓存，省额度提速）

## 验收口径（全局）

- `php tests/integration.php` 断言基线只增不减（当前 15）。
- 模板混排文件改动后 `php -l` 通过。
- 每模块一提交，中文提交信息。
- CSS/JS 引用带 `?v=N`，改动递增。

## 负面清单（全局，防扩散）

- 不加用户系统/登录注册（历史记录 MVP 不分用户）
- 不加后台管理页面
- 不发邮件、不接短信
- 不引入 composer/npm 依赖
- 不做 Google 系翻译（国内不可达）
- 不动惠蒙优选项目（E:\e2\phpstudy\phpstudy_pro\WWW\my_project）
