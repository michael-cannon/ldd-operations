<?php
/**
 * Copyright 2014 Michael Cannon (email: mc@aihr.us)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if ( class_exists( 'LDD_Operations' ) )
	return;


class LDD_Operations extends Aihrus_Common {
	const BASE    = LDD_OPERATIONS_BASE;
	const ID      = 'ldd-operations';
	const SLUG    = 'ldd_operations_';
	const VERSION = LDD_OPERATIONS_VERSION;

	public static $class = __CLASS__;
	public static $menu_id;
	public static $notice_key;
	public static $plugin_assets;
	public static $scripts = array();
	public static $settings_link;
	public static $styles        = array();
	public static $styles_called = false;

	private static $pre_save_data;

	public static $delivery_record_url;
	public static $payment_history_url;


	public function __construct() {
		parent::__construct();

		self::$plugin_assets = plugins_url( '/assets/', dirname( __FILE__ ) );
		self::$plugin_assets = self::strip_protocol( self::$plugin_assets );

		self::$delivery_record_url = admin_url( 'post.php?action=edit' );
		self::$payment_history_url = admin_url( 'edit.php?post_type=download&page=edd-payment-history' );

		self::actions();
		self::filters();

		add_shortcode( 'ldd_operations_shortcode', array( __CLASS__, 'ldd_operations_shortcode' ) );
	}


	public static function admin_init() {
		self::$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . LDD::PT . '&page=' . LDD_Settings::ID . '">' . __( 'Settings', 'ldd-operations' ) . '</a>';

		self::add_agent_meta_box();
	}


	public static function admin_menu() {
		self::$menu_id = add_management_page( esc_html__( 'Legal Document Deliveries - Operations Processor', 'ldd-operations' ), esc_html__( 'Legal Document Deliveries - Operations Processor', 'ldd-operations' ), 'manage_options', self::ID, array( __CLASS__, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( __CLASS__, 'styles' ) );
	}


	public static function init() {
		load_plugin_textdomain( self::ID, false, 'ldd-operations/languages' );

		if ( LDD::do_load() ) {
			self::styles();
		}
	}


	public static function actions() {
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'edd_admin_sale_notice', array( __CLASS__, 'notice_new_delivery' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 20 );
	}


	public static function filters() {
		add_filter( 'edd_email_template_tags', array( __CLASS__, 'edd_email_template_tags' ), 10, 3 );
		add_filter( 'ldd_sections', array( __CLASS__, 'sections' ) );
		add_filter( 'ldd_settings', array( __CLASS__, 'settings' ) );
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'current_delivery_data' ), 10, 2 );
	}


	public static function plugin_action_links( $links, $file ) {
		if ( self::BASE == $file ) {
			array_unshift( $links, self::$settings_link );

			// fixme $link = '<a href="' . get_admin_url() . 'tools.php?page=' . self::ID . '">' . esc_html__( 'Process', 'ldd-operations' ) . '</a>';
			// fixme array_unshift( $links, $link );
		}

		return $links;
	}


	public static function activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		global $wpdb;

		require_once LDD_OPERATIONS_DIR_INC . 'class-ldd-operations-settings.php';

		$delete_data = ldd_get_option( 'delete_data', false );
		if ( $delete_data ) {
			delete_option( LDD_Settings::ID );
			$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );
		}
	}


	public static function plugin_row_meta( $input, $file ) {
		if ( self::BASE != $file )
			return $input;

		$disable_donate = ldd_get_option( 'disable_donate', true );
		if ( $disable_donate )
			return $input;

		$links = array(
			self::$donate_link,
		);

		$input = array_merge( $input, $links );

		return $input;
	}


	public static function notice_0_0_1() {
		$text = sprintf( __( 'If your Legal Document Deliveries - Operations display has gone to funky town, please <a href="%s">read the FAQ</a> about possible CSS fixes.', 'ldd-operations' ), 'https://aihrus.zendesk.com/entries/23722573-Major-Changes-Since-2-10-0' );

		aihr_notice_updated( $text );
	}


	public static function scripts( $atts = array() ) {
		if ( is_admin() ) {
			wp_enqueue_script( 'jquery' );

			// fixme wp_register_script( 'jquery-ui-progressbar', self::$plugin_assets . 'js/jquery.ui.progressbar.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget' ), '1.10.3' );
			// fixme wp_enqueue_script( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'LDD_Operations', 'get_scripts' ) );
		} else {
			add_action( 'wp_footer', array( 'LDD_Operations', 'get_scripts' ) );
		}

		do_action( 'ldd_operations_scripts', $atts );
	}


	public static function styles() {
		if ( is_admin() ) {
			// fixme wp_register_style( 'jquery-ui-progressbar', self::$plugin_assets . 'css/redmond/jquery-ui-1.10.3.custom.min.css', false, '1.10.3' );
			// fixme wp_enqueue_style( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'LDD_Operations', 'get_styles' ) );
		} else {
			wp_register_style( __CLASS__, self::$plugin_assets . 'css/ldd-operations.css' );
			wp_enqueue_style( __CLASS__ );

			add_action( 'wp_footer', array( 'LDD_Operations', 'get_styles' ) );
		}

		do_action( 'ldd_operations_styles' );
	}


	public static function ldd_operations_shortcode( $atts ) {
		self::call_scripts_styles( $atts );

		return __CLASS__ . ' shortcode';
	}


	public static function version_check() {
		$good_version = true;

		return $good_version;
	}


	public static function call_scripts_styles( $atts ) {
		self::scripts( $atts );
	}


	public static function get_scripts() {
		if ( empty( self::$scripts ) )
			return;

		foreach ( self::$scripts as $script )
			echo $script;
	}


	public static function get_styles() {
		if ( empty( self::$styles ) )
			return;

		if ( empty( self::$styles_called ) ) {
			echo '<style>';

			foreach ( self::$styles as $style )
				echo $style;

			echo '</style>';

			self::$styles_called = true;
		}
	}


	/**
	 * Sends the new delivery notice
	 *
	 * @param int $payment_id Payment ID (default: 0)
	 * @param array $payment_data Payment Meta and Data
	 * @return void
	 */
	public static function notice_new_delivery( $payment_id = 0, $payment_data = array() ) {
		// Send new delivery notification to admin
		$delivery_id = get_post_meta( $payment_id, LDD_Ordering::KEY_DELIVERY_ID, true );
		$part        = 'new_delivery';
		self::notice_mailer( $delivery_id, $part );
	   
		do_action( 'ldd_operations_notice_' . $part , $delivery_id );
	}


	public static function sections( $sections ) {
		$sections['emails'] = esc_html__( 'Emails' );

		return $sections;
	}


	public static function settings( $settings ) {
		$settings['notify'] = array(
			'title' => esc_html__( 'Notification Email' ),
			'desc' => esc_html__( 'Central email address to send delivery notifications to.' ),
			'validate' => 'email',
			'std' => 'mc+test@aihr.us',
		);

		$settings['notify_cc'] = array(
			'title' => esc_html__( 'Notification Cc Email' ),
			'validate' => 'email',
			'desc' => esc_html__( 'Separate email addresses using a comma.' ),
			'std' => 'legaldocumentdeliveries+test@gmail.com',
		);

		$settings['new_delivery_heading'] = array(
			'desc' => esc_html__( 'New Delivery Notification' ),
			'type' => 'heading',
			'section' => 'emails',
		);

		$settings['new_delivery_subject'] = array(
			'title' => esc_html__( 'Subject' ),
			'section' => 'emails',
			'std' => esc_html__( 'LDD Delivery #{delivery_id}: New Delivery' ),
		);

		$settings['new_delivery_body'] = array(
			'title' => esc_html__( 'Body' ),
			'type' => 'rich_editor',
			'section' => 'emails',
			'std' => self::new_delivery_body_content(),
		);

		$settings['assign_agent_heading'] = array(
			'desc' => esc_html__( 'Assign Agent Notification' ),
			'type' => 'heading',
			'section' => 'emails',
		);

		$settings['assign_agent_subject'] = array(
			'title' => esc_html__( 'Subject' ),
			'section' => 'emails',
			'std' => esc_html__( 'LDD Delivery #{delivery_id}: Agent Assigned' ),
		);

		$settings['assign_agent_body'] = array(
			'title' => esc_html__( 'Body' ),
			'type' => 'rich_editor',
			'section' => 'emails',
			'std' => self::assign_agent_body_content(),
		);

		$settings['status_change_heading'] = array(
			'desc' => esc_html__( 'Status Change Notification' ),
			'type' => 'heading',
			'section' => 'emails',
		);

		$settings['status_change_subject'] = array(
			'title' => esc_html__( 'Subject' ),
			'section' => 'emails',
			'std' => esc_html__( 'LDD Delivery #{delivery_id}: Status Change' ),
		);

		$settings['status_change_body'] = array(
			'title' => esc_html__( 'Body' ),
			'type' => 'rich_editor',
			'section' => 'emails',
			'std' => self::status_change_body_content(),
		);

		return $settings;
	}


	public static function get_edd_options( $key = null, $default = null ) {
		$edd_options = edd_get_settings();

		if ( is_null( $key ) )
			return $edd_options;
		elseif ( isset( $edd_options[ self::SLUG . $key ] ) )
			return $edd_options[ self::SLUG . $key ];
		elseif ( isset( $edd_options[ $key ] ) )
			return $edd_options[ $key ];
		else
			return $default;
	}


	public static function new_delivery_body_content() {
		$content = __(
			'{admin_delivery_link}
<hr />
<h1>Delivery #{delivery_id}: New Delivery</h1>
'
);
		$content .= self::core_body_content();

		return $content;
	}


	public static function pretty_print_cart_items( $payment_id ) {
		$html       = '';
		$cart_items = edd_get_payment_meta_cart_details( $payment_id );
		if ( empty( $cart_items ) )
			return $html;

		foreach ( $cart_items as $item ) {
			$link = self::create_link( $item['id'] );
			if ( empty( $link ) )
				continue;

			$html .= '<li>';
			$html .= $link;
			$html .= '</li>';
		}

		if ( ! empty( $html ) )
			$html = '<ul>' . $html . '</ul>';

		return $html;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.LongVariable)
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function edd_email_template_tags( $message, $payment_data, $payment_id ) {
		$delivery_id = get_post_meta( $payment_id, LDD_Ordering::KEY_DELIVERY_ID, true );
		if ( is_null( $delivery_id ) ) {
			$delivery_id = $payment_id;
			$payment_id  = get_post_meta( $delivery_id, LDD_Ordering::KEY_PAYMENT_ID, true );
		}

		$admin_delivery_url  = self::get_delivery_url( $delivery_id );
		$admin_delivery_link = self::get_delivery_link( $delivery_id );

		$admin_order_url  = self::get_order_url( $payment_id );
		$admin_order_link = self::get_order_link( $payment_id );

		$cart_items = self::pretty_print_cart_items( $payment_id );

		$delivery            = get_post( $delivery_id );
		$status              = $delivery->post_status;
		$text                = esc_html__( 'Status: %s' );
		$ldd_delivery_status = sprintf( $text, $status );

		$agent_id = get_post_meta( $delivery_id, 'agent', true );
		$text     = esc_html__( 'Agent: %s' );
		$agent    = esc_html__( 'None assigned' );
		if ( ! empty( $agent_id ) ) {
			$agent_info = get_userdata( $agent_id );
			$agent      = $agent_info->display_name;
		}

		$ldd_delivery_agent = sprintf( $text, $agent );

		$ldd_delivery_progress = $ldd_delivery_status;
		$ldd_delivery_progress .= "\n";
		$ldd_delivery_progress .= $ldd_delivery_agent;

		$payment_meta      = edd_get_payment_meta( $payment_id );
		$email             = $payment_meta['email'];
		$users_orders_text = __( 'View <a href="%1$s">user\'s orders</a>.' );
		$users_orders_url  = add_query_arg( 'user', $email, self::$payment_history_url );
		$users_orders_link = sprintf( $users_orders_text, $users_orders_url );

		$message = str_replace( '{admin_delivery_link}', $admin_delivery_link, $message );
		$message = str_replace( '{admin_delivery_url}', $admin_delivery_url, $message );
		$message = str_replace( '{admin_order_link}', $admin_order_link, $message );
		$message = str_replace( '{admin_order_url}', $admin_order_url, $message );
		$message = str_replace( '{cart_items}', $cart_items, $message );
		$message = str_replace( '{delivery_id}', $delivery_id, $message );
		$message = str_replace( '{ldd_delivery_progress}', $ldd_delivery_progress, $message );
		$message = str_replace( '{users_orders_link}', $users_orders_link, $message );
		$message = str_replace( '{users_orders_url}', $users_orders_url, $message );

		return $message;
	}


	public static function get_order_link( $payment_id ) {
		$order_link = __( 'View <a href="%1$s">order details</a>.' );
		$order_url  = self::get_order_url( $payment_id );
		$order_link = sprintf( $order_link, $order_url );

		return $order_link;
	}


	public static function get_order_url( $payment_id ) {
		$link_base = self::$payment_history_url . '&view=view-order-details';
		$link      = add_query_arg( 'id', $payment_id, $link_base );

		return $link;
	}


	public static function get_delivery_link( $delivery_id ) {
		$delivery_link = __( 'View <a href="%1$s">delivery record</a>.' );
		$delivery_url  = self::get_delivery_url( $delivery_id );
		$delivery_link = sprintf( $delivery_link, $delivery_url );

		return $delivery_link;
	}


	public static function get_delivery_url( $delivery_id ) {
		$link_base = self::$delivery_record_url;
		$link      = add_query_arg( 'post', $delivery_id, $link_base );

		return $link;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public static function add_agent_meta_box() {
		$fields = array(
			array(
				'name' => esc_html__( 'Delivery Agent' ),
				'id' => 'agent',
				'type' => 'select_users',
			),
			array(
				'name' => esc_html__( 'Order Receipt' ),
				'id' => 'order_date',
				'type' => 'datetime',
			),
			array(
				'name' => esc_html__( 'Time Since Ordered' ),
				'id' => 'time_since_order',
				'type' => 'ldd_operations_time_elasped_current',
			),
			array(
				'name' => esc_html__( 'Last Update' ),
				'id' => 'last_update',
				'type' => 'datetime',
			),
			array(
				'name' => esc_html__( 'Time Since Last Update' ),
				'id' => 'time_since_update',
				'type' => 'ldd_operations_time_elasped',
			),
		);

		$fields = apply_filters( 'ldd_operations_agent_meta_box', $fields );

		$meta_box = redrokk_metabox_class::getInstance(
			self::ID . '-agent',
			array(
				'title' => esc_html__( 'Delivery Handling' ),
				'description' => '',
				'_object_types' => LDD::PT,
				'context' => 'side',
				'_fields' => $fields,
			)
		);
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function save_post( $delivery_id ) {
		$post_type = get_post_type( $delivery_id );
		if ( ! in_array( $post_type, array( LDD::PT ) ) )
			return;

		if ( ! current_user_can( 'edit_post', $delivery_id ) )
			return;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( 'revision' == $post_type )
			return;

		if ( isset( $_POST[ self::ID ] ) && ! wp_verify_nonce( $_POST[ self::ID ], self::BASE ) )
			return;

		remove_action( 'save_post', array( __CLASS__, 'save_post' ), 20 );

		// check for change in agent
		$agent_before = 0;
		if ( isset( self::$pre_save_data['agent'][0] ) ) {
			$agent_before = intval( self::$pre_save_data['agent'][0] );
		}

		$agent_now = 0;
		if ( isset( $_POST['agent'] ) ) {
			$agent_now = intval( $_POST['agent'] );
		}

		$notice_assign_agent = false;
		if ( $agent_before != $agent_now ) {
			$agent_before_data = get_userdata( $agent_before );
			$agent_before_name = $agent_before_data->display_name;

			$agent_now_data = get_userdata( $agent_now );
			$agent_now_name = $agent_now_data->display_name;

			$text = esc_html__( 'Agent changed from %1$s to %2$s' ); 
			$note = sprintf( $text, $agent_before_name, $agent_now_name );
			LDD::insert_delivery_note( $delivery_id, $note );

			$notice_assign_agent = true;
		}

		// check for change in status
		$status_before = false;
		if ( isset( self::$pre_save_data['post_status'] ) ) {
			$status_before = self::$pre_save_data['post_status'];
		}

		$status_now = false;
		if ( isset( $_POST['post_status'] ) ) {
			$status_now = $_POST['post_status'];
		}

		$notice_status_change = false;
		if ( $status_before != $status_now ) {
			$text = esc_html__( 'Status changed from %1$s to %2$s' ); 
			$note = sprintf( $text, $status_before, $status_now );
			LDD::insert_delivery_note( $delivery_id, $note );

			$notice_status_change = true;
		}

		if ( $notice_status_change ) {
			self::notice_status_change( $delivery_id );
		} elseif ( $notice_assign_agent ) {
			self::notice_assign_agent( $delivery_id );
		}

		// update last_update entry
		update_post_meta( $delivery_id, 'last_update', current_time( 'mysql' ) );
	}


	public static function notice_assign_agent( $delivery_id ) {
		// admin
		$part = 'assign_agent';
		self::notice_mailer( $delivery_id, $part );

		$to = 'agent';
		$cc = false;
		self::notice_mailer( $delivery_id, $part, $to, $cc );
	   
		if ( false ) {
			$to = 'client';
			$cc = 'shared';
			self::notice_mailer( $delivery_id, $part, $to, $cc );
		}
	   
		do_action( 'ldd_operations_notice_' . $part , $delivery_id );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function current_delivery_data( $data , $postarr ) {
		$delivery_id = isset( $data['post_ID'] ) ? $data['post_ID'] : get_the_ID();
		$post_type   = get_post_type( $delivery_id );
		if ( ! in_array( $post_type, array( LDD::PT ) ) )
			return $data;

		if ( ! current_user_can( 'edit_post', $delivery_id ) )
			return $data;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $data;

		if ( 'revision' == $post_type )
			return $data;

		if ( isset( $_POST[ self::ID ] ) && ! wp_verify_nonce( $_POST[ self::ID ], self::BASE ) )
			return $data;

		self::$pre_save_data                = get_post_custom( $delivery_id );
		self::$pre_save_data['post_status'] = get_post_status( $delivery_id );
		
		return $data;
	}


	public static function notice_status_change( $delivery_id ) {
		// admin
		$part = 'status_change';
		self::notice_mailer( $delivery_id, $part );

		$to = 'agent';
		$cc = false;
		self::notice_mailer( $delivery_id, $part, $to, $cc );
	   
		$delivery = get_post( $delivery_id );
		$status   = $delivery->post_status;
		if ( 'publish' == $status ) {
			$to = 'client';
			$cc = 'shared';
			self::notice_mailer( $delivery_id, $part, $to, $cc );
		}
	   
		do_action( 'ldd_operations_notice_' . $part , $delivery_id );
	}


	public static function time_elasped( $delivery_id, $status_diff = true  ) {
		$order_date  = get_post_meta( $delivery_id, 'order_date', true );

		if ( $status_diff )
			$last_update = get_post_meta( $delivery_id, 'last_update', true );
		else
			$last_update = current_time( 'mysql' );

		$time_diff = human_time_diff( strtotime( $order_date ), strtotime( $last_update ) );

		return $time_diff;
	}


	public static function get_message_body( $delivery_id, $part = 'new_delivery_body' ) {
		$payment_id = get_post_meta( $delivery_id, LDD_Ordering::KEY_PAYMENT_ID, true );

		$email      = ldd_get_option( $part, '' );
		$email_body = edd_do_email_tags( $email, $payment_id );

		$body  = edd_get_email_body_header();
		$body .= apply_filters( 'ldd_operations_message_' . $part, wpautop( $email_body ), $delivery_id );
		$body .= edd_get_email_body_footer();

		return $body;
	}


	public static function get_message_subject( $delivery_id, $part = 'new_delivery_subject' ) {
		$payment_id = get_post_meta( $delivery_id, LDD_Ordering::KEY_PAYMENT_ID, true );

		$subject_part = ldd_get_option( $part, '' );

		$subject = edd_do_email_tags( $subject_part, $payment_id );
		$subject = apply_filters( 'ldd_operations_message_' . $part, $subject, $delivery_id );

		return $subject;
	}


	public static function get_admin_email() {
		return ldd_get_option( 'notify', edd_get_admin_notice_emails() );
	}


	public static function get_admin_email_cc() {
		$ccs = ldd_get_option( 'notify_cc' );
		if ( empty( $ccs ) )
			return;

		$headers = self::get_email_ccs( $ccs );

		return $headers;
	}


	public static function get_email_ccs( $cc_list ) {
		$headers = '';

		if ( false !== strstr( $cc_list, ',' ) )
			$ccs = explode( ',', $cc_list );
		else
			$ccs = explode( "\n", $cc_list );

		foreach ( $ccs as $cc ) {
			if ( Aihrus_Settings::validate_email( $cc ) )
				$headers .= "Cc: ". $cc . "\r\n";
		}

		return $headers;
	}


	public static function get_from_name() {
		return self::get_edd_options( 'from_name', get_bloginfo( 'name' ) );
	}


	public static function get_from_email() {
		return self::get_edd_options( 'from_email', get_option( 'admin_email' ) );
	}


	public static function get_email_headers_from() {
		$from_name  = self::get_from_name();
		$from_email = self::get_from_email();

		$headers  = 'From: ' . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . ' <' . $from_email . '>' . "\r\n";
		$headers .= 'Reply-To: ' . $from_email . "\r\n";

		return $headers;
	}


	public static function get_email_headers_html() {
		$headers = 'Content-Type: text/html; charset=utf-8' . "\r\n";

		return $headers;
	}


	public static function notice_mailer( $delivery_id, $part, $to = 'admin', $cc = 'admin' ) {
		if ( 'admin' == $to ) {
			$to = self::get_admin_email();
		} elseif ( 'agent' == $to ) {
			$da_id   = get_post_meta( $delivery_id, 'agent', true );
			$da_data = get_userdata( $da_id );
			$to      = $da_data->user_email;
		} elseif ( 'client' == $to ) {
			$delivery    = get_post( $delivery_id );
			$client_id   = $delivery->post_author;
			$client_data = get_userdata( $client_id );
			$to          = $client_data->user_email;
		}
		
		if ( ! Aihrus_Settings::validate_email( $to ) ) {
			$text = esc_html__( 'To email address doesn\'t validate: %1$s. "%2$s" failed to be sent.' ); 
			$note = sprintf( $text, $to, $part );
			LDD::insert_delivery_note( $delivery_id, $note );

			return false;
		}

		$admin_subject = self::get_message_subject( $delivery_id, $part . '_subject' );
		$admin_message = self::get_message_body( $delivery_id, $part . '_body' );

		$admin_headers  = self::get_email_headers_from();
		$admin_headers .= self::get_email_headers_html();
		if ( 'admin' == $cc )
			$admin_headers .= self::get_admin_email_cc();
		elseif ( 'shared' == $cc )
			$admin_headers .= self::get_email_cc( $cc );

		$admin_headers = apply_filters( 'ldd_operations_' . $part . '_headers', $admin_headers, $delivery_id );

		$admin_attachments = apply_filters( 'ldd_operations_' . $part . '_attachments', array(), $delivery_id );

		$mailed = wp_mail( $to, $admin_subject, $admin_message, $admin_headers, $admin_attachments );
		if ( ! $mailed ) {
			$text = esc_html__( 'Mail wasn\'t sent: %7$s %6$s %1$s %6$s %2$s %6$s %3$s %6$s %4$s %6$s %5$s' );
			$note = sprintf( $text, $to, $admin_subject, $admin_message, $admin_headers, $admin_attachments, "\n\n\n", print_r( $mailed, true ) );
			LDD::insert_delivery_note( $delivery_id, $note );
		}

		return $mailed;
	}


	public static function get_email_cc( $delivery_id ) {
		$ccs = get_post_meta( $delivery_id, 'shared_notification', true );
		if ( empty( $ccs ) )
			return;

		$headers = self::get_email_ccs( $ccs );

		return $headers;
	}


	public static function assign_agent_body_content() {
		$content = __(
			'{admin_delivery_link}
<hr />
<h1>Delivery #{delivery_id}: Agent Assigned</h1>
'
);
		$content .= self::core_body_content();

		return $content;
	}


	public static function status_change_body_content() {
		$content = __(
			'{admin_delivery_link}
<hr />
<h1>Delivery #{delivery_id}: Status Change</h1>
'
);
		$content .= self::core_body_content();

		return $content;
	}


	public static function core_body_content() {
		$content = __(
			'
Order #{receipt_id} - {admin_order_link}
{date}

<h2>Delivery Progress</h2>
{ldd_delivery_progress}

<h2>Service Purchased</h2>
{cart_items}

<h2>Delivery Information</h2>
{ldd_delivery_details}

<h2>Client Information</h2>
{ldd_company}
{fullname}
{ldd_job_title}
{ldd_telephone}
{user_email}

{users_orders_link}
'
);

		return $content;
	}
}

?>
