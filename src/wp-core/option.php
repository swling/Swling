<?php
/**
 * Retrieves an option value based on an option name.
 *
 * If the option does not exist, and a default value is not provided,
 * boolean false is returned. This could be used to check whether you need
 * to initialize an option during installation of a plugin, however that
 * can be done better by using add_option() which will not overwrite
 * existing options.
 *
 * Not initializing an option and using boolean `false` as a return value
 * is a bad practice as it triggers an additional database query.
 *
 * The type of the returned value can be different from the type that was passed
 * when saving or updating the option. If the option value was serialized,
 * then it will be unserialized when it is returned. In this case the type will
 * be the same. For example, storing a non-scalar value like an array will
 * return the same array.
 *
 * In most cases non-string scalar and null values will be converted and returned
 * as string equivalents.
 *
 * Exceptions:
 * 1. When the option has not been saved in the database, the `$default` value
 *    is returned if provided. If not, boolean `false` is returned.
 * 2. When one of the Options API filters is used: {@see 'pre_option_{$option}'},
 *    {@see 'default_option_{$option}'}, or {@see 'option_{$option}'}, the returned
 *    value may not match the expected type.
 * 3. When the option has just been saved in the database, and get_option()
 *    is used right after, non-string scalar and null values are not converted to
 *    string equivalents and the original type is returned.
 *
 * Examples:
 *
 * When adding options like this: `add_option( 'my_option_name', 'value' );`
 * and then retrieving them with `get_option( 'my_option_name' );`, the returned
 * values will be:
 *
 * `false` returns `string(0) ""`
 * `true`  returns `string(1) "1"`
 * `0`     returns `string(1) "0"`
 * `1`     returns `string(1) "1"`
 * `'0'`   returns `string(1) "0"`
 * `'1'`   returns `string(1) "1"`
 * `null`  returns `string(0) ""`
 *
 * When adding options with non-scalar values like
 * `add_option( 'my_array', array( false, 'str', null ) );`, the returned value
 * will be identical to the original as it is serialized before saving
 * it in the database:
 *
 *    array(3) {
 *        [0] => bool(false)
 *        [1] => string(3) "str"
 *        [2] => NULL
 *    }
 *
 * @since 1.5.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option  Name of the option to retrieve. Expected to not be SQL-escaped.
 * @param mixed  $default Optional. Default value to return if the option does not exist.
 * @return mixed Value of the option. A value of any type may be returned, including
 *               scalar (string, boolean, float, integer), null, array, object.
 *               Scalar and null values will be returned as strings as long as they originate
 *               from a database stored option value. If there is no option in the database,
 *               boolean `false` is returned.
 */
function get_option(string $option_name, $default = false) {
	try {
		$handler      = Model\WPDB_Handler_Option::get_instance();
		$option_value = $handler->get_by('option_name', $option_name)->option_value ?? $default;
		return maybe_unserialize($option_value);
	} catch (Exception $e) {
		return $default;
	}

}

/**
 * Updates the value of an option that was already added.
 *
 * You do not need to serialize values. If the value needs to be serialized,
 * then it will be serialized before it is inserted into the database.
 * Remember, resources cannot be serialized or added as an option.
 *
 * If the option does not exist, it will be created.
 *
 * This function is designed to work with or without a logged-in user. In terms of security,
 * plugin developers should check the current user's capabilities before updating any options.
 *
 * @since 1.0.0
 * @since 4.2.0 The `$autoload` parameter was added.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string      $option   Name of the option to update. Expected to not be SQL-escaped.
 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
 *                              the default value is 'yes'. Default null.
 * @return bool True if the value was updated, false otherwise.
 */
function update_option(string $option_name, $option_value, bool $autoload = true): bool{
	/**
	 * If the new and old values are the same, no need to update.
	 *
	 * Unserialized values will be adequate in most cases. If the unserialized
	 * data differs, the (maybe) serialized data is checked to avoid
	 * unnecessary database calls for otherwise identical object instances.
	 *
	 * See https://core.trac.wordpress.org/ticket/38903
	 */
	$old_value = get_option($option_name);
	if ($option_value === $old_value || (maybe_serialize($option_value) === maybe_serialize($old_value))) {
		return false;
	}

	$option_value = maybe_serialize($option_value);
	$autoload     = $autoload ? 'yes' : 'no';
	$data         = compact('option_name', 'option_value', 'autoload');

	try {
		$handler  = Model\WPDB_Handler_Option::get_instance();
		$old_data = $handler->get_by('option_name', $option_name);
		if ($old_data) {
			$new_data = array_merge((array) $old_data, $data);
			return $handler->update($new_data);
		} else {
			return $handler->insert($data);
		}
	} catch (Exception $e) {
		return false;
	}
}

/**
 * Removes option by name. Prevents removal of protected WordPress options.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option Name of the option to delete. Expected to not be SQL-escaped.
 * @return bool True if the option was deleted, false otherwise.
 */
function delete_option(string $option_name): bool {
	try {
		$handler = Model\WPDB_Handler_Option::get_instance();

		$old_data = $handler->get_by('option_name', $option_name);
		if (!$old_data) {
			return false;
		}

		return $handler->delete($old_data->option_id);
	} catch (Exception $e) {
		return false;
	}
}

/**
 * Adds a new option.
 *
 * You do not need to serialize values. If the value needs to be serialized,
 * then it will be serialized before it is inserted into the database.
 * Remember, resources cannot be serialized or added as an option.
 *
 * You can create options without values and then update the values later.
 * Existing options will not be updated and checks are performed to ensure that you
 * aren't adding a protected WordPress option. Care should be taken to not name
 * options the same as the ones which are protected.
 *
 * @since 1.0.0
 *
 * @param string      $option     Name of the option to add. Expected to not be SQL-escaped.
 * @param mixed       $value      Optional. Option value. Must be serializable if non-scalar.
 *                                Expected to not be SQL-escaped.
 * @param string|bool $autoload   Optional. Whether to load the option when WordPress starts up.
 *                                Default is enabled. Accepts 'no' to disable for legacy reasons.
 * @return bool True if the option was added, false otherwise.
 */
function add_option(string $option_name, $option_value, bool $autoload = true): bool {
	if (get_option($option_name)) {
		return false;
	}

	return update_option($option_name, $option_value, $autoload);
}
