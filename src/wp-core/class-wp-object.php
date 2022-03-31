<?php
/**
 * Core WP Item Object abstract.
 * - WP_Post
 * - WP_User
 * - WP_Term
 * - WP_Comment
 *
 */
abstract class WP_Object {

	/**
	 * Retrieve WP_Object instance.
	 *
	 *
	 * @param int $object_id Object ID.
	 * @return WP_Object|false WP Object, false otherwise.
	 */
	public static function get_instance(int $object_id) {
		$handler = static::get_wpdb_handler();
		$object  = $handler->get($object_id);

		if (!$object) {
			return $object;
		}

		return new static($object);
	}

	abstract protected static function get_wpdb_handler(): object;

	/**
	 * Constructor.
	 *
	 * @param WP_Object|object $object WP_Object
	 */
	public function __construct(object $object) {
		foreach (get_object_vars($object) as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Convert object to array.
	 *
	 * @return array Object as array.
	 */
	public function to_array(): array{
		return get_object_vars($this);
	}
}
