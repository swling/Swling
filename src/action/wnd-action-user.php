<?php
namespace Wnd\Action;

use Exception;

/**
 * 注册用户 Ajax 操作基类
 * @since 0.8.66
 */
abstract class Wnd_Action_User extends Wnd_Action {

	final public function __construct() {
		if (!is_user_logged_in()) {
			throw new Exception(__('请登录', 'wnd'));
		}

		parent::__construct();
	}
}
