# 用户cookie 设置与验证
```php
wp_set_auth_cookie(1, true);
var_dump(wp_validate_auth_cookie('', 'logged_in'));
```