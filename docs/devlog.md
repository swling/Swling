## 保留 WP
- wpdb
- filters
- WP_Query
- WP_User_Query
- WP_Term_Query
- WP_Comment_Query

## 开发路线图
- 路由
- 数据库：引入 wpdb
- 钩子：引入 wp filter、wp action
- 确定数据表并完成数据表表的读写函数封装：引入 wp 数据表并做针对性调整
- 封装查询输出
- 模板加载及渲染
- 引入 wnd-frontend API 完成前端数据交互操作
- 对象缓存：引入 wp object cache
- 静态缓存（非必须）