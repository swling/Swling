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
- 引入 query-monitor 插件以便于调试
- 引入 wp-super-cache 缓存插件
- 对象缓存：引入 wp object cache
- 静态缓存（非必须）

## 开发备忘录
- 统一封装数据表读写、及对象缓存机制（缓存机制可延后实现），参考 wnd-frontend wnd_users 表