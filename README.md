# WordPress User Utilities

A lightweight WordPress library for working with users and user metadata. Provides clean APIs for user operations, search functionality, and value/label formatting perfect for forms and admin interfaces.

## Features

* 🎯 **Clean API**: WordPress-style snake_case methods with consistent interfaces
* 🔍 **Built-in Search**: User search with value/label formatting
* 📋 **Form-Ready Options**: Perfect value/label arrays for selects and forms
* 👤 **User Information**: Easy access to user data and meta
* 🔐 **Authentication**: Simple authentication status checks
* 📊 **Meta Operations**: User meta handling with type safety
* 🚀 **Bulk Operations**: Process multiple users efficiently
* ➕ **User Creation**: Create single or multiple users with flexible options

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-user-utils
```

## Basic Usage

### Working with Single Users

```php
use ArrayPress\UserUtils\User;

// Get user by ID
$user = User::get( 123 );

// Get user with current user fallback
$user = User::get(); // Gets current user if ID is 0

// Check if user exists
if ( User::exists( 123 ) ) {
	// User exists
}

// Create a new user
$user = User::create( 'johndoe', 'john@example.com' );

// Create user with additional data
$user = User::create( 'johndoe', 'john@example.com', [
	'user_pass'    => 'custom_password',
	'role'         => 'editor',
	'display_name' => 'John Doe',
	'first_name'   => 'John',
	'last_name'    => 'Doe',
	'description'  => 'Content editor'
] );

// Create user only if they don't exist
$user = User::create_if_not_exists( 'johndoe', 'john@example.com' );

// Get user by different identifiers
$user = User::get_by_email( 'user@example.com' );
$user = User::get_by_login( 'username' );
$user = User::get_by_slug( 'user-slug' );

// Get user information
$full_name    = User::get_full_name( 123 );
$email        = User::get_email( 123 );
$display_name = User::get_display_name( 123 );
$login        = User::get_login( 123 );

// Get specific field
$field_value = User::get_field( 123, 'description' );

// Authentication checks
$is_logged_in = User::is_logged_in();
$is_guest     = User::is_guest();
$is_current   = User::is_current( 123 );

// Capabilities and roles
$has_cap      = User::has_capability( 123, 'edit_posts' );
$has_role     = User::has_role( 123, 'editor' );
$roles        = User::get_roles( 123 );
$capabilities = User::get_capabilities( 123 );

// User meta
$meta_value        = User::get_meta( 123, 'custom_field' );
$meta_with_default = User::get_meta_with_default( 123, 'preference', 'default_value' );

// Update meta only if changed
User::update_meta_if_changed( 123, 'last_activity', time() );

// Delete meta
User::delete_meta( 123, 'temp_data' );

// Date information
$reg_date    = User::get_registration_date( 123, 'Y-m-d' );
$account_age = User::get_account_age( 123 ); // Days since registration
$time_since  = User::get_time_since_registration( 123 ); // "2 years ago"

// Delete user
User::delete( 123, 456 ); // Delete user 123, reassign content to user 456
```

### Working with Multiple Users

```php
use ArrayPress\UserUtils\Users;

// Get multiple users
$users = Users::get( [ 1, 2, 3 ] );

// Create multiple users
$result = Users::create( [
	[
		'user_login'   => 'johndoe',
		'user_email'   => 'john@example.com',
		'display_name' => 'John Doe',
		'role'         => 'editor'
	],
	[
		'user_login'   => 'janedoe',
		'user_email'   => 'jane@example.com',
		'display_name' => 'Jane Doe',
		'role'         => 'author'
	]
] );

// Create users with shared defaults
$result = Users::create( [
	[
		'user_login' => 'user1',
		'user_email' => 'user1@example.com'
	],
	[
		'user_login' => 'user2',
		'user_email' => 'user2@example.com'
	]
], [
	'role' => 'subscriber', // Default role for all users
	'send_user_notification' => false
] );

// Create users only if they don't exist
$result = Users::create_if_not_exists( [
	[
		'user_login' => 'admin',
		'user_email' => 'admin@example.com',
		'role'       => 'administrator'
	],
	[
		'user_login' => 'editor',
		'user_email' => 'editor@example.com',
		'role'       => 'editor'
	]
] );

// Check results
if ( ! empty( $result['created'] ) ) {
	foreach ( $result['created'] as $user ) {
		echo "Created user: " . $user->user_login . "\n";
	}
}

if ( ! empty( $result['existing'] ) ) {
	foreach ( $result['existing'] as $user ) {
		echo "User already exists: " . $user->user_login . "\n";
	}
}

if ( ! empty( $result['errors'] ) ) {
	foreach ( $result['errors'] as $error ) {
		echo "Error: " . $error . "\n";
	}
}

// Get users by identifiers (IDs, emails, usernames)
$user_ids     = Users::get_by_identifiers( [ 'admin', 'user@example.com', 123 ] );
$user_objects = Users::get_by_identifiers( [ 'admin', 'user@example.com' ], true );

// Get users by role
$editors     = Users::get_by_role( 'editor' );
$admin_count = Users::count_by_role( 'administrator' );

// Get recent users
$recent_users = Users::get_recent( 10 );

// Get users by meta
$premium_users = Users::get_by_meta( 'subscription_type', 'premium' );

// Search users and get options
$options = Users::search_options( 'john' );
// Returns: [['value' => 1, 'label' => 'John Doe'], ...]

// Search users
$search_results = Users::search( 'smith' );

// Get all users as options
$all_options = Users::get_options();
// Returns: [1 => 'John Doe', 2 => 'Jane Smith', ...]

// Get options with custom args
$author_options = Users::get_options( [
	'role'       => 'author',
	'meta_key'   => 'active_status',
	'meta_value' => 'active'
] );

// Bulk operations
$results = Users::update_meta( [ 1, 2, 3 ], 'newsletter', true );
$results = Users::delete_meta( [ 1, 2, 3 ], 'temp_data' );
$results = Users::delete( [ 4, 5, 6 ], 1 ); // Delete users, reassign to user 1

// Utility methods
$existing_users = Users::exists( [ 1, 2, 3, 999 ] ); // Returns [1, 2, 3]
$clean_ids      = Users::sanitize( [ '1', 'invalid', '3' ] ); // Returns [1, 3]
```

### User Creation Examples

```php
// Plugin activation - create default users
function create_default_users() {
	$default_users = [
		[
			'user_login'   => 'site_manager',
			'user_email'   => 'manager@yoursite.com',
			'display_name' => 'Site Manager',
			'role'         => 'editor'
		],
		[
			'user_login'   => 'content_writer',
			'user_email'   => 'writer@yoursite.com',
			'display_name' => 'Content Writer',
			'role'         => 'author'
		]
	];

	$result = Users::create_if_not_exists( $default_users );

	// Log creation results
	error_log( sprintf( 'Created %d users, %d already existed',
		count( $result['created'] ),
		count( $result['existing'] )
	) );
}

// Import users from CSV data
function import_users_from_csv( $csv_data ) {
	$users = [];

	foreach ( $csv_data as $row ) {
		$users[] = [
			'user_login'   => $row['username'],
			'user_email'   => $row['email'],
			'display_name' => $row['full_name'],
			'role'         => $row['role'] ?? 'subscriber'
		];
	}

	return Users::create( $users, [
		'send_user_notification' => false // Don't email new users
	] );
}

// Create test users for development
function create_test_users() {
	$test_users = [
		'Test Admin'      => 'administrator',
		'Test Editor'     => 'editor',
		'Test Author'     => 'author',
		'Test Subscriber' => 'subscriber'
	];

	$users_data = [];
	foreach ( $test_users as $name => $role ) {
		$username     = strtolower( str_replace( ' ', '_', $name ) );
		$users_data[] = [
			'user_login'   => $username,
			'user_email'   => $username . '@test.local',
			'display_name' => $name,
			'role'         => $role
		];
	}

	return Users::create_if_not_exists( $users_data );
}
```

### Search Functionality

```php
// Basic search
$users = Users::search( 'john doe' );

// Search with custom args
$users = Users::search( 'manager', [
	'role'       => 'editor',
	'number'     => 5,
	'meta_key'   => 'department',
	'meta_value' => 'marketing'
] );

// Get search results as options for forms
$options = Users::search_options( 'admin' );
```

### User Validation and Sanitization

```php
// Validate user ID with current user fallback
$user_id = User::validate_id( 0, true ); // Returns current user ID if logged in

// Check which users exist
$existing = Users::exists( [ 1, 2, 999 ] ); // Returns [1, 2]

// Sanitize mixed identifiers
$clean_users = Users::sanitize( [ 'admin', 'user@example.com', 123 ] );
```

## Key Features

- **Value/Label Format**: Perfect for forms and selects
- **Search Functionality**: Built-in user search with flexible options
- **User Creation**: Create single or multiple users with validation
- **Meta Operations**: Simple user meta handling
- **Bulk Operations**: Process multiple users efficiently
- **Authentication Helpers**: Login status and current user checks
- **Flexible Identifiers**: Use IDs, emails, usernames, or objects interchangeably

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-user-utils)
- [Issue Tracker](https://github.com/arraypress/wp-user-utils/issues)