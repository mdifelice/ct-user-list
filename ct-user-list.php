<?php
/**
 * Plugin Name: Codeable Test User List
 * Description: Allows to print a list of users
 * Author:      Martín Di Felice
 * Author URI:  https://github.com/mdifelice
 * Text Domain: ct-user-list
 * Domain Path: /languages
 * Version:     1
 *
 * @package     CT_User_List
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'CT_USER_LIST_PAGE_LENGTH',     10 );
define( 'CT_USER_LIST_GET_USERS_NONCE', 'ct-user-list-get-users-nonce' );

/**
 * Returns a list of users.
 *
 * @param array/string $options {
 * 	Options to be considered when retrieving users from the database.
 *
 * 	@type string $role     Roles should be retrieved. Default is any role.
 * 	@type string $order_by Field to be used to order users.
 * 	@type string $order    Type of order: ASC (ascending) or DESC (descending).
 * 	                       Default is ascending.
 * 	@type int    $page     Page to retrieve. Default is one.
 * }
 *
 * @return array User information {
 *  @type int   $total Number of rows
 *  @type array $data  List of users
 * }
 */
function ct_user_list_get_user_data( $options = null ) {
	$defaults = array(
		'role'     => '',
		'order_by' => '',
		'order'    => 'ASC',
		'page'     => 1,
	);

	$options = wp_parse_args( $defaults, $options );

	$roles = ct_user_list_get_roles();

	if ( isset( $roles[ $options['role'] ] ) ) {
		$role = $options['role'];
	} else {
		$role = $defaults['role'];
	}

	$fields   = ct_user_list_get_fields();
	$order_by = $defaults['order_by'];

	if ( isset( $fields[ $options['order_by'] ] ) ) {
		$field = $fields[ $options['order_by'] ];

		if ( $field['sortable'] ) {
			$order_by = $options['order_by'];
		}
	}

	if ( in_array( strtolower( $options['order'] ), array( 'asc', 'desc' ), true ) ) {
		$order = $options['order'];
	} else {
		$order = $defaults['order'];
	}

	$page = absint( $options['page'] );

	if ( ! $page ) {
		$page = $defauts['page'];
	}

	$query = new WP_User_Query( array(
		'role'        => $role,
		'order_by'    => $order_by,
		'order'       => $order,
		'paged'       => $page,
		'number'      => CT_USER_LIST_PAGE_LENGTH,
		'count_total' => true,
	) );

	$users   = array();
	$results = $query->get_results();

	foreach ( $results as $result ) {
		$user = array();

		foreach ( $fields as $id => $field ) {
			$user[ $id ] = $field['callback']( $result );
		}

		$users[] = $user;
	}

	return array(
		'users' => $users,
		'total' => $query->get_total(),
	);
}

/**
 * @todo
 */
function ct_user_list_ajax_get_users() {
	check_ajax_referer( CT_USER_LIST_GET_USERS_NONCE );

	$options             = array();
	$whitelisted_options = array(
		'role',
		'order',
		'order_by',
		'page',
	);

	foreach ( $whitelisted_options as $whitelisted_option ) {
		if ( isset( $_POST[ $whitelisted_option ] ) ) {
			$options[ $whitelisted_option ] = sanitize_text_field( wp_unslash( $_POST[ $whitelisted_option ] ) );
		}
	}

	$users = ct_user_list_get_users( $options );

	wp_send_json( $users );
}

/**
 * @todo
 */
function ct_user_list_get_roles() {
	$all_roles = WP_Roles()->roles;

	foreach ( $all_roles as $role => $details ) {
		$roles[ $role ] = translate_user_role( $details['name'] );
	}

	return $roles;
}

/**
 * @todo
 */
function ct_user_list_get_fields() {
	return array(
		'username'   => array(
			'label'    => __( 'Username', 'ct-user-list' ),
			'sortable' => true,
			'callback' => function( $user ) {
				return $user->user_login;
			}
		),
		'first_name' => array(
			'label'    => __( 'First Name', 'ct-user-list' ),
			'sortable' => true,
			'callback' => function( $user ) {
				$user_data  = get_userdata( $user->ID );
				$first_name = '';

				if ( ! empty( $user_data->first_name ) ) {
					$first_name = $user_data->first_name;
				}

				return $first_name;
			}
		),
		'role'       => array(
			'label'    => __( 'Role', 'ct-user-list' ),
			'callback' => function( $user ) {
				$role       = '';
				$roles      = ct_user_list_get_roles();
				$user_data  = get_userdata( $user->ID );

				if ( ! empty( $user_data->roles ) ) {
					$user_roles = get_userdata( $user->ID )->roles;

					foreach ( $user_roles as $user_role ) {
						if ( isset( $roles[ $user_role ] ) ) {
							$role .= ( $role ? ' ' : '' ) . $roles[ $user_role ];
						}
					}
				}

				return $role;
			}
		),
	);
}

add_action( 'wp_ajax_nopriv_ct_user_list_get_users', 'ct_user_list_ajax_get_users' );
add_action( 'wp_ajax_priv_ct_user_list_get_users', 'ct_user_list_ajax_get_users' );

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_script(
		'ct-user-list',
		plugins_url( 'public/js/ct-user-list.js', __FILE__ ),
		array( 'jquery' ),
		'1',
		true
	);
} );

add_action( 'init', function() {
	load_plugin_textdomain( 'ct-user-list', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
} );

add_shortcode( 'ct-user-list', function() {
	$html = '<div id="ct-user-list-container">';
	$html .= '<form class="ct-user-list-search" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">';
	$html .= '<select name="role">';
	$html .= '<option value="">';
	$html .= esc_html__( 'Select role...', 'ct-user-list' );
	$html .= '</option>';

	$roles = ct_user_list_get_roles();

	foreach ( $roles as $role => $label ) {
		$html .= '<option value="' . esc_attr( $role ) . '">';
		$html .= esc_html( $label );
		$html .= '</option>';
	}

	$html .= '</select>';
	$html .= '<input type="hidden" name="page" />';
	$html .= '<input type="hidden" name="order" />';
	$html .= '<input type="hidden" name="order_by" />';
	$html .= '<input type="hidden" name="action" value="ct_user_list_get_users" />';
	$html .= '<input type="hidden" name="nonce" value="' . esc_attr( wp_create_nonce( CT_USER_LIST_GET_USERS_NONCE ) ) . '" />';
	$html .= '</form>';
	$html .= '<div class="ct-user-list">';
	$html .= '</div>';
	$html .= '</div>';

	wp_enqueue_script( 'ct-user-list' );

	$fields = ct_user_list_get_fields();

	foreach ( $fields as $id => $field ) {
		$headers[ $id ] = array(
			'label'    => $field['label'],
			'sortable' => $field['sortable'],
		);
	}

	wp_localize_script( 'ct-user-list', 'ct_user_list_options', array(
		'data'    => ct_user_list_get_user_data(),
		'headers' => $headers,
		'labels'  => array(
			'notFound' => __( 'No users found', 'ct-user-list' ),
		),
	) );

	return $html;
} );
