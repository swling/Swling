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

/**
 * @uses dbDelta
 */
function wp_should_upgrade_global_tables() {
	return true;
}

function __() {}

function is_wp_error() {
	return false;
}

// ***************************************************************************************************************** //
/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 *                    Default false.
 * @see reset_mbstring_encoding()
 * @since 3.7.0
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 */
function mbstring_binary_safe_encoding($reset = false) {
	static $encodings  = [];
	static $overloaded = null;

	if (is_null($overloaded)) {
		if (function_exists('mb_internal_encoding')
			&& ((int) ini_get('mbstring.func_overload') & 2) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
		) {
			$overloaded = true;
		} else {
			$overloaded = false;
		}
	}

	if (false === $overloaded) {
		return;
	}

	if (!$reset) {
		$encoding = mb_internal_encoding();
		array_push($encodings, $encoding);
		mb_internal_encoding('ISO-8859-1');
	}

	if ($reset && $encodings) {
		$encoding = array_pop($encodings);
		mb_internal_encoding($encoding);
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
	mbstring_binary_safe_encoding(true);
}
