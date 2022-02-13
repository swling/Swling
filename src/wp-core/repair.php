<?php
/**
 * 为在不修改wp原文件的前提下引入文件，需要临时性补充缺失的wp函数或类
 * 本文件为开发时期临时文件，后期相关代码稳固后，应废弃对本文件的依赖
 */
function wp_load_translations_early() {}

function wp_debug_backtrace_summary() {}

function is_multisite() {
	return false;
}

function __() {}

function is_wp_error() {
	return false;
}
