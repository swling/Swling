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
 * Changes the current user by ID or name.
 *
 * Set $id to null and specify a name if you do not know a user's ID.
 *
 * Some WordPress functionality is based on the current user and not based on
 * the signed in user. Therefore, it opens the ability to edit and perform
 * actions on users who aren't signed in.
 *
 * @since 2.0.3
 *
 * @global WP_User $current_user The current user object which holds the user data.
 *
 * @param int|null $id   User ID.
 * @param string   $name User's username.
 * @return WP_User Current user User object.
 */
function wp_set_current_user($id, $name = '') {
	global $current_user;

	// If `$id` matches the current user, there is nothing to do.
	if (isset($current_user)
		&& ($current_user instanceof WP_User)
		&& ($id == $current_user->ID)
		&& (null !== $id)
	) {
		return $current_user;
	}

	$current_user = WP_User::get_instance($id);

	setup_userdata($current_user->ID);

	/**
	 * Fires after the current user is set.
	 *
	 * @since 2.0.1
	 */
	do_action('set_current_user');

	return $current_user;
}

/**
 * Retrieves the current user object.
 *
 * Will set the current user, if the current user is not set. The current user
 * will be set to the logged-in person. If no user is logged-in, then it will
 * set the current user to 0, which is invalid and won't have any permissions.
 *
 * This function is used by the pluggable functions wp_get_current_user() and
 * get_currentuserinfo(), the latter of which is deprecated but used for backward
 * compatibility.
 *
 * @since 4.5.0
 * @access private
 *
 * @see wp_get_current_user()
 * @global WP_User $current_user Checks if the current user is set.
 *
 * @return WP_User Current WP_User instance.
 */
function _wp_get_current_user() {
	global $current_user;

	if (!empty($current_user)) {
		if ($current_user instanceof WP_User) {
			return $current_user;
		}

		// Upgrade stdClass to WP_User.
		if (is_object($current_user) && isset($current_user->ID)) {
			$cur_id       = $current_user->ID;
			$current_user = null;
			wp_set_current_user($cur_id);
			return $current_user;
		}

		// $current_user has a junk value. Force to WP_User with ID 0.
		$current_user = null;
		wp_set_current_user(0);
		return $current_user;
	}

	if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
		wp_set_current_user(0);
		return $current_user;
	}

	/**
	 * Filters the current user.
	 *
	 * The default filters use this to determine the current user from the
	 * request's cookies, if available.
	 *
	 * Returning a value of false will effectively short-circuit setting
	 * the current user.
	 *
	 * @since 3.9.0
	 *
	 * @param int|false $user_id User ID if one has been determined, false otherwise.
	 */
	$user_id = apply_filters('determine_current_user', false);
	if (!$user_id) {
		wp_set_current_user(0);
		return $current_user;
	}

	wp_set_current_user($user_id);

	return $current_user;
}

/**
 * Sets up global user vars.
 *
 * Used by wp_set_current_user() for back compat. Might be deprecated in the future.
 *
 * @since 2.0.4
 *
 * @global string  $user_login    The user username for logging in
 * @global WP_User $userdata      User data.
 * @global int     $user_level    The level of the user
 * @global int     $user_ID       The ID of the user
 * @global string  $user_email    The email address of the user
 * @global string  $user_url      The url in the user's profile
 * @global string  $user_identity The display name of the user
 *
 * @param int $for_user_id Optional. User ID to set up global data. Default 0.
 */
function setup_userdata($for_user_id = 0) {
	global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_identity;

	if (!$for_user_id) {
		$for_user_id = get_current_user_id();
	}
	$user = get_userdata($for_user_id);

	if (!$user) {
		$user_ID       = 0;
		$user_level    = 0;
		$userdata      = null;
		$user_login    = '';
		$user_email    = '';
		$user_url      = '';
		$user_identity = '';
		return;
	}

	$user_ID       = (int) $user->ID;
	$user_level    = (int) WP_User::get_user_level($user->ID);
	$userdata      = $user;
	$user_login    = $user->user_login;
	$user_email    = $user->user_email;
	$user_url      = $user->user_url;
	$user_identity = $user->display_name;
}

/**
 * Gets the current user's ID.
 *
 * @since MU (3.0.0)
 *
 * @return int The current user's ID, or 0 if no user is logged in.
 */
function get_current_user_id() {
	if (!function_exists('wp_get_current_user')) {
		return 0;
	}
	$user = wp_get_current_user();
	return (isset($user->ID) ? (int) $user->ID : 0);
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
