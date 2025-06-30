<?php
/**
 * User Utility Class
 *
 * Provides utility functions for working with individual WordPress users,
 * including meta operations, authentication, and user information.
 *
 * @package ArrayPress\UserUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\UserUtils;

use WP_User;

/**
 * User Class
 *
 * Core operations for working with individual WordPress users.
 */
class User {

	/**
	 * Get a user object.
	 *
	 * @param int  $user_id       Optional. User ID. Default is 0.
	 * @param bool $allow_current Optional. Whether to allow fallback to current user. Default true.
	 *
	 * @return WP_User|null The user object if found, null otherwise.
	 */
	public static function get( int $user_id = 0, bool $allow_current = true ): ?WP_User {
		$user_id = self::validate_id( $user_id, $allow_current );
		if ( $user_id === null ) {
			return null;
		}

		$user = get_userdata( $user_id );

		return ( $user instanceof WP_User ) ? $user : null;
	}

	/**
	 * Get a user by identifier (ID, email, login, slug, or user object).
	 *
	 * @param mixed $identifier    The user identifier (ID, email, login, slug, or WP_User object).
	 * @param bool  $allow_current Optional. Whether to allow fallback to current user for empty/zero values. Default
	 *                             true.
	 *
	 * @return WP_User|null The user object if found, null otherwise.
	 */
	public static function get_by( $identifier, bool $allow_current = true ): ?WP_User {
		if ( empty( $identifier ) && ! is_numeric( $identifier ) ) {
			return $allow_current && is_user_logged_in() ? self::get( get_current_user_id(), false ) : null;
		}

		// Handle user object
		if ( is_object( $identifier ) && isset( $identifier->ID ) ) {
			return get_user_by( 'id', $identifier->ID ) ?: null;
		}

		// Handle numeric ID
		if ( is_numeric( $identifier ) ) {
			$user_id = (int) $identifier;
			if ( $user_id === 0 && $allow_current ) {
				return self::get( 0, true );
			}
			$user = get_userdata( $user_id );

			return ( $user instanceof WP_User ) ? $user : null;
		}

		// Handle email
		if ( is_string( $identifier ) && is_email( $identifier ) ) {
			$user = get_user_by( 'email', $identifier );

			return ( $user instanceof WP_User ) ? $user : null;
		}

		// Handle login/username or slug (nicename)
		if ( is_string( $identifier ) ) {
			$user = get_user_by( 'login', $identifier );
			if ( ! $user ) {
				// Try by slug (nicename) if login fails
				$user = get_user_by( 'slug', $identifier );
			}

			return ( $user instanceof WP_User ) ? $user : null;
		}

		return null;
	}

	/**
	 * Check if a user exists.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool True if the user exists, false otherwise.
	 */
	public static function exists( int $user_id ): bool {
		return get_userdata( $user_id ) !== false;
	}

	/**
	 * Get a user by their email address.
	 *
	 * @param string $email The email address to look up.
	 *
	 * @return WP_User|null The user object if found, null otherwise.
	 */
	public static function get_by_email( string $email ): ?WP_User {
		if ( ! is_email( $email ) ) {
			return null;
		}

		$user = get_user_by( 'email', $email );

		return ( $user instanceof WP_User ) ? $user : null;
	}

	/**
	 * Get a user by their login name.
	 *
	 * @param string $login The user's login name.
	 *
	 * @return WP_User|null Returns the user object if found, null otherwise.
	 */
	public static function get_by_login( string $login ): ?WP_User {
		$user = get_user_by( 'login', $login );

		return ( $user instanceof WP_User ) ? $user : null;
	}

	/**
	 * Get a user by their nicename (slug).
	 *
	 * @param string $slug The user's nicename.
	 *
	 * @return WP_User|null Returns the user object if found, null otherwise.
	 */
	public static function get_by_slug( string $slug ): ?WP_User {
		$user = get_user_by( 'slug', $slug );

		return ( $user instanceof WP_User ) ? $user : null;
	}

	// ========================================
	// User Information
	// ========================================

	/**
	 * Get the user's full name, falling back to display name if not set.
	 *
	 * @param mixed $identifier Optional. User identifier. Defaults to current user.
	 *
	 * @return string User's full name, display name, or empty string if no user found.
	 */
	public static function get_full_name( $identifier = 0 ): string {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return '';
		}

		$first     = trim( $user->first_name );
		$last      = trim( $user->last_name );
		$full_name = trim( "$first $last" );

		return empty( $full_name ) ? $user->display_name : $full_name;
	}

	/**
	 * Get the user's email address.
	 *
	 * @param mixed  $identifier Optional. User identifier. Default is the current user.
	 * @param string $default    Optional. Default email address.
	 *
	 * @return string User email or default value.
	 */
	public static function get_email( $identifier = 0, string $default = '' ): string {
		$user = self::get_by( $identifier );

		if ( ! $user || ! is_email( $user->user_email ) ) {
			return $default;
		}

		return $user->user_email;
	}

	/**
	 * Get the user's display name.
	 *
	 * @param mixed  $identifier Optional. User identifier. Default is the current user.
	 * @param string $default    Optional. Default display name.
	 *
	 * @return string Display name or default value.
	 */
	public static function get_display_name( $identifier = 0, string $default = '' ): string {
		$user = self::get_by( $identifier );

		return $user ? $user->display_name : $default;
	}

	/**
	 * Get the user's login (username).
	 *
	 * @param mixed  $identifier Optional. User identifier. Default is the current user.
	 * @param string $default    Optional. Default username.
	 *
	 * @return string User login or default value.
	 */
	public static function get_login( $identifier = 0, string $default = '' ): string {
		$user = self::get_by( $identifier );

		return $user ? $user->user_login : $default;
	}

	/**
	 * Get a specific field from the user.
	 *
	 * @param mixed  $identifier The user identifier.
	 * @param string $field      The field name.
	 *
	 * @return mixed The field value or null if not found.
	 */
	public static function get_field( $identifier, string $field ) {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return null;
		}

		if ( isset( $user->$field ) ) {
			return $user->$field;
		}

		return get_user_meta( $user->ID, $field, true );
	}

	// ========================================
	// Authentication
	// ========================================

	/**
	 * Check if a user is logged in.
	 *
	 * @return bool True if a user is logged in, false otherwise.
	 */
	public static function is_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check if the current user is a guest (not logged in).
	 *
	 * @return bool True if the current user is a guest, false if logged in.
	 */
	public static function is_guest(): bool {
		return ! self::is_logged_in();
	}

	/**
	 * Check if a specific user is the current logged-in user.
	 *
	 * @param mixed $identifier The user identifier to check.
	 *
	 * @return bool True if this is the current user, false otherwise.
	 */
	public static function is_current( $identifier ): bool {
		$user = self::get_by( $identifier );

		return $user && get_current_user_id() === $user->ID;
	}

	// ========================================
	// Capabilities & Roles
	// ========================================

	/**
	 * Check if the user has a specific capability.
	 *
	 * @param mixed  $identifier User identifier.
	 * @param string $capability The capability to check.
	 *
	 * @return bool Whether the user has the capability.
	 */
	public static function has_capability( $identifier, string $capability ): bool {
		$user = self::get_by( $identifier );

		return $user && $user->has_cap( $capability );
	}

	/**
	 * Get all capabilities for the user.
	 *
	 * @param mixed $identifier User identifier.
	 *
	 * @return array Array of capabilities.
	 */
	public static function get_capabilities( $identifier ): array {
		$user = self::get_by( $identifier );

		return $user ? array_keys( $user->allcaps ) : [];
	}

	/**
	 * Check if the user has a specific role.
	 *
	 * @param mixed  $identifier User identifier.
	 * @param string $role       The role to check for.
	 *
	 * @return bool Whether the user has the role.
	 */
	public static function has_role( $identifier, string $role ): bool {
		$user = self::get_by( $identifier );

		return $user && in_array( $role, $user->roles, true );
	}

	/**
	 * Get all roles for the user.
	 *
	 * @param mixed $identifier User identifier.
	 *
	 * @return array Array of role slugs.
	 */
	public static function get_roles( $identifier ): array {
		$user = self::get_by( $identifier );

		return $user ? $user->roles : [];
	}

	// ========================================
	// Meta Operations
	// ========================================

	/**
	 * Get meta value.
	 *
	 * @param mixed  $identifier The user identifier.
	 * @param string $key        Meta key.
	 * @param bool   $single     Whether to return single value.
	 *
	 * @return mixed Meta value or null if not found.
	 */
	public static function get_meta( $identifier, string $key, bool $single = true ) {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return null;
		}

		$value = get_user_meta( $user->ID, $key, $single );

		if ( $single && $value === '' ) {
			return null;
		}

		if ( ! $single && empty( $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get meta value with default.
	 *
	 * @param mixed  $identifier The user identifier.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $default    Default value.
	 *
	 * @return mixed Meta value or default.
	 */
	public static function get_meta_with_default( $identifier, string $meta_key, $default ) {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return $default;
		}

		$value = get_user_meta( $user->ID, $meta_key, true );

		return $value !== '' ? $value : $default;
	}

	/**
	 * Update meta if value has changed.
	 *
	 * @param mixed  $identifier The user identifier.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New meta value.
	 *
	 * @return bool True if value was changed.
	 */
	public static function update_meta_if_changed( $identifier, string $meta_key, $meta_value ): bool {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return false;
		}

		$current_value = get_user_meta( $user->ID, $meta_key, true );

		if ( $current_value !== $meta_value ) {
			return update_user_meta( $user->ID, $meta_key, $meta_value );
		}

		return false;
	}

	/**
	 * Delete meta.
	 *
	 * @param mixed  $identifier The user identifier.
	 * @param string $meta_key   Meta key.
	 *
	 * @return bool True on success.
	 */
	public static function delete_meta( $identifier, string $meta_key ): bool {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return false;
		}

		return delete_user_meta( $user->ID, $meta_key );
	}

	// ========================================
	// Dates & Activity
	// ========================================

	/**
	 * Get user registration date.
	 *
	 * @param mixed  $identifier Optional. User identifier. Default is the current user.
	 * @param string $format     Optional. PHP date format. Default 'Y-m-d H:i:s'.
	 *
	 * @return string|null Formatted date or null if user not found.
	 */
	public static function get_registration_date( $identifier = 0, string $format = 'Y-m-d H:i:s' ): ?string {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return null;
		}

		return mysql2date( $format, $user->user_registered );
	}

	/**
	 * Get user account age in days.
	 *
	 * @param mixed $identifier Optional. User identifier. Default is the current user.
	 *
	 * @return int|null Number of days since registration, or null if user not found.
	 */
	public static function get_account_age( $identifier = 0 ): ?int {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return null;
		}

		$registration_date = strtotime( $user->user_registered );

		return (int) floor( ( time() - $registration_date ) / DAY_IN_SECONDS );
	}

	/**
	 * Get human-readable time difference since registration.
	 *
	 * @param mixed $identifier Optional. User identifier. Default is the current user.
	 *
	 * @return string|null Human-readable time difference or null if user not found.
	 */
	public static function get_time_since_registration( $identifier = 0 ): ?string {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return null;
		}

		$registration_time = strtotime( $user->user_registered );

		return human_time_diff( $registration_time, time() );
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Validate and normalize user ID, optionally falling back to current user.
	 *
	 * @param int  $user_id       The user ID to validate.
	 * @param bool $allow_current Whether to allow fallback to current user.
	 *
	 * @return int|null Normalized user ID or null if invalid.
	 */
	public static function validate_id( int $user_id = 0, bool $allow_current = true ): ?int {
		if ( empty( $user_id ) && $allow_current && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		return empty( $user_id ) ? null : $user_id;
	}

	/**
	 * Delete a user and optionally reassign their content.
	 *
	 * @param mixed    $identifier     The user identifier.
	 * @param int|null $reassign_to    Optional. User ID to reassign posts to. Default null.
	 * @param bool     $delete_content Optional. Whether to delete the user's content. Default false.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $identifier, ?int $reassign_to = null, bool $delete_content = false ): bool {
		$user = self::get_by( $identifier );
		if ( ! $user ) {
			return false;
		}

		// If reassign_to user is specified, verify it exists
		if ( $reassign_to !== null && ! self::exists( $reassign_to ) ) {
			return false;
		}

		if ( $reassign_to === null && $delete_content ) {
			$reassign_to = true; // WordPress uses true to indicate content deletion
		}

		return wp_delete_user( $user->ID, $reassign_to );
	}

}