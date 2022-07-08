<?php
/**
 * Insert a user into the database.
 *
 * Most of the `$userdata` array fields have filters associated with the values. Exceptions are
 * 'ID', 'rich_editing', 'syntax_highlighting', 'comment_shortcuts', 'admin_color', 'use_ssl',
 * 'user_registered', 'user_activation_key', 'spam', and 'role'. The filters have the prefix
 * 'pre_user_' followed by the field name. An example using 'description' would have the filter
 * called 'pre_user_description' that can be hooked into.
 *
 * @since 2.0.0
 * @since 3.6.0 The `aim`, `jabber`, and `yim` fields were removed as default user contact
 *              methods for new installations. See wp_get_user_contact_methods().
 * @since 4.7.0 The `locale` field can be passed to `$userdata`.
 * @since 5.3.0 The `user_activation_key` field can be passed to `$userdata`.
 * @since 5.3.0 The `spam` field can be passed to `$userdata` (Multisite only).
 * @since 5.9.0 The `meta_input` field can be passed to `$userdata` to allow addition of user meta data.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array|object|WP_User $userdata {
 *     An array, object, or WP_User object of user data arguments.
 *
 *     @type int    $ID                   User ID. If supplied, the user will be updated.
 *     @type string $user_pass            The plain-text user password.
 *     @type string $user_login           The user's login username.
 *     @type string $user_nicename        The URL-friendly user name.
 *     @type string $user_url             The user URL.
 *     @type string $user_email           The user email address.
 *     @type string $display_name         The user's display name.
 *                                        Default is the user's username.
 *     @type string $nickname             The user's nickname.
 *                                        Default is the user's username.
 *     @type string $first_name           The user's first name. For new users, will be used
 *                                        to build the first part of the user's display name
 *                                        if `$display_name` is not specified.
 *     @type string $last_name            The user's last name. For new users, will be used
 *                                        to build the second part of the user's display name
 *                                        if `$display_name` is not specified.
 *     @type string $description          The user's biographical description.
 *     @type string $rich_editing         Whether to enable the rich-editor for the user.
 *                                        Accepts 'true' or 'false' as a string literal,
 *                                        not boolean. Default 'true'.
 *     @type string $syntax_highlighting  Whether to enable the rich code editor for the user.
 *                                        Accepts 'true' or 'false' as a string literal,
 *                                        not boolean. Default 'true'.
 *     @type string $comment_shortcuts    Whether to enable comment moderation keyboard
 *                                        shortcuts for the user. Accepts 'true' or 'false'
 *                                        as a string literal, not boolean. Default 'false'.
 *     @type string $admin_color          Admin color scheme for the user. Default 'fresh'.
 *     @type bool   $use_ssl              Whether the user should always access the admin over
 *                                        https. Default false.
 *     @type string $user_registered      Date the user registered in UTC. Format is 'Y-m-d H:i:s'.
 *     @type string $user_activation_key  Password reset key. Default empty.
 *     @type bool   $spam                 Multisite only. Whether the user is marked as spam.
 *                                        Default false.
 *     @type string $show_admin_bar_front Whether to display the Admin Bar for the user
 *                                        on the site's front end. Accepts 'true' or 'false'
 *                                        as a string literal, not boolean. Default 'true'.
 *     @type string $role                 User's role.
 *     @type string $locale               User's locale. Default empty.
 *     @type array  $meta_input           Array of custom user meta values keyed by meta key.
 *                                        Default empty.
 * }
 * @return int|WP_Error The newly created user's ID or a WP_Error object if the user could not
 *                      be created.
 */
function wp_insert_user($userdata) {
	$handler = WP_Core\Model\WPDB_Handler_User::get_instance();
	return $handler->insert($userdata);
}

/**
 * Update a user in the database.
 *
 * It is possible to update a user's password by specifying the 'user_pass'
 * value in the $userdata parameter array.
 *
 * If current user's password is being updated, then the cookies will be
 * cleared.
 *
 * @since 2.0.0
 *
 * @see wp_insert_user() For what fields can be set in $userdata.
 *
 * @param array|object|WP_User $userdata An array of user data or a user object of type stdClass or WP_User.
 * @return int|WP_Error The updated user's ID or a WP_Error object if the user could not be updated.
 */
function wp_update_user($userdata) {
	$handler = WP_Core\Model\WPDB_Handler_User::get_instance();
	return $handler->update($userdata);
}

/**
 * A simpler way of inserting a user into the database.
 *
 * Creates a new user with just the username, password, and email. For more
 * complex user creation use wp_insert_user() to specify more information.
 *
 * @since 2.0.0
 *
 * @see wp_insert_user() More complete way to create a new user.
 *
 * @param string $username The user's username.
 * @param string $password The user's password.
 * @param string $email    Optional. The user's email. Default empty.
 * @return int|WP_Error The newly created user's ID or a WP_Error object if the user could not
 *                      be created.
 */
function wp_create_user($username, $password, $email = '') {
	$user_login   = wp_slash($username);
	$user_email   = wp_slash($email);
	$user_pass    = $password;
	$display_name = $user_login;

	$userdata = compact('user_login', 'user_email', 'user_pass', 'display_name');
	return wp_insert_user($userdata);
}

/**
 * Remove user and optionally reassign posts and links to another user.
 *
 * If the $reassign parameter is not assigned to a User ID, then all posts will
 * be deleted of that user. The action {@see 'delete_user'} that is passed the User ID
 * being deleted will be run after the posts are either reassigned or deleted.
 * The user meta will also be deleted that are for that User ID.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $id User ID.
 * @param int $reassign Optional. Reassign posts and links to new User ID.
 * @return bool True when finished.
 */
function wp_delete_user($user_id, $reassign = 0) {
	$handler = WP_Core\Model\WPDB_Handler_User::get_instance();
	return $handler->delete($user_id);
}

/**
 * Retrieve user info by a given field
 *
 * @since 2.8.0
 * @since 4.4.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
 * @since 5.8.0 Returns the global `$current_user` if it's the user being fetched.
 *
 * @global WP_User $current_user The current user object which holds the user data.
 *
 * @param string     $field The field to retrieve the user with. id | ID | slug | email | login.
 * @param int|string $value A value for $field. A user ID, slug, email address, or login name.
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_user_by($field, $value) {
	global $current_user;

	switch ($field) {
		case 'id':
			$db_field = 'ID';
			break;
		case 'slug':
			$db_field = 'user_nicename';
			break;
		case 'email':
			$db_field = 'user_email';
			break;
		case 'login':
			// $value    = sanitize_user($value);
			$db_field = 'user_login';
			break;
		default:
			$db_field = $field;
	}

	$handler  = WP_Core\Model\WPDB_Handler_User::get_instance();
	$userdata = $handler->get_by($db_field, $value);

	if (!$userdata) {
		return false;
	}

	if ($current_user instanceof WP_User && $current_user->ID === (int) $userdata->ID) {
		return $current_user;
	}

	$user = new WP_User($userdata);

	return $user->data;
}

/**
 * Retrieve user info by user ID.
 *
 * @since 0.71
 *
 * @param int $user_id User ID
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_userdata($user_id) {
	return get_user_by('ID', $user_id);
}

/**
 * Retrieve user info by user ID.
 *
 * @since 0.71
 *
 * @param int $user_id User ID
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_user($user_id) {
	return get_user_by('ID', $user_id);
}

/**
 * Retrieves the current session token from the logged_in cookie.
 *
 * @since 4.0.0
 *
 * @return string Token.
 */
function wp_get_session_token() {
	$cookie = wp_parse_auth_cookie('', 'logged_in');
	return !empty($cookie['token']) ? $cookie['token'] : '';
}
