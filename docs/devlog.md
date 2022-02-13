## 保留 WP
- wpdb
- filters
- WP_Query
- WP_User_Query
- WP_Term_Query
- WP_Comment_Query

<del>## 开发路线图</del>
- 路由
- 数据库：引入 wpdb
- 钩子：引入 wp filter、wp action
- 确定数据表并完成数据表表的读写函数封装：引入 wp 数据表并做针对性调整
- 封装查询输出
- 模板加载及渲染
- 引入 wnd-frontend API 完成前端数据交互操作
- 对象缓存：引入 wp object cache
- 静态缓存（非必须）

## 开发路线
从零开发难度过大，预估时间精力成本过高。且本年度主要项目为【创图网】故修改策略为：基于现有精简版 WP 开发，移除 WP-Admin
- 完整引入 wp-includes，测试主题插件挂载，功能运行正常
- 针对性精简、优化