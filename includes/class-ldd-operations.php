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

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_shortcode( 'ldd_operations_shortcode', array( __CLASS__, 'ldd_operations_shortcode' ) );
	}


	public static function admin_init() {
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

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
		add_action( 'edd_admin_sale_notice', array( __CLASS__, 'new_delivery_notice' ), 10, 2 );
	}


	public static function filters() {
		add_filter( 'ldd_sections', array( __CLASS__, 'sections' ) );
		add_filter( 'ldd_settings', array( __CLASS__, 'settings' ) );
		add_filter( 'edd_email_template_tags', array( __CLASS__, 'edd_email_template_tags' ), 10, 3 );
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
	public static function new_delivery_notice( $payment_id = 0, $payment_data = array() ) {
		/* Send an email notification to the admin */
		$admin_email    = ldd_get_option( 'notify', edd_get_admin_notice_emails() );
		$admin_email_cc = ldd_get_option( 'notify_cc' );

		$user_id   = edd_get_payment_user_id( $payment_id );
		$user_info = maybe_unserialize( $payment_data['user_info'] );
		if ( isset( $user_id ) && $user_id > 0 ) {
			$user_data = get_userdata($user_id);
			$name      = $user_data->display_name;
		} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
		} else {
			$name = $user_info['email'];
		}

		$admin_message  = edd_get_email_body_header();
		$admin_message .= self::new_delivery_body( $payment_id, $payment_data );
		$admin_message .= edd_get_email_body_footer();

		$admin_subject = ldd_get_option( 'new_delivery_subject' );
		$admin_subject = edd_do_email_tags( $admin_subject, $payment_id );
		$admin_subject = apply_filters( 'ldd_operations_new_delivery_subject', $admin_subject, $payment_id, $payment_data );

		$from_name  = self::get_edd_options( 'from_name', get_bloginfo( 'name' ) );
		$from_email = self::get_edd_options( 'from_email', get_option( 'admin_email' ) );

		$admin_headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$admin_headers .= "Reply-To: ". $from_email . "\r\n";
		$admin_headers .= "Content-Type: text/html; charset=utf-8\r\n";

		if ( ! empty( $admin_email_cc ) ) {
			$ccs = explode( ',', $admin_email_cc );
			foreach ( $ccs as $cc ) {
				$admin_headers .= "Cc: ". $cc . "\r\n";
			}
		}

		$admin_headers .= apply_filters( 'ldd_operations_new_delivery_headers', $admin_headers, $payment_id, $payment_data );

		$admin_attachments = apply_filters( 'ldd_operations_new_delivery_attachments', array(), $payment_id, $payment_data );

		wp_mail( $admin_email, $admin_subject, $admin_message, $admin_headers, $admin_attachments );
	}


	/**
	 * Delivery Template Body
	 *
	 * @param int $payment_id Payment ID
	 * @param array $payment_data Payment Data
	 * @return string $email_body Body of the email
	 */
	public static function new_delivery_body( $payment_id = 0, $payment_data = array() ) {
		$email      = ldd_get_option( 'new_delivery_body' );
		$email_body = edd_do_email_tags( $email, $payment_id );

		return apply_filters( 'ldd_operations_new_delivery_body', wpautop( $email_body ), $payment_id, $payment_data );
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
			'std' => esc_html__( 'LDD New Delivery #{delivery_id}' ),
		);

		$settings['new_delivery_body'] = array(
			'title' => esc_html__( 'Body' ),
			'type' => 'rich_editor',
			'section' => 'emails',
			'std' => self::new_delivery_body_content(),
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
			'{admin_delivery_record}
<hr />
<h1>New Delivery Request #{delivery_id}</h1>
Order #{receipt_id} - {admin_order_details}
{date}

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

{users_orders}
'
);

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

		$admin_delivery_record_url = self::get_delivery_url( $delivery_id );
		$admin_delivery_record     = self::get_delivery_link( $delivery_id );

		$admin_order_details_url = self::get_order_url( $payment_id );
		$admin_order_details     = self::get_order_link( $payment_id );

		$cart_items = self::pretty_print_cart_items( $payment_id );

		$payment_meta      = edd_get_payment_meta( $payment_id );
		$email             = $payment_meta['email'];
		$users_orders_text = __( 'View <a href="%1$s">user\'s orders</a>.' );
		$users_orders_url  = add_query_arg( 'user', $email, self::$payment_history_url );
		$users_orders      = sprintf( $users_orders_text, $users_orders_url );

		$message = str_replace( '{admin_delivery_record_url}', $admin_delivery_record_url, $message );
		$message = str_replace( '{admin_delivery_record}', $admin_delivery_record, $message );
		$message = str_replace( '{admin_order_details_url}', $admin_order_details_url, $message );
		$message = str_replace( '{admin_order_details}', $admin_order_details, $message );
		$message = str_replace( '{cart_items}', $cart_items, $message );
		$message = str_replace( '{delivery_id}', $delivery_id, $message );
		$message = str_replace( '{users_orders_url}', $users_orders_url, $message );
		$message = str_replace( '{users_orders}', $users_orders, $message );

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
				'desc' => '',
			),
			array(
				'name' => esc_html__( 'Order Receipt' ),
				'id' => 'order_date',
				'type' => 'datetime',
			),
			array(
				'name' => esc_html__( 'Last Update' ),
				'id' => 'last_update',
				'type' => 'datetime',
			),
			array(
				'name' => esc_html__( 'Time Elasped' ),
				'id' => 'time_elasped',
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
}

?>
