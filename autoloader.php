<?php
/**
 * # 自动加载类文件
 * @since 0.1 @2022.02.11
 */
spl_autoload_register(function ($class) {
	$base_path = SWL_PATH;
	$src_dir   = 'src';
	$filename  = str_replace('_', '-', $class);

	/**
	 * ### WordPress 类自动加载
	 * - 目录：wp-includes
	 * - 统一转为小写
	 * - 路径添加 【class-】前缀
	 *
	 * ### 其他类
	 * - Component 类：路径保留大小写
	 * - 本框架其他类：路径统一转小写
	 */
	if (0 === stripos($class, 'wp_')) {
		$src_dir  = 'wp-includes';
		$filename = 'class-' . strtolower($filename);
	} else if (0 !== stripos($class, 'component')) {
		$filename = strtolower($filename);
	}

	$path = $base_path . DIRECTORY_SEPARATOR . $src_dir . DIRECTORY_SEPARATOR . $filename;
	$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
	$file = $path . '.php';
	if (file_exists($file)) {
		require $file;
	}
});
