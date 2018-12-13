<?php
/**
 * Plugin Name: Codeable Test User List
 * Description: Allows to print a list of users
 * Author:      Mart�n Di Felice
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

define( 'CT_USER_LIST_PAGE_LENGTH', 10 );
define( 'CT_USER_LIST_GET_USER_DATA_NONCE', 'ct-user-list-get-user-data-nonce' );

/**
 * Returns a list of users.
 *
 * @param array/string $options {
 *  Options to be considered when retrieving users from the database.
 *
 *  @type string $role     Roles should be retrieved. Default is any role.
 *  @type string $order_by Field to be used to order users.
 *  @type string $order    Type of order: ASC (ascending) or DESC (descending).
 *                         Default is ascending.
 *  @type int    $page     Page to retrieve. Default is one.
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

	$options    = wp_parse_args( $options, $defaults );
	$roles      = ct_user_list_get_roles();
	$fields     = ct_user_list_get_fields();
	$query_args = array(
		'number'      => CT_USER_LIST_PAGE_LENGTH,
		'count_total' => true,
	);

	if ( isset( $roles[ $options['role'] ] ) ) {
		$query_args['role'] = $options['role'];
	}

	if ( isset( $fields[ $options['order_by'] ] ) ) {
		$field = $fields[ $options['order_by'] ];

		if ( $field['order_field'] ) {
			$order_field = $field['order_field'];

			if ( preg_match( '/^meta_key=(.+)$/', $order_field, $matches ) ) {
				$order_by               = 'meta_value';
				$query_args['meta_key'] = $matches[1];
			} else {
				$order_by = $order_field;
			}

			$query_args['orderby'] = $order_by;
		}
	}

	if ( in_array( strtolower( $options['order'] ), array( 'asc', 'desc' ), true ) ) {
		$query_args['order'] = $options['order'];
	}

	$page = absint( $options['page'] );

	if ( ! $page ) {
		$page = $defaults['page'];
	}

	$query_args['paged'] = $page;

	$query   = new WP_User_Query( $query_args );
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
 * Handles the AJAX call for retrieving user data.
 */
function ct_user_list_ajax_get_user_data() {
	check_ajax_referer( CT_USER_LIST_GET_USER_DATA_NONCE );

	$options             = array();
	$whitelisted_options = array(
		'role',
		'order',
		'order_by',
		'page',
	);

	foreach ( $whitelisted_options as $whitelisted_option ) {
		if ( isset( $_POST[ $whitelisted_option ] ) ) {
			$value = $_POST[ $whitelisted_option ];

			if ( is_string( $value ) ) {
				$options[ $whitelisted_option ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
	}

	$users = ct_user_list_get_user_data( $options );

	wp_send_json( $users );
}

/**
 * Returns a list of user roles and their descriptions.
 *
 * @return array User roles.
 */
function ct_user_list_get_roles() {
	$all_roles = WP_Roles()->roles;

	foreach ( $all_roles as $role => $details ) {
		$roles[ $role ] = translate_user_role( $details['name'] );
	}

	return $roles;
}

/**
 * Returns the list of fields that will be displayed in the shortcode.
 *
 * @return array List of fields.
 */
function ct_user_list_get_fields() {
	return array(
		'username'   => array(
			'label'       => __( 'Username', 'ct-user-list' ),
			'order_field' => 'username',
			'callback'    => function( $user ) {
				return $user->user_login;
			},
		),
		'first_name' => array(
			'label'       => __( 'First Name', 'ct-user-list' ),
			'order_field' => 'meta_key=first_name',
			'callback'    => function( $user ) {
				$user_data  = get_userdata( $user->ID );
				$first_name = '';

				if ( ! empty( $user_data->first_name ) ) {
					$first_name = $user_data->first_name;
				}

				return $first_name;
			},
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
			},
		),
	);
}

add_action( 'wp_ajax_nopriv_ct_user_list_get_user_data', 'ct_user_list_ajax_get_user_data' );
add_action( 'wp_ajax_ct_user_list_get_user_data', 'ct_user_list_ajax_get_user_data' );

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_register_script(
			'ct-user-list',
			plugins_url( 'public/js/ct-user-list.js', __FILE__ ),
			array( 'jquery' ),
			'1',
			true
		);

		wp_register_style(
			'ct-user-list',
			plugins_url( 'public/css/ct-user-list.css', __FILE__ ),
			array(),
			'1'
		);
	}
);

add_action(
	'init',
	function() {
		if ( ! is_admin() ) {
			load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo' );
		}

		load_plugin_textdomain( 'ct-user-list', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
);

add_shortcode(
	'ct_user_list',
	function() {
		$html  = '<div id="ct-user-list-container">';
		$html .= '<form class="ct-user-list-search" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">';
		$html .= '<select name="role">';
		$html .= '<option value="">';
		$html .= esc_html__( 'Any role', 'ct-user-list' );
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
		$html .= '<input type="hidden" name="action" value="ct_user_list_get_user_data" />';
		$html .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( CT_USER_LIST_GET_USER_DATA_NONCE ) ) . '" />';
		$html .= '</form>';
		$html .= '<div class="ct-user-list">';
		$html .= '</div>';
		$html .= '</div>';

		$fields = ct_user_list_get_fields();

		foreach ( $fields as $id => $field ) {
			$headers[ $id ] = array(
				'label'    => $field['label'],
				'sortable' => ! empty( $field['order_field'] ),
			);
		}

		wp_enqueue_style( 'ct-user-list' );

		wp_enqueue_script( 'ct-user-list' );

		wp_localize_script(
			'ct-user-list',
			'ct_user_list_options',
			array(
				'data'        => ct_user_list_get_user_data(),
				'headers'     => $headers,
				'page_length' => CT_USER_LIST_PAGE_LENGTH,
				'labels'      => array(
					'noUsersFound' => __( 'No users found', 'ct-user-list' ),
					'previousPage' => __( 'Previous page', 'ct-user-list' ),
					'nextPage'     => __( 'Next page', 'ct-user-list' ),
				),
			)
		);

		return $html;
	}
);
