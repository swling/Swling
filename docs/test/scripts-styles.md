```php
## script
$wp_scripts = wp_scripts::get_instance();

wp_enqueue_script('jquery', '//baidu.com/jq.js', [], '2.9');
wp_enqueue_script('vue', '//baidu.com/vue.js', [], '2.9');
wp_enqueue_script('wnd', '//baidu.com/wnd.js', ['jquery', 'vue'], '2.9');
wp_enqueue_script('wndt', '//baidu.com/wndt.js', ['vue'], '2.9', true);

wp_enqueue_script('jqueryv3', '//baidu.com/jq_v3.js', [], '2.9');
wp_dequeue_script('jqueryv3');

wp_add_inline_script('vue', 'var wnd={}');

// 替换
wp_deregister_script('jquery');
wp_enqueue_script('jquery', '//ai.baidu.com/jq_v3.js', [], '2.9');

## style
$wp_styles = wp_styles::get_instance();

wp_enqueue_style('bulma', '//baidu.com/bulma.css', [], '2.9');
wp_enqueue_style('base', '//baidu.com/base.css', [], '2.9');
wp_enqueue_style('wnd', '//baidu.com/wnd.css', ['bulma'], '2.9');
wp_enqueue_style('wndt', '//baidu.com/wndt.css', ['bulma'], '2.9', true);

wp_add_inline_style('wnd', 'var wnd={}');

wp_head();
// echo 'string';
wp_footer();

print_r($wp_styles);
```