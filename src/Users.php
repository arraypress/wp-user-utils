<?php
/**
 * Users Utility Class
 *
 * Provides utility functions for working with multiple WordPress users,
 * including queries, bulk operations, and search functionality.
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
 * Users Class
 *
 * Operations for working with multiple WordPress users.
 */
class Users {

	/**
	 * Get multiple users by IDs.
	 *
	 * @param array  $user_ids Array of user IDs.
	 * @param string $fields   Optional. Which fields to return. Default 'all'.
	 *                         Accepts 'all', 'all_with_meta', 'ID', or specific field names.
	 *
	 * @return array Array of user objects, IDs, or field values based on $fields parameter.
	 */
	public static function get( array $user_ids, string $fields = 'all' ): array {
		$user_ids = array_filter( array_map( 'intval', $user_ids ) );

		if ( empty( $user_ids ) ) {
			return [];
		}

		return get_users( [
			'include' => $user_ids,
			'fields'  => $fields
		] );
	}

	/**
	 * Get users by field and value.
	 *
	 * @param string $field The field to search by (role, meta_key, etc.).
	 * @param mixed  $value The value to search for.
	 * @param array  $args  Additional query arguments.
	 *
	 * @return array Array of user objects.
	 */
	public static function get_by( string $field, $value, array $args = [] ): array {
		$default_args = [
			'number' => - 1,
		];

		switch ( $field ) {
			case 'role':
				$default_args['role'] = $value;
				break;

			case 'meta_key':
				$default_args['meta_key'] = $value;
				break;

			case 'meta':
			case 'meta_value':
				// Requires meta_key to be set in $args
				$default_args['meta_value'] = $value;
				break;

			case 'capability':
			case 'cap':
				// Custom handling for capabilities
				$all_users = get_users( wp_parse_args( $args, $default_args ) );

				return array_filter( $all_users, function ( $user ) use ( $value ) {
					return $user->has_cap( $value );
				} );

			default:
				// For any other field, try to use it directly
				$default_args[ $field ] = $value;
				break;
		}

		$args = wp_parse_args( $args, $default_args );

		return get_users( $args );
	}

	/**
	 * Get users by identifiers (IDs, emails, usernames, or user objects).
	 *
	 * @param array $identifiers    Array of user identifiers.
	 * @param bool  $return_objects Whether to return user objects or IDs.
	 *
	 * @return array Array of user IDs or user objects.
	 */
	public static function get_by_identifiers( array $identifiers, bool $return_objects = false ): array {
		if ( empty( $identifiers ) ) {
			return [];
		}

		$unique_users = [];

		foreach ( $identifiers as $identifier ) {
			if ( empty( $identifier ) ) {
				continue;
			}

			$user = null;

			// Handle user object
			if ( is_object( $identifier ) && isset( $identifier->ID ) ) {
				$user = get_user_by( 'id', $identifier->ID );
			} // Handle numeric ID
			elseif ( is_numeric( $identifier ) ) {
				$user = get_user_by( 'id', $identifier );
			} // Handle email
			elseif ( is_email( $identifier ) ) {
				$user = get_user_by( 'email', $identifier );
			} // Handle login/username
			else {
				$user = get_user_by( 'login', $identifier );
				if ( ! $user ) {
					// Try by slug (nicename) if login fails
					$user = get_user_by( 'slug', $identifier );
				}
			}

			if ( $user instanceof WP_User ) {
				$unique_users[ $user->ID ] = $user;
			}
		}

		return $return_objects ? array_values( $unique_users ) : array_map( 'intval', array_keys( $unique_users ) );
	}

	/**
	 * Get users by role.
	 *
	 * @param string $role The role to search for.
	 * @param array  $args Additional query arguments.
	 *
	 * @return array Array of user objects.
	 */
	public static function get_by_role( string $role, array $args = [] ): array {
		return self::get_by( 'role', $role, $args );
	}

	/**
	 * Get recent users.
	 *
	 * @param int   $number The number of users to retrieve.
	 * @param array $args   Additional query arguments.
	 *
	 * @return array Array of user objects.
	 */
	public static function get_recent( int $number = 5, array $args = [] ): array {
		$default_args = [
			'number'  => $number,
			'orderby' => 'registered',
			'order'   => 'DESC',
		];

		$args = wp_parse_args( $args, $default_args );

		return get_users( $args );
	}

	/**
	 * Get users by meta key and value.
	 *
	 * @param string $meta_key   The meta key to search for.
	 * @param mixed  $meta_value The meta value to match.
	 * @param array  $args       Additional query arguments.
	 *
	 * @return array Array of user objects.
	 */
	public static function get_by_meta( string $meta_key, $meta_value, array $args = [] ): array {
		$args['meta_key'] = $meta_key;

		return self::get_by( 'meta_value', $meta_value, $args );
	}

	/**
	 * Count users by role.
	 *
	 * @param string $role The role to count users by.
	 *
	 * @return int The number of users with the specified role.
	 */
	public static function count_by_role( string $role ): int {
		return count( self::get_by_role( $role ) );
	}

	// ========================================
	// Search & Options
	// ========================================

	/**
	 * Search users and return in value/label format.
	 *
	 * @param string $search Search term.
	 * @param array  $args   Additional arguments.
	 *
	 * @return array Array of ['value' => id, 'label' => name] items.
	 */
	public static function search_options( string $search, array $args = [] ): array {
		$users = self::search( $search, $args );

		$options = [];
		foreach ( $users as $user ) {
			$options[] = [
				'value' => $user->ID,
				'label' => $user->display_name,
			];
		}

		return $options;
	}

	/**
	 * Search users.
	 *
	 * @param string $search Search term.
	 * @param array  $args   Additional arguments.
	 *
	 * @return array Array of user objects.
	 */
	public static function search( string $search, array $args = [] ): array {
		if ( empty( $search ) ) {
			return [];
		}

		$defaults = [
			'search'         => '*' . $search . '*',
			'search_columns' => [ 'user_login', 'user_nicename', 'user_email', 'display_name' ],
			'number'         => 20,
			'orderby'        => 'display_name',
			'order'          => 'ASC'
		];

		$args = wp_parse_args( $args, $defaults );

		return get_users( $args );
	}

	/**
	 * Get user options for form fields.
	 *
	 * @param array $args Optional arguments.
	 *
	 * @return array Array of ['id' => 'name'] options.
	 */
	public static function get_options( array $args = [] ): array {
		$defaults = [
			'number'  => - 1,
			'orderby' => 'display_name',
			'order'   => 'ASC'
		];

		$args  = wp_parse_args( $args, $defaults );
		$users = get_users( $args );

		if ( empty( $users ) ) {
			return [];
		}

		$options = [];
		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}

		return $options;
	}

	// ========================================
	// Bulk Operations
	// ========================================

	/**
	 * Bulk update user meta.
	 *
	 * @param array  $user_ids   Array of user IDs.
	 * @param string $meta_key   Meta key to update.
	 * @param mixed  $meta_value Meta value to set.
	 *
	 * @return array Array of results with user IDs as keys and boolean success as values.
	 */
	public static function update_meta( array $user_ids, string $meta_key, $meta_value ): array {
		$results = [];

		foreach ( $user_ids as $user_id ) {
			$results[ $user_id ] = update_user_meta( $user_id, $meta_key, $meta_value );
		}

		return $results;
	}

	/**
	 * Bulk delete user meta.
	 *
	 * @param array  $user_ids Array of user IDs.
	 * @param string $meta_key Meta key to delete.
	 *
	 * @return array Array of results with user IDs as keys and boolean success as values.
	 */
	public static function delete_meta( array $user_ids, string $meta_key ): array {
		$results = [];

		foreach ( $user_ids as $user_id ) {
			$results[ $user_id ] = delete_user_meta( $user_id, $meta_key );
		}

		return $results;
	}

	/**
	 * Bulk delete users.
	 *
	 * @param array    $user_ids    Array of user IDs to delete.
	 * @param int|null $reassign_to Optional. User ID to reassign content to.
	 *
	 * @return array Array of results with user IDs as keys and boolean success as values.
	 */
	public static function delete( array $user_ids, ?int $reassign_to = null ): array {
		$results = [];

		foreach ( $user_ids as $user_id ) {
			$results[ $user_id ] = wp_delete_user( $user_id, $reassign_to );
		}

		return $results;
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Check which users exist from a given list.
	 *
	 * @param array $user_ids Array of user IDs to check.
	 *
	 * @return array Array of existing user IDs.
	 */
	public static function exists( array $user_ids ): array {
		$user_ids = array_filter( array_map( 'intval', $user_ids ) );

		if ( empty( $user_ids ) ) {
			return [];
		}

		return array_filter( $user_ids, function ( $user_id ) {
			return get_userdata( $user_id ) !== false;
		} );
	}

	/**
	 * Sanitize and validate user identifiers.
	 *
	 * @param array|mixed $identifiers    The input identifiers.
	 * @param bool        $return_objects Whether to return objects or IDs.
	 *
	 * @return array Array of sanitized user IDs or objects.
	 */
	public static function sanitize( $identifiers, bool $return_objects = false ): array {
		$identifiers = is_array( $identifiers ) ? $identifiers : [ $identifiers ];
		$identifiers = array_filter( $identifiers, function ( $identifier ) {
			return ! empty( $identifier ) || $identifier === '0';
		} );

		if ( empty( $identifiers ) ) {
			return [];
		}

		return self::get_by_identifiers( $identifiers, $return_objects );
	}

}