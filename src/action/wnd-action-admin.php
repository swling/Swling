<?php
namespace Wnd\Action;

use Exception;

/**
 * 管理员 Ajax 操作基类
 * @since 0.8.66
 */
abstract class Wnd_Action_Admin extends Wnd_Action {

	final public function __construct() {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}

		parent::__construct();
	}
}
