<?php

/**
 * Class WC_Klarna_KPM
 *
 * The payment method merges invoice, account and special campaign payment methods.
 *
 * @class     WC_Klarna_PMS
 * @version   1.0
 * @since     2.0
 * @category  Class
 * @author    Krokedil
 * @package WC_Gateway_Klarna
 */

/**
 * Class for Klarna Account payment.
 */
class WC_Gateway_Klarna_KPM extends WC_Gateway_Klarna {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $woocommerce;
		
		parent::__construct();
		
		$this->id = 'klarna_kpm';
		$this->method_title = __( 'Klarna Payment Methods (KPM)', 'klarna' );
		$this->has_fields = true;
		$this->order_button_text = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
				
		// Klarna warning banner - used for NL only
		$klarna_wb_img_checkout = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm1-jpg.ashx';
		$klarna_wb_img_single_product = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';
		$klarna_wb_img_product_list = 'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Load shortcodes. 
		// This is used so that the merchant easily can modify the displayed monthly 
		// cost text (on single product and shop page) via the settings page.
		include_once( KLARNA_DIR . 'classes/class-klarna-shortcodes.php' );

		// Define user set variables
		$this->enabled =
			( isset( $this->settings['enabled'] ) ) ? $this->settings['enabled'] : '';
		$this->title =
			( isset( $this->settings['title'] ) ) ? $this->settings['title'] : '';
		$this->description =
			( isset( $this->settings['description'] ) ) ? $this->settings['description'] : '';

		$this->eid_se =
			( isset( $this->settings['eid_se'] ) ) ? $this->settings['eid_se'] : '';
		$this->secret_se = 
			( isset( $this->settings['secret_se'] ) ) ? $this->settings['secret_se'] : '';

		$this->eid_no = 
			( isset( $this->settings['eid_no'] ) ) ? $this->settings['eid_no'] : '';
		$this->secret_no = 
			( isset( $this->settings['secret_no'] ) ) ? $this->settings['secret_no'] : '';

		$this->eid_fi = 
			( isset( $this->settings['eid_fi'] ) ) ? $this->settings['eid_fi'] : '';
		$this->secret_fi = 
			( isset( $this->settings['secret_fi'] ) ) ? $this->settings['secret_fi'] : '';

		$this->eid_dk = 
			( isset( $this->settings['eid_dk'] ) ) ? $this->settings['eid_dk'] : '';
		$this->secret_dk = 
			( isset( $this->settings['secret_dk'] ) ) ? $this->settings['secret_dk'] : '';

		$this->eid_de = 
			( isset( $this->settings['eid_de'] ) ) ? $this->settings['eid_de'] : '';
		$this->secret_de = 
			( isset( $this->settings['secret_de'] ) ) ? $this->settings['secret_de'] : '';

		$this->eid_nl = 
			( isset( $this->settings['eid_nl'] ) ) ? $this->settings['eid_nl'] : '';
		$this->secret_nl = 
			( isset( $this->settings['secret_nl'] ) ) ? $this->settings['secret_nl'] : '';

		$this->eid_at = 
			( isset( $this->settings['eid_at'] ) ) ? $this->settings['eid_at'] : '';
		$this->secret_at = 
			( isset( $this->settings['secret_at'] ) ) ? $this->settings['secret_at'] : '';


		$this->lower_threshold = 
			( isset( $this->settings['lower_threshold'] ) ) ? $this->settings['lower_threshold'] : '';
		$this->upper_threshold = 
			( isset( $this->settings['upper_threshold'] ) ) ? $this->settings['upper_threshold'] : '';
		$this->show_monthly_cost = 
			( isset( $this->settings['show_monthly_cost'] ) ) ? $this->settings['show_monthly_cost'] : '';
		$this->show_monthly_cost_prio = 
			( isset( $this->settings['show_monthly_cost_prio'] ) ) ? $this->settings['show_monthly_cost_prio'] : '15';

		$this->testmode = 
			( isset( $this->settings['testmode'] ) ) ? $this->settings['testmode'] : '';
		$this->de_consent_terms = 
			( isset( $this->settings['de_consent_terms'] ) ) ? $this->settings['de_consent_terms'] : '';
		$this->lower_threshold_monthly_cost = 
			( isset( $this->settings['lower_threshold_monthly_cost'] ) ) ? $this->settings['lower_threshold_monthly_cost'] : '';
		$this->upper_threshold_monthly_cost = 
			( isset( $this->settings['upper_threshold_monthly_cost'] ) ) ? $this->settings['upper_threshold_monthly_cost'] : '';
		$this->ship_to_billing_address = 
			( isset( $this->settings['ship_to_billing_address'] ) ) ? $this->settings['ship_to_billing_address'] : '';

		if ( $this->lower_threshold_monthly_cost == '' ) $this->lower_threshold_monthly_cost = 0;
		if ( $this->upper_threshold_monthly_cost == '' ) $this->upper_threshold_monthly_cost = 10000000;	

		// Authorized countries
		$this->authorized_countries = array();
		if ( ! empty( $this->eid_se ) ) {
			$this->authorized_countries[] = 'SE';
		}
		if ( ! empty( $this->eid_no ) ) {
			$this->authorized_countries[] = 'NO';
		}
		if ( ! empty( $this->eid_fi ) ) {
			$this->authorized_countries[] = 'FI';
		}
		if ( ! empty( $this->eid_dk ) ) {
			$this->authorized_countries[] = 'DK';
		}
		if ( ! empty( $this->eid_de ) ) {
			$this->authorized_countries[] = 'DE';
		}
		if ( ! empty( $this->eid_nl ) ) {
			$this->authorized_countries[] = 'NL';
		}
		
		$klarna_basic_icon = '';
		$klarna_account_info = '';

		// Define Klarna object
		require_once( KLARNA_LIB . 'Klarna.php' );
		$this->klarna = new Klarna();

		// Test mode or Live mode		
		if ( $this->testmode == 'yes' ) {
			// Disable SSL if in testmode
			$this->klarna_ssl = 'false';
			$this->klarna_mode = Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$this->klarna_ssl = 'true';
			} else {
				$this->klarna_ssl = 'false';
			}
			$this->klarna_mode = Klarna::LIVE;
		}

		// Apply filters to Country and language
		$this->klarna_account_info = apply_filters( 'klarna_kpm_info', $klarna_account_info );
		$this->icon = apply_filters( 'klarna_kpm_icon', $this->get_account_icon() );	
		$this->icon_basic = apply_filters( 'klarna_basic_icon', $klarna_basic_icon );
		$this->klarna_wb_img_checkout = apply_filters( 'klarna_wb_img_checkout', $klarna_wb_img_checkout );
		$this->klarna_wb_img_single_product = apply_filters( 'klarna_wb_img_single_product', $klarna_wb_img_single_product );
		$this->klarna_wb_img_product_list = apply_filters( 'klarna_wb_img_product_list', $klarna_wb_img_product_list );
				
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_klarna_account', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'klarna_account_checkout_field_process' ) );
		// add_action( 'wp_print_footer_scripts', array(  $this, 'footer_scripts' ) );
		
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {

	   	$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Payment Methods (KPM)', 'klarna' ), 
				'default' => 'no'
			), 
			'title' => array(
				'title' => __( 'Title', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'This controls the title which the user sees during checkout.', 'klarna' ), 
				'default' => __( 'Klarna Payment Methods', 'klarna' )
			),
			'description' => array(
				'title' => __( 'Description', 'klarna' ), 
				'type' => 'textarea', 
				'description' => __( 'This controls the description which the user sees during checkout. ', 'klarna' ), 
				'default' => ''
			),

			'eid_se' => array(
				'title' => __( 'Eid - Sweden', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Sweden. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_se' => array(
				'title' => __( 'Shared Secret - Sweden', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Sweden.', 'klarna' ), 
				'default' => ''
			),

			'eid_no' => array(
				'title' => __( 'Eid - Norway', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Norway. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_no' => array(
				'title' => __( 'Shared Secret - Norway', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Norway.', 'klarna' ), 
				'default' => ''
			),

			'eid_fi' => array(
				'title' => __( 'Eid - Finland', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Finland. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_fi' => array(
				'title' => __( 'Shared Secret - Finland', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Finland.', 'klarna' ), 
				'default' => ''
			),

			'eid_dk' => array(
				'title' => __( 'Eid - Denmark', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Denmark. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_dk' => array(
				'title' => __( 'Shared Secret - Denmark', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Denmark.', 'klarna' ), 
				'default' => ''
			),

			'eid_de' => array(
				'title' => __( 'Eid - Germany', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Germany. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_de' => array(
				'title' => __( 'Shared Secret - Germany', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Germany.', 'klarna' ), 
				'default' => ''
			),

			'eid_nl' => array(
				'title' => __( 'Eid - Netherlands', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Eid for Netherlands. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'secret_nl' => array(
				'title' => __( 'Shared Secret - Netherlands', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Please enter your Klarna Shared Secret for Netherlands.', 'klarna' ), 
				'default' => ''
			),

			'lower_threshold' => array(
				'title' => __( 'Lower threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Account if Cart Total is lower than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),
			'upper_threshold' => array(
				'title' => __( 'Upper threshold', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable Klarna Account if Cart Total is higher than the specified value. Leave blank to disable this feature.', 'klarna' ), 
				'default' => ''
			),

			'show_monthly_cost' => array(
				'title' => __( 'Display monthly cost - product page', 'klarna' ), 
				'type' => 'checkbox',
				'label' => __( 'Display monthly cost on single products page.', 'klarna' ), 
				'default' => 'yes'
			),
			'show_monthly_cost_prio' => array(
				'title' => __( 'Placement of monthly cost - product page', 'klarna' ), 
				'type' => 'select',
				'options' => array(
					'4' => __( 'Above Title', 'klarna' ),
					'7' => __( 'Between Title and Price', 'klarna'),
					'15' => __( 'Between Price and Excerpt', 'klarna'), 
					'25' => __( 'Between Excerpt and Add to cart-button', 'klarna'), 
					'35' => __( 'Between Add to cart-button and Product meta', 'klarna'), 
					'45' => __( 'Between Product meta and Product sharing-buttons', 'klarna'), 
					'55' => __( 'After Product sharing-buttons', 'klarna' )
				),
				'description' => __( 'Select where on the products page the Monthly cost information should be displayed.', 'klarna' ), 
				'default' => '15'
			),
			'lower_threshold_monthly_cost' => array(
				'title' => __( 'Lower threshold for monthly cost', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable the monthly cost feature if <i>Product price</i> is lower than the specified value. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),
			'upper_threshold_monthly_cost' => array(
				'title' => __( 'Upper threshold for monthly cost', 'klarna' ), 
				'type' => 'text', 
				'description' => __( 'Disable the monthly cost feature if <i>Product price</i> is higher than the specified value. Leave blank to disable.', 'klarna' ), 
				'default' => ''
			),

			'ship_to_billing_address' => array(
				'title' => __( 'Send billing address as shipping address', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Send the entered billing address in WooCommerce checkout as shipping address to Klarna.', 'klarna' ), 
				'default' => 'no'
			),

			'de_consent_terms' => array(
				'title' => __( 'Klarna consent terms (DE & AT only)', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna consent terms checkbox in checkout. This only apply to German and Austrian merchants.', 'klarna' ), 
				'default' => 'no'
			),

			'testmode' => array(
				'title' => __( 'Test Mode', 'klarna' ), 
				'type' => 'checkbox', 
				'label' => __( 'Enable Klarna Test Mode. This will only work if you have a Klarna test account. For test purchases with a live account, <a href="http://integration.klarna.com/en/testing/test-persons" target="_blank">follow these instructions</a>.', 'klarna' ), 
				'default' => 'no'
			)
		);
	    
	}


	/**
	 * Admin Panel Options.
	 * 
	 * Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @todo  Move PClasses retrieval out of this method.
	 */
	public function admin_options() { ?>

		<h3><?php _e('Klarna Account', 'klarna'); ?></h3>
		<p><?php printf(__('With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna'), 'http://docs.woothemes.com/document/klarna/' ); ?></p>

		<?php
		if ( ! empty( $this->authorized_countries ) && $this->enabled == 'yes' ) {
			echo '<h4>' . __( 'Active PClasses', 'klarna' ) . '</h4>';
			foreach ( $this->authorized_countries as $key => $country ) {
				$pclasses = $this->fetch_pclasses( $country );
				if ( $pclasses ) {
					echo '<p>' . $country . '</p>';
					foreach( $pclasses as $pclass ) {
						// if ( $pclass->getType() == 0 || $pclass->getType() == 1 ) { // Passed from parent file
							echo '<pre>';
							print_r( $pclass );
							echo '</pre>';
						// }
					}
					echo '<br/>';
				}
			}
		}
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); // Generate the HTML For the settings form. ?>
		</table>

	<?php }


	/**
	 * Check if this gateway is enabled and available in user's country.
	 *
	 * @since 1.0.0
	 * @todo  Move all individual checks to helper functions, since they are used in each method?
	 */		
	function is_available() {

		global $woocommerce;
		
		// Check if this payment method is enabled
		if ( 'yes' == $this->enabled ) {
		
			// Required fields check
			if ( ! $this->get_eid() || ! $this->get_secret() )
				return false;
			
			// PClass check
			$pclasses = $this->fetch_pclasses( $this->get_klarna_country() );
			if ( empty( $pclasses ) ) {
				return false;
			}
			
			// Checkout form check
			if ( isset( $woocommerce->cart->total ) ) {
			
				// Cart totals check - Lower threshold
				if ( $this->lower_threshold !== '' ) {
					if ( $woocommerce->cart->total < $this->lower_threshold )
						return false;
				}
			
				// Cart totals check - Upper threshold
				if ( $this->upper_threshold !== '' ) {
					if ( $woocommerce->cart->total > $this->upper_threshold )
						return false;
				}
				
				// Don't allow orders over the amount of €250 for Dutch customers
				if ( ( $woocommerce->customer->get_country() == true && $woocommerce->customer->get_country() == 'NL' ) && $woocommerce->cart->total >= 251 )
					return false;
			
				// Only activate the payment gateway if the customers country is the same as the filtered shop country ($this->klarna_country)
				if ( $woocommerce->customer->get_country() == true && ! in_array( $woocommerce->customer->get_country(), $this->authorized_countries ) )
					return false;
				
				// Currency check
				$currency_for_country = $this->get_currency_for_country($woocommerce->customer->get_country());
				if ( ! empty($currency_for_country) && $currency_for_country !== $this->selected_currency )
					return false;
			
			} // End Checkout form check
			
			return true;
			
		}	

		return false;

	}


	/**
	 * Set up Klarna configuration.
	 * 
	 * @since  2.0
	 **/
	function configure_klarna( $klarna ) {

		$klarna->config(
			$this->get_eid(), 												// EID
			$this->get_secret(), 											// Secret
			$this->get_klarna_country(), 									// Country
			$this->get_klarna_language($this->get_klarna_country()), 		// Language
			$this->selected_currency, 										// Currency
			$this->klarna_mode, 										    // Live or test
			$pcStorage = 'jsondb', 											// PClass storage
			$pcURI = 'klarna_pclasses_' . $this->get_klarna_country()		// PClass storage URI path
		);

	}


	/**
	 * Payment form on checkout page
	 *
	 * @since 1.0.0
	 */	
	function payment_fields() {

	   	global $woocommerce;
	   		   	
	   	// Get PClasses so that the customer can chose between different payment plans.
	  	require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );
		
		if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
		}

		if ( 'yes' == $this->testmode ) { ?>
			<p><?php _e('TEST MODE ENABLED', 'klarna'); ?></p>
		<?php }

		// Test mode or Live mode		
		if ( 'yes' == $this->testmode ) {
			// Disable SSL if in testmode
			$klarna_ssl = 'false';
			$klarna_mode = Klarna::BETA;
		} else {
			// Set SSL if used in webshop
			if ( is_ssl() ) {
				$klarna_ssl = 'true';
			} else {
				$klarna_ssl = 'false';
			}
			$klarna_mode = Klarna::LIVE;
		}
	   			
		$klarna = $this->klarna;

		/**
		 * Setup Klarna configuration
		 */
		$this->configure_klarna( $klarna );
		
		Klarna::$xmlrpcDebug = false;
		Klarna::$debug = false;
		
		// apply_filters to cart total so we can filter this if needed
		$klarna_cart_total = $woocommerce->cart->total;
		$sum = apply_filters( 'klarna_cart_total', $klarna_cart_total ); // Cart total.
		$flag = KlarnaFlags::CHECKOUT_PAGE; // or KlarnaFlags::PRODUCT_PAGE, if you want to do it for one item.
		
		// Description
		if ( $this->description ) {
			$klarna_description = $this->description;
			// apply_filters to the description so we can filter this if needed
			echo '<p>' . apply_filters( 'klarna_account_description', $klarna_description ) . '</p>';
		}
			
		if ( 'NO' == $this->get_klarna_country() ) {

			// Use Klarna PMS for Norway
			$payment_method_group = 'part_payment';
			$payment_method_select_id = 'klarna_account_pclass';
			include_once( KLARNA_DIR . 'views/public/payment-fields-pms.php' );

		} else {

			// For countries other than NO do the old thing
			include_once( KLARNA_DIR . 'views/public/payment-fields-kpm.php' );
		
		}
	
	}


	/**
	 * Retrieve the PClasses from Klarna
	 *
	 * @since 1.0.0
	 */
	function fetch_pclasses( $country ) {
		
		// Get PClasses so that the customer can chose between different payment plans.
		require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );
		
		if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
		}
					
		$klarna = $this->klarna;

		$klarna->config(
			$this->get_eid( $country ), 					// EID
			$this->get_secret( $country ), 					// Secret
			$country, 										// Country
			$this->get_klarna_language( $country ),			// Language
			$this->get_currency_for_country( $country ),	// Currency
			$this->klarna_mode, 							// Live or test
			$pcStorage = 'jsondb', 							// PClass storage
			$pcURI = 'klarna_pclasses_' . $country			// PClass storage URI path
		);
		
		if ( $klarna->getPClasses() ) {
			return $klarna->getPClasses();
		} else {
			try {
				// You can specify country (and language, currency if you wish) if you don't want 
				// to use the configured country.
				$klarna->fetchPClasses( $country ); 
				return $klarna->getPClasses();
			}
			catch( Exception $e ) {
				return false;
			}
		}

	}


	/**
 	 * Process the gateway specific checkout form fields
	 *
	 * @since 1.0.0
	 * @todo  Use helper functions. One for SE, NO, DK and FI, and one for DE and AT.
 	 **/
	function klarna_account_checkout_field_process() {

		global $woocommerce;

 		// Only run this if Klarna account is the choosen payment method
 		if ( $_POST['payment_method'] == 'klarna_account' ) {
 		
 			$klarna_field_prefix = 'klarna_account_';

			include_once( KLARNA_DIR . 'includes/checkout-field-process.php' );

		}

	}


	/**
	 * Get Monthly cost execution priority, used in product page.
	 *
	 * @since 1.0.0
	 **/
	function get_monthly_cost_prio() {

		return $this->show_monthly_cost_prio;

	}

	
	/**
	 * Get Monthly cost execution priority, used in shop base page and archives.
	 *
	 * @since 1.0.0
	 **/
	function get_monthly_cost_shop_prio() {

		return $this->show_monthly_cost_shop_prio;

	}

	
	/**
	 * Helper function, checks if payment method is enabled.
	 *
	 * @since 1.0.0
	 **/
	function get_enabled() {

		return $this->enabled;

	}


	/**
	 * Helper function, gets Klarna locale based on current locale.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $locale
	 * @return string $klarna_locale
	 **/
	function get_klarna_locale( $locale ) {

		switch ( $locale ) {
			case 'da_DK':
				$klarna_locale = 'da_dk';
				break;
			case 'de_DE' :
				$klarna_locale = 'de_de';
				break;
			case 'no_NO' :
			case 'nb_NO' :
			case 'nn_NO' :
				$klarna_locale = 'nb_no';
				break;
			case 'nl_NL' :
				$klarna_locale = 'nl_nl';
				break;
			case 'fi_FI' :
			case 'fi' :
				$klarna_locale = 'fi_fi';
				break;
			case 'sv_SE' :
				$klarna_locale = 'sv_se';
				break;
			case 'de_AT' :
				$klarna_locale = 'de_at';
				break;
			case 'en_US' :
			case 'en_GB' :
				$klarna_locale = 'en_se';
				break;
			default:
				$klarna_locale = '';
		}
		
		return $klarna_locale;

	}
	
	
	/**
	 * Helper function, gets Klarna eid based on country.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $country
	 * @return integer $current_eid
	 **/
	function get_eid( $country = false ) {

		global $woocommerce;
	
		if ( empty( $country ) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->shop_country;
		}
		
		$current_eid = '';
		
		switch ( $country )	{
			case 'DK' :
				$current_eid = $this->eid_dk;
				break;
			case 'DE' :
				$current_eid = $this->eid_de;
				break;
			case 'NL' :
				$current_eid = $this->eid_nl;
				break;
			case 'NO' :
				$current_eid = $this->eid_no;
				break;
			case 'FI' :
				$current_eid = $this->eid_fi;
				break;
			case 'SE' :
				$current_eid = $this->eid_se;
				break;
			case 'AT' :
				$current_eid = $this->eid_at;
				break;
			default:
				$current_eid = '';
		}
		
		return $current_eid;

	}
	
	
	/**
	 * Helper function, gets Klarna secret based on country.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $country
	 * @return string $current_secret
	 **/
	function get_secret( $country = false ) {

		global $woocommerce;
	
		if ( empty( $country ) ) {
			$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : $this->shop_country;
		}
		
		$current_secret = '';
		
		switch ( $country )	{
			case 'DK' :
				$current_secret = $this->secret_dk;
				break;
			case 'DE' :
				$current_secret = $this->secret_de;
				break;
			case 'NL' :
				$current_secret = $this->secret_nl;
				break;
			case 'NO' :
				$current_secret = $this->secret_no;
				break;
			case 'FI' :
				$current_secret = $this->secret_fi;
				break;
			case 'SE' :
				$current_secret = $this->secret_se;
				break;
			case 'AT' :
				$current_secret = $this->secret_at;
				break;
			default:
				$current_secret = '';
		}
		
		return $current_secret;

	}
	
	
	/**
	 * Helper function, gets currency for selected country.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $country
	 * @return string $currency
	 **/
	function get_currency_for_country( $country ) {
				
		switch ( $country )	{
			case 'DK' :
				$currency = 'DKK';
				break;
			case 'DE' :
				$currency = 'EUR';
				break;
			case 'NL' :
				$currency = 'EUR';
				break;
			case 'NO' :
				$currency = 'NOK';
				break;
			case 'FI' :
				$currency = 'EUR';
				break;
			case 'SE' :
				$currency = 'SEK';
				break;
			case 'AT' :
				$currency = 'EUR';
				break;
			default:
				$currency = '';
		}
		
		return $currency;

	}
	
	
	/**
	 * Helper function, gets Klarna language for selected country.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $country
	 * @return string $language
	 **/
	function get_klarna_language( $country ) {
				
		switch ( $country )	{
			case 'DK' :
				$language = 'DA';
				break;
			case 'DE' :
				$language = 'DE';
				break;
			case 'NL' :
				$language = 'NL';
				break;
			case 'NO' :
				$language = 'NB';
				break;
			case 'FI' :
				$language = 'FI';
				break;
			case 'SE' :
				$language = 'SV';
				break;
			case 'AT' :
				$language = 'DE';
				break;
			default:
				$language = '';
		}
		
		return $language;

	}
	
	
	/**
	 * Helper function, gets Klarna country.
	 *
	 * @since 1.0.0
	 * 
	 * @return string $klarna_country
	 **/
	function get_klarna_country() {

		global $woocommerce;
		
		if ( $woocommerce->customer->get_country() ) {
			$klarna_country = $woocommerce->customer->get_country();
		} else {
			$klarna_country = $this->shop_language;
			switch ( $this->shop_country ) {
				case 'NB' :
					$klarna_country = 'NO';
					break;
				case 'SV' :
					$klarna_country = 'SE';
					break;
			}
		}
		
		// Check if $klarna_country exists among the authorized countries
		if ( ! in_array( $klarna_country, $this->authorized_countries ) ) {
			return $this->shop_country;
		} else {
			return $klarna_country;
		}

	}
	
	
	/**
	 * Helper function, gets invoice icon.
	 *
	 * @since 1.0.0
	 * 
	 * @return string $klarna_account_icon
	 **/
	function get_account_icon() {
		
		global $woocommerce;

		$country = ( isset( $woocommerce->customer->country ) ) ? $woocommerce->customer->country : '';
	
		if ( empty( $country ) ) {
			$country = $this->shop_country;
		}
		
		$current_secret = '';
		
		switch ( $country ) {
			case 'DK':
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/da_dk/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'DE' :
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_de/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NL' :
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/nl_nl/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NO' :
				$klarna_account_icon = false;
				break;
			case 'FI' :
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/fi_fi/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'SE' :
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/sv_se/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'AT' :
				$klarna_account_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_at/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			default:
				$klarna_account_icon = '';
		}
		
		return $klarna_account_icon;

	}

}