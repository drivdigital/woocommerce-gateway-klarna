<?php
/**
 * Klarna part payment KPM
 *
 * @link http://www.woothemes.com/products/klarna/
 * @since 1.0.0
 *
 * @package WC_Gateway_Klarna
 */


/**
 * Class for Klarna Part Payment.
 */
class WC_Gateway_Klarna_KPM_Part_Payment extends WC_Gateway_Klarna {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $woocommerce;
		
		parent::__construct();
		
		$this->id                 = 'klarna_kpm_part_payment';
		$this->method_title       = __( 'Klarna Part Payment', 'klarna' );
		$this->method_description = sprintf( __( 'With Klarna your customers can pay by invoice. Klarna works by adding extra personal information fields and then sending the details to Klarna for verification. Documentation <a href="%s" target="_blank">can be found here</a>.', 'klarna' ), 'http://docs.woothemes.com/document/klarna/' );
		$this->has_fields         = true;
		$this->order_button_text  = apply_filters( 'klarna_order_button_text', __( 'Place order', 'woocommerce' ) );
						
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Load shortcodes. 
		// This is used so that the merchant easily can modify the displayed monthly 
		// cost text (on single product and shop page) via the settings page.
		include_once( KLARNA_DIR . 'classes/class-klarna-shortcodes.php' );

		/**
		 * Define user set variables
		 */
		$this->enabled     = $this->get_option( 'enabled' );
		$this->testmode    = $this->get_option( 'testmode' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		// Sweden
		$this->eid_se    = $this->get_option( 'eid_se' );
		$this->secret_se = $this->get_option( 'secret_se' );

		// Norway
		$this->eid_no    = $this->get_option( 'eid_no' );
		$this->secret_no = $this->get_option( 'secret_no' );

		// Finland
		$this->eid_fi    = $this->get_option( 'eid_fi' );
		$this->secret_fi = $this->get_option( 'secret_fi' );

		// Denmark
		$this->eid_dk    = $this->get_option( 'eid_dk' );
		$this->secret_dk = $this->get_option( 'secret_dk' );

		// Germany
		$this->eid_de    = $this->get_option( 'eid_de' );
		$this->secret_de = $this->get_option( 'secret_de' );

		// Netherlands
		$this->eid_nl    = $this->get_option( 'eid_nl' );
		$this->secret_nl = $this->get_option( 'secret_nl' );

		// Austria
		$this->eid_at    = $this->get_option( 'eid_at' );
		$this->secret_at = $this->get_option( 'secret_at' );

		// Lower and upper treshold
		$this->lower_threshold = $this->get_option( 'lower_threshold' );
		$this->upper_threshold = $this->get_option( 'upper_threshold' );

		// Monthly cost widget
		$this->show_monthly_cost            = $this->get_option( 'show_monthly_cost' );
		$this->show_monthly_cost_prio       = $this->get_option( 'show_monthly_cost_prio', 15 );
		$this->lower_threshold_monthly_cost = $this->get_option( 'lower_threshold_monthly_cost', 0 );
		$this->upper_threshold_monthly_cost = $this->get_option( 'upper_threshold_monthly_cost', 10000000 );

		$this->de_consent_terms        = $this->get_option( 'de_consent_terms' );
		$this->ship_to_billing_address = $this->get_option( 'ship_to_billing_address' );

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
		$this->klarna_kpm_part_payment_info = apply_filters( 'klarna_kpm_part_payment_info', '' );
		$this->icon = apply_filters( 'klarna_kpm_part_payment_icon', $this->get_account_icon() );	
		$this->icon_basic = apply_filters( 'klarna_basic_icon', '' );

		// Apply filters to Klarna warning banners (NL only)
		$klarna_wb = $this->get_klarna_wb();

		$this->klarna_wb_img_checkout = apply_filters( 'klarna_wb_img_checkout', $klarna_wb['img_checkout'] );
		$this->klarna_wb_img_single_product = apply_filters( 'klarna_wb_img_single_product', $klarna_wb['img_single_product'] );
		$this->klarna_wb_img_product_list = apply_filters( 'klarna_wb_img_product_list', $klarna_wb['img_product_list'] );
				
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_klarna_kpm_part_payment', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'klarna_kpm_part_payment_checkout_field_process' ) );
		add_action( 'wp_print_footer_scripts', array(  $this, 'footer_scripts' ) );
		
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	function init_form_fields() {

		$this->form_fields = include( KLARNA_DIR . 'includes/settings-kpm-part-payment.php' );
	    
	}


	/**
	 * Admin Panel Options.
	 * 
	 * @since 1.0.0
	 * @todo  Move PClasses retrieval out of this method.
	 */
	public function admin_options() { ?>

		<h3>
			<?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'klarna' ) ; ?>
		</h3>
		<?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>

		<?php $this->display_available_pclasses( array( 0, 1 ) ); ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); // Generate the HTML For the settings form. ?>
		</table>

	<?php }


	/**
	 * Shows all available PClasses.
	 * 
	 * @since 1.0.0
	 */
	function display_available_pclasses( $type ) {

		if ( ! empty( $this->authorized_countries ) && $this->enabled == 'yes' ) { ?>
			<h4><?php echo __( 'Active PClasses', 'klarna' ); ?></h4>
			<?php
			foreach ( $this->authorized_countries as $key => $country ) {
				$this->display_pclasses_for_country_and_type( $country, $type );
			}
		}

	}


	/**
	 * Retrieves PClasses for country.
	 * 
	 * @since 1.0.0
	 * 
	 * @param  $country  Country code
	 * @param  $type     Array of PClasses types
	 * @return $pclasses Key value array of countries and their PClasses
	 */
	function get_pclasses_for_country_and_type( $country, $type ) {

		$pclasses_country_all = $this->fetch_pclasses( $country );
		$pclasses_country_type = array();

		if ( $pclasses_country_all ) {
			foreach ( $pclasses_country_all as $pclass ) {
				if ( in_array( $pclass->getType(), $type ) ) { // Passed from parent file
					$pclasses_country_type[] = $pclass;
				}
			}
		}

		if ( ! empty( $pclasses_country_type ) ) {
			return $pclasses_country_type;
		}

	}


	/**
	 * Displays available PClasses.
	 * 
	 * @since 1.0.0
	 */
	function display_pclasses_for_country_and_type( $country, $type ) {

		$pclasses = $this->get_pclasses_for_country_and_type( $country, $type );

		if ( $pclasses ) { ?>
			<h5 style="margin-bottom:0.25em;"><?php echo $country; ?></h5>
			<?php
			$pclass_string = '';
			foreach( $pclasses as $pclass ) {
				if ( $pclass->getType() == 0 || $pclass->getType() == 1 ) { // Passed from parent file
					$pclass_string .= $pclass->getDescription() . ', ';
				}
			}
			$pclass_string = substr( $pclass_string, 0 ,-2 );
			?>
			<p style="margin-top:0;"><?php echo $pclass_string; ?></p>
		<?php }
				
	}


	/**
	 * Gets Klarna warning banner images, used for NL only.
	 *
	 * @since  1.0.0
	 * 
	 * @return $klarna_wb array
	 */		
	function get_klarna_wb() {
		
		$klarna_wb = array();

		// Klarna warning banner - used for NL only
		$klarna_wb['img_checkout'] = 
			'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm1-jpg.ashx';
		$klarna_wb['img_single_product'] = 
			'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';
		$klarna_wb['img_product_list'] = 
			'http://www.afm.nl/~/media/Images/wetten-regels/kredietwaarschuwing/balk_afm2-jpg.ashx';

		return $klarna_wb;

	}


	/**
	 * Check if this gateway is enabled and available in user's country.
	 *
	 * @since 1.0.0
	 */		
	function is_available() {

		$this->check_enabled();
		$this->check_required_fields();
		$this->check_pclasses();
		$this->check_cart_total();
		$this->check_lower_threshold();
		$this->check_upper_threshold();
		$this->check_customer_country();
		$this->check_customer_currency();

		return true;

	}


	/**
	 * Checks if payment method is enabled.
	 * 
	 * @since  2.0
	 **/
	function check_enabled() {

		if ( 'yes' != $this->enabled )
			return false;

	}	


	/**
	 * Checks if required fields are set.
	 * 
	 * @since  2.0
	 **/
	function check_required_fields() {

		// Required fields check
		if ( ! $this->get_eid() || ! $this->get_secret() )
			return false;

	}	


	/**
	 * Checks if there are PClasses.
	 * 
	 * @since  2.0
	 **/
	function check_pclasses() {

		$pclasses = $this->fetch_pclasses( $this->get_klarna_country() );
		if ( empty( $pclasses ) )
			return false;

	}	


	/**
	 * Checks if there is cart total.
	 * 
	 * @since  2.0
	 **/
	function check_cart_total() {

		global $woocommerce;

		if ( ! isset( $woocommerce->cart->total ) )
			return false;

	}	


	/**
	 * Checks if lower threshold is OK.
	 * 
	 * @since  2.0
	 **/
	function check_lower_threshold() {

		global $woocommerce;

		// Cart totals check - Lower threshold
		if ( $this->lower_threshold !== '' ) {
			if ( $woocommerce->cart->total < $this->lower_threshold )
				return false;
		}

	}	


	/**
	 * Checks if upper threshold is OK.
	 * 
	 * @since  2.0
	 **/
	function check_upper_threshold() {

		global $woocommerce;
		
		// Cart totals check - Upper threshold
		if ( $this->upper_threshold !== '' ) {
			if ( $woocommerce->cart->total > $this->upper_threshold )
				return false;
		}

	}	


	/**
	 * Checks if selling to customer's country is allowed.
	 * 
	 * @since  2.0
	 **/
	function check_customer_country() {

		global $woocommerce;
		
		// Only activate the payment gateway if the customers country is the same as 
		// the filtered shop country ($this->klarna_country)
		if ( $woocommerce->customer->get_country() == true && ! in_array( $woocommerce->customer->get_country(), $this->authorized_countries ) )
			return false;

		// Don't allow orders over the amount of €250 for Dutch customers
		if ( ( $woocommerce->customer->get_country() == true && $woocommerce->customer->get_country() == 'NL' ) && $woocommerce->cart->total >= 251 )
			return false;

	}	


	/**
	 * Checks if customer's currency is allowed.
	 * 
	 * @since  2.0
	 **/
	function check_customer_currency() {

		global $woocommerce;
		
		// Currency check
		$currency_for_country = $this->get_currency_for_country( $woocommerce->customer->get_country() );
		if ( ! empty( $currency_for_country ) && $currency_for_country !== $this->selected_currency )
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
			echo '<p>' . apply_filters( 'klarna_kpm_part_payment_description', $klarna_description ) . '</p>';
		}
			
		if ( 'NO' == $this->get_klarna_country() ) {

			// Use Klarna PMS for Norway
			$payment_method_group = 'part_payment';
			$payment_method_select_id = 'klarna_kpm_part_payment_pclass';
			include_once( KLARNA_DIR . 'views/public/payment-fields-pms.php' );

		} else {

			// For countries other than NO do the old thing
			include_once( KLARNA_DIR . 'views/public/payment-fields-kpm.php' );
		
		}
	
	}
	
	
	/**
 	 * Process the gateway specific checkout form fields
	 *
	 * @since 1.0.0
	 * @todo  Use helper functions. One for SE, NO, DK and FI, and one for DE and AT.
 	 **/
	function klarna_kpm_part_payment_checkout_field_process() {

		global $woocommerce;

 		// Only run this if Klarna Part Payment is the choosen payment method
 		if ( $_POST['payment_method'] == 'klarna_kpm_part_payment' ) {
 		
 			$klarna_field_prefix = 'klarna_kpm_part_payment_';

			include_once( KLARNA_DIR . 'includes/checkout-field-process.php' );

		}

	}
	
	
	/**
	 * Collect DoB, based on country.
	 * 
	 * @since  2.0
	 **/
	function collect_dob( $order_id ) {
	
		// Collect the dob different depending on country
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' ) {
			$klarna_pno_day = 
				isset( $_POST['date_of_birth_day'] ) ? woocommerce_clean( $_POST['date_of_birth_day'] ) : '';
			$klarna_pno_month = 
				isset( $_POST['date_of_birth_month'] ) ? woocommerce_clean( $_POST['date_of_birth_month'] ) : '';
			$klarna_pno_year = 
				isset( $_POST['date_of_birth_year'] ) ? woocommerce_clean( $_POST['date_of_birth_year'] ) : '';

			$klarna_pno = $klarna_pno_day . $klarna_pno_month . $klarna_pno_year;
		} else {
			$klarna_pno = 
				isset( $_POST['klarna_kpm_part_payment_pno'] ) ? woocommerce_clean( $_POST['klarna_kpm_part_payment_pno'] ) : '';
		}

		return $klarna_pno;

	}


	/**
	 * Process the payment and return the result
	 *
	 * @since 1.0.0
	 **/
	function process_payment( $order_id ) {

		global $woocommerce;
		
		$order = WC_Klarna_Compatibility::wc_get_order( $order_id );
		
		require_once( KLARNA_LIB . 'Klarna.php' );
		require_once( KLARNA_LIB . 'pclasses/storage.intf.php' );
		
		if ( ! function_exists( 'xmlrpc_encode_entitites' ) && ! class_exists( 'xmlrpcresp' ) ) {
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc' );
			require_once( KLARNA_LIB . '/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc' );
		}
		
		// Get values from klarna form on checkout page
		
		// Collect the DoB
		$klarna_pno = $this->collect_dob( $order_id );

		// Store Klarna specific form values in order as post meta
		update_post_meta( $order_id, 'klarna_pno', $klarna_pno);
		
		$klarna_pclass = 
			isset( $_POST['klarna_kpm_part_payment_pclass'] ) ? woocommerce_clean( $_POST['klarna_kpm_part_payment_pclass'] ) : '';
		$klarna_gender = 
			isset( $_POST['klarna_kpm_part_payment_gender'] ) ? woocommerce_clean( $_POST['klarna_kpm_part_payment_gender'] ) : '';
		$klarna_de_consent_terms = 
			isset( $_POST['klarna_de_consent_terms'] ) ? woocommerce_clean( $_POST['klarna_de_consent_terms'] ) : '';
			
		// Split address into House number and House extension for NL & DE customers
		$klarna_billing = array();
		$klarna_shipping = array();
		if ( $_POST['billing_country'] == 'NL' || $_POST['billing_country'] == 'DE' ) {
			require_once( KLARNA_DIR . 'split-address.php' );
			
			// Set up billing address array
			$klarna_billing_address             = $order->billing_address_1;
			$splitted_address                   = splitAddress( $klarna_billing_address );
			$klarna_billing['address']          = $splitted_address[0];
			$klarna_billing['house_number']	    = $splitted_address[1];
			$klarna_billing['house_extension']  = $splitted_address[2];
			
			// Set up shipping address array
			$klarna_shipping_address            = $order->shipping_address_1;
			$splitted_address                   = splitAddress( $klarna_shipping_address );
			$klarna_shipping['address']         = $splitted_address[0];
			$klarna_shipping['house_number']    = $splitted_address[1];
			$klarna_shipping['house_extension'] = $splitted_address[2];
		} else {			
			$klarna_billing['address']          = $order->billing_address_1;
			$klarna_billing['house_number']     = '';
			$klarna_billing['house_extension']  = '';
			
			$klarna_shipping['address']         = $order->shipping_address_1;
			$klarna_shipping['house_number']    = '';
			$klarna_shipping['house_extension'] = '';
		}
			
		$klarna = $this->klarna;
		

		/**
		 * Setup Klarna configuration
		 */
		$this->configure_klarna( $klarna );

		$klarna_order = new WC_Gateway_Klarna_Order(
			$order,
			$klarna_billing,
			$klarna_shipping,
			$this->ship_to_billing_address,
			$klarna
		);

		// Set store specific information so you can e.g. search and associate invoices with order numbers.
		$klarna->setEstoreInfo(
		    $orderid1 = ltrim( $order->get_order_number(), '#' ),
		    $orderid2 = $order_id,
		    $user     = '' // Username, email or identifier for the user?
		);
		

		try {
			// Transmit all the specified data, from the steps above, to Klarna.
			$result = $klarna->reserveAmount(
				$klarna_pno, 			// Date of birth.
				$klarna_gender,			// Gender.
				-1, 					// Automatically calculate and reserve the cart total amount
				KlarnaFlags::NO_FLAG, 	// No specific behaviour like RETURN_OCR or TEST_MODE.
				$klarna_pclass 			// Get the pclass object that the customer has choosen.
			);
    		
			// Prepare redirect url
			$redirect_url = $order->get_checkout_order_received_url();

			// Store the selected pclass in the order
			update_post_meta( $order_id, '_klarna_order_pclass', $klarna_pclass );

			// Retreive response
			$invno = $result[0];

    		switch( $result[1] ) {
				case KlarnaFlags::ACCEPTED :
					$order->add_order_note(
						__( 'Klarna payment completed. Klarna Invoice number: ', 'klarna' ) . $invno
					);
					update_post_meta( $order_id, '_klarna_order_reservation', $invno );
					$order->payment_complete(); // Payment complete
					$woocommerce->cart->empty_cart(); // Remove cart	
					// Return thank you redirect
					return array(
						'result'   => 'success',
						'redirect' => $redirect_url
					);
					break;

				case KlarnaFlags::PENDING :
					$order->add_order_note(
						__( 'Order is PENDING APPROVAL by Klarna. Please visit Klarna Online for the latest status on this order. Klarna Invoice number: ', 'klarna' ) . $invno
					);
					$order->payment_complete(); // Payment complete					
					$woocommerce->cart->empty_cart(); // Remove cart
					// Return thank you redirect
					return array(
						'result'   => 'success',
						'redirect' => $redirect_url
					);
					break;

				case KlarnaFlags::DENIED : // Order is denied, store it in a database.
					$order->add_order_note(
						__( 'Klarna payment denied.', 'klarna' )
					);
					wc_add_notice(
						__( 'Klarna payment denied.', 'klarna' ), 
						'error'
					);
					return;
					break;

				default: // Unknown response, store it in a database.
					$order->add_order_note(
						__( 'Unknown response from Klarna.', 'klarna' )
					);
					wc_add_notice(
						__( 'Unknown response from Klarna.', 'klarna' ),
						'error'
					);
					return;
					break;
			}

		} catch( Exception $e ) {
    		// The purchase was denied or something went wrong, print the message:
			wc_add_notice(
				sprintf(
					__( '%s (Error code: %s)', 'klarna' ),
					utf8_encode( $e->getMessage() ),
					$e->getCode()
				),
				'error'
			);
			return;
		}

	}
	
	/**
	 * Adds note in receipt page.
	 *
	 * @since 1.0.0
	 **/
	function receipt_page( $order ) {
	
		echo '<p>'.__('Thank you for your order.', 'klarna').'</p>';
		
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
	 * Calc monthly cost on single Product page and print it out
	 *
	 * @since 1.0.0
	 **/ 
	function print_product_monthly_cost() {
		
		if ( $this->enabled!="yes" || $this->get_klarna_locale(get_locale()) == 'nl_nl' )
			return;
			
		global $woocommerce, $product, $klarna_kpm_part_payment_shortcode_currency, $klarna_kpm_part_payment_shortcode_price, $klarna_shortcode_img, $klarna_kpm_part_payment_country;
		
		$klarna_product_total = $product->get_price();
		
		// Product with no price - do nothing
		if ( empty( $klarna_product_total ) )
			return;
		
		$sum = apply_filters( 'klarna_product_total', $klarna_product_total ); // Product price.
		$sum = trim( $sum );
		
	 	// Only execute this if the feature is activated in the gateway settings
		if ( $this->show_monthly_cost == 'yes' ) {

	    		
    		// Monthly cost threshold check. This is done after apply_filters to product price ($sum).
	    	if ( $this->lower_threshold_monthly_cost < $sum && $this->upper_threshold_monthly_cost > $sum ) {
	    		$data = new WC_Gateway_Klarna_Invoice;
	    		$invoice_fee = $data->get_invoice_fee_price();
	    		
	    		?>
				<div style="width:220px; height:70px" 
				     class="klarna-widget klarna-part-payment"
				     data-eid="<?php echo $this->get_eid();?>" 
				     data-locale="<?php echo $this->get_klarna_locale(get_locale());?>"
				     data-price="<?php echo $sum;?>"
				     data-layout="pale"
				     data-invoice-fee="<?php echo $invoice_fee;?>">
				</div>
		
				<?php
	    		
	    		// Show klarna_warning_banner if NL
				if ( $this->shop_country == 'NL' ) {
					echo '<img src="' . $this->klarna_wb_img_single_product . '" class="klarna-wb" style="max-width: 100%;"/>';	
				}
	    				    	
	    	}
		
		}
		
	}
	
	
	/**
	 * Disable the radio button for the Klarna Part Payment payment method if Company name
	 * is entered and the customer is from Germany or Austria.
	 *
	 * @since 1.0.0
	 * @todo  move to separate JS file?
	 **/
	function footer_scripts () {
			
		if ( is_checkout() && 'yes' == $this->enabled ) { ?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ajaxComplete(function(){
					if (jQuery.trim(jQuery('input[name=billing_company]').val()) && (jQuery( "#billing_country" ).val()=='DE' || jQuery( "#billing_country" ).val()=='AT')) {
						jQuery('#payment_method_klarna_kpm_part_payment').prop('disabled', true);
					} else jQuery('#payment_method_klarna_kpm_part_payment').prop('disabled', false);
				});
				
				jQuery(document).ready(function($){
					$(window).load(function(){
						$('input[name=billing_company]').keyup(function() {
							if ($.trim(this.value).length && ($( "#billing_country" ).val()=='DE' || $( "#billing_country" ).val()=='AT')) {
								$('#payment_method_klarna_kpm_part_payment').prop('disabled', true);
							} else $('#payment_method_klarna_kpm_part_payment').prop('disabled', false);
						});
					});	
				});
				//]]>
			</script>
		<?php }
	
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
	 * @return string $klarna_kpm_part_payment_icon
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
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/da_dk/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'DE' :
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_de/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NL' :
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/nl_nl/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'NO' :
				$klarna_kpm_part_payment_icon = false;
				break;
			case 'FI' :
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/fi_fi/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'SE' :
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/sv_se/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			case 'AT' :
				$klarna_kpm_part_payment_icon = 'https://cdn.klarna.com/1.0/shared/image/generic/logo/de_at/basic/blue-black.png?width=100&eid=' . $this->get_eid();
				break;
			default:
				$klarna_kpm_part_payment_icon = '';
		}
		
		return $klarna_kpm_part_payment_icon;

	}
			 
} // End class WC_Gateway_Klarna_KPM_Part_Payment