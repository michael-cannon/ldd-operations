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

	public static $post_id;


	public function __construct() {
		parent::__construct();

		self::$plugin_assets = plugins_url( '/assets/', dirname( __FILE__ ) );
		self::$plugin_assets = self::strip_protocol( self::$plugin_assets );

		self::actions();

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		// fixme add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_shortcode( 'ldd_operations_shortcode', array( __CLASS__, 'ldd_operations_shortcode' ) );
	}


	public static function admin_init() {
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		self::$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . LDD::PT . '&page=' . LDD_Settings::ID . '">' . __( 'Settings', 'ldd-operations' ) . '</a>';
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
		add_action( 'edd_admin_sale_notice', 'delivery_notice_initial', 10, 2 );
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
	 * Sends the initial delivery notice
	 *
	 * @param int $payment_id Payment ID (default: 0)
	 * @param array $payment_data Payment Meta and Data
	 * @return void
	 */
	function delivery_notice_initial( $payment_id = 0, $payment_data = array() ) {
		global $edd_options;

		/* Send an email notification to the admin */
		$admin_email = edd_get_admin_notice_emails();
		$user_id     = edd_get_payment_user_id( $payment_id );
		$user_info   = maybe_unserialize( $payment_data['user_info'] );

		if ( isset( $user_id ) && $user_id > 0 ) {
			$user_data = get_userdata($user_id);
			$name      = $user_data->display_name;
		} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
		} else {
			$name = $user_info['email'];
		}

		$admin_message  = edd_get_email_body_header();
		// fixme
		$admin_message .= self::delivery_notification_body_content( $payment_id, $payment_data );
		$admin_message .= edd_get_email_body_footer();

		// fixme
		if( ! empty( $edd_options['delivery_notification_subject'] ) ) {
			$admin_subject = wp_strip_all_tags( $edd_options['delivery_notification_subject'], true );
		} else {
			$admin_subject = sprintf( __( 'New Delivery Notice - Order #%1$s' ), $payment_id );
		}

		$admin_subject = edd_do_email_tags( $admin_subject, $payment_id );
		$admin_subject = apply_filters( 'ldd_operations_delivery_notification_subject', $admin_subject, $payment_id, $payment_data );

		$from_name  = isset( $edd_options['from_name'] )  ? $edd_options['from_name']  : get_bloginfo('name');
		$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option('admin_email');

		$admin_headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$admin_headers .= "Reply-To: ". $from_email . "\r\n";
		$admin_headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$admin_headers .= apply_filters( 'ldd_operations_delivery_notification_headers', $admin_headers, $payment_id, $payment_data );

		$admin_attachments = apply_filters( 'ldd_operations_delivery_notification_attachments', array(), $payment_id, $payment_data );

		wp_mail( $admin_email, $admin_subject, $admin_message, $admin_headers, $admin_attachments );
	}


	/**
	 * Delivery Template Body
	 *
	 * @param int $payment_id Payment ID
	 * @param array $payment_data Payment Data
	 * @return string $email_body Body of the email
	 */
	function delivery_notification_body_content( $payment_id = 0, $payment_data = array() ) {
		global $edd_options;

		$user_info = maybe_unserialize( $payment_data['user_info'] );
		$email     = edd_get_payment_user_email( $payment_id );

		if( isset( $user_info['id'] ) && $user_info['id'] > 0 ) {
			$user_data = get_userdata( $user_info['id'] );
			$name      = $user_data->display_name;
		} elseif( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
		} else {
			$name = $email;
		}

		$download_list = '';
		$downloads     = maybe_unserialize( $payment_data['downloads'] );

		if( is_array( $downloads ) ) {
			foreach( $downloads as $download ) {
				$id = isset( $payment_data['cart_details'] ) ? $download['id'] : $download;
				$title = get_the_title( $id );
				if( isset( $download['options'] ) ) {
					if( isset( $download['options']['price_id'] ) ) {
						$title .= ' - ' . edd_get_price_option_name( $id, $download['options']['price_id'], $payment_id );
					}
				}
				$download_list .= html_entity_decode( $title, ENT_COMPAT, 'UTF-8' ) . "\n";
			}
		}

		$gateway = edd_get_gateway_admin_label( get_post_meta( $payment_id, '_edd_payment_gateway', true ) );

		$default_email_body  = __( 'Hello', 'edd' ) . "\n\n" . sprintf( __( 'A %s purchase has been made', 'edd' ), edd_get_label_plural() ) . ".\n\n";
		$default_email_body .= sprintf( __( '%s sold:', 'edd' ), edd_get_label_plural() ) . "\n\n";
		$default_email_body .= $download_list . "\n\n";
		$default_email_body .= __( 'Purchased by: ', 'edd' ) . " " . html_entity_decode( $name, ENT_COMPAT, 'UTF-8' ) . "\n";
		$default_email_body .= __( 'Amount: ', 'edd' ) . " " . html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ), ENT_COMPAT, 'UTF-8' ) . "\n";
		$default_email_body .= __( 'Payment Method: ', 'edd' ) . " " . $gateway . "\n\n";
		$default_email_body .= __( 'Thank you', 'edd' );

		$email = isset( $edd_options['delivery_notification'] ) ? stripslashes( $edd_options['delivery_notification'] ) : $default_email_body;

		//$email_body = edd_email_template_tags( $email, $payment_data, $payment_id, true );
		$email_body = edd_do_email_tags( $email, $payment_id );

		return apply_filters( 'ldd_operations_delivery_notification', wpautop( $email_body ), $payment_id, $payment_data );
	}

}

?>
