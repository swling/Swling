## 保留 WP
- wpdb
- filters
- WP_Query
- WP_User_Query
- WP_Term_Query
- WP_Comment_Query

## 开发路线图 (一)
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

## 开发路线图 (二)
基于 WordPress 4.9 开发
- git clone git@github.com:swling/WordPress.git
- 新建分支开发，方便随时切换回标准 4.9.19 做 AB 性能测试
- 适配 php 8
- 核心文件和当前 WP 5 覆盖对比，择优选取
- ucloud 服务器配置测试环境
- ignore /wp-content/ 、wp-config.php
- sublime 格式化：不格式化注释

## 开发线路（三）
- 阅读、理解、充分测试 WP_Query / WP_Tax_query / WP_Meta_Query / WP_Date_Query。一旦完成对 WP_Query 的重写或适配，项目即取得决定性成果

## 开发备忘录
- 统一封装数据表读写、及对象缓存机制（缓存机制可延后实现），参考 wnd-frontend wnd_users 表

https://stackoverflow.com/questions/50992188/how-to-push-a-shallow-clone-to-a-new-repo
- git 子模块 https://www.jianshu.com/p/9000cd49822c https://zhuanlan.zhihu.com/p/97761640

- 合并 wp_terms wp_term_taxonomy （将后者字段合并到前者：taxonomy、description、parent、count） https://www.zhihu.com/question/48691476
- 合并后将无法使用友情链接分类
- 需要注意层级分类法，和标签分类法 设置的区别 https://wndwp.com/archives/94

- 函数出错若需包含信息，返回 wp_error 类中 抛出异常
<!-- - wpdb_handler 改为单例模式 -->
<!-- - 统一 post term user comment 实例，继承抽象对象 WP_Object  -->

## 数据库操作相关函数返回值规范
- Get : data (单行：objcet; 多行：array) or false
- Insert/Update : ID（int） or 0
- Delete :  ID (int) or 0

## 后续
将用户描述简介 description 从 user_meta 转入 users 表