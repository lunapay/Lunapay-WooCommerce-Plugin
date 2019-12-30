<?php

/**
 * Plugin Name: LunaPay for WooCommerce
 * Plugin URI: 
 * Description: LunaPay Payment Gateway | <a href="https://app.lunapay.co/login" target="_blank">Sign up Now</a>.
 * Author: Fintech Capital Group Berhad
 * Author URI: https://github.com/lunapay/Lunapay-WooCommerce-Plugin
 * Version: 3.21.3
 * Requires PHP: 5.2.4
 * Requires at least: 4.6
 * License: GPLv3
 * Text Domain: lpw
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 3.4.5
 */


/* Load LunaPay Class */
if (!class_exists('LunaPayWooCommerceAPI') && !class_exists('LunaPayWooCommerceWPConnect')) {
    require('includes/LunaPay_API.php');
    require('includes/LunaPay_WPConnect.php');
}

function lpw_plugin_uninstall()
{
    global $wpdb;

    /* Remove rows that created from previous version */
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'lunapay_fwoo_%'");

    delete_option('lunapay_fpx_banks');
    delete_option('lunapay_fpx_banks_last');
}
register_uninstall_hook(__FILE__, 'lpw_plugin_uninstall');

/*
 *  Add settings link on plugin page
 */

function lpw_plugin_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=lunapay">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin_action_link = 'plugin_action_links_'.plugin_basename(__FILE__);
add_filter($plugin_action_link, 'lpw_plugin_settings_link');

function lpw_fallback_notice()
{
    $message = '<div class="error">';
    $message .= '<p>' . __('lunapay for WooCommerce depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'lpw') . '</p>';
    $message .= '</div>';
    echo $message;
}

/**
 * lunapay plugin function
 *
 * @return mixed
 */
function lpw_load()
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'lpw_fallback_notice');
        return;
    }
    // Load language
    load_plugin_textdomain('lpw', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    /**
     * Add lunapay gateway to ensure WooCommerce can load it
     *
     * @param array $methods
     * @return array
     */
    function lpw_add_gateway($methods)
    {
        /* Retained for compatibility with previous version */
        $methods[] = 'WC_lunapay_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'lpw_add_gateway');
    add_filter('lpw_settings_value', array('WC_lunapay_Gateway', 'settings_value'));

    /**
     * Define the lunapay gateway
     *
     */
    class WC_lunapay_Gateway extends WC_Payment_Gateway
    {

        /** @var bool Whether or not logging is enabled */
        public static $log_enabled = false;

        /** @var WC_Logger Logger instance */
        public static $log = false;

        /**
         * Construct the lunapay for WooCommerce class
         *
         * @global mixed $woocommerce
         */
        public function __construct()
        {
            //global $woocommerce;

            $this->id = 'lunapay';
//             $this->icon = apply_filters('lpw_icon', plugins_url('assets/lunaPa.png', __FILE__));
			$this->icon = apply_filters('lpw_icon','https://lunapay.sgp1.digitaloceanspaces.com/$2y$10$YvpcwFNZR2ogtM1AWzqqVu0FWEYyye9haRrHiTBytz.PgQFQ89QE2.png');
            $this->method_title = __('Lunapay', 'lpw');
            $this->debug = 'yes' === $this->get_option('debug', 'no');

            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            /* Enable settings value alteration through plugins/themes function */
            $this->settings = apply_filters('lpw_settings_value', $this->settings);

            /* Customize checkout button label */
            $this->order_button_text = __($this->settings['checkout_label'], 'lpw');

            // Define user setting variables.
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];

          
            $this->client_id = $this->settings['client_id'];
			$this->client_secret = $this->settings['client_secret'];
            $this->luna_signature = $this->settings['luna_signature'];
			$this->group_id = $this->settings['group_id'];
			
            $this->clearcart = $this->settings['clearcart'];
            $this->notification = $this->settings['notification'];
            $this->custom_error = $this->settings['custom_error'];

            /* Enable Premium Features */
            $this->has_fields = $this->settings['has_fields'];
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                $this->notification = '0';
                add_filter('lpw_url', array($this, 'url'));
            }

            $this->live = $this->settings['live'];
            if (isset($this->live) && $this->live === 'yes') {
                $this->current_url = 'https://uat.lunapay.com';
            }else{
				 $this->current_url = 'https://sandbox.lunapay.com';
            }

            // Payment instruction after payment
            $this->instructions = $this->settings['instructions'];

            add_action('woocommerce_thankyou_lunapay', array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

            self::$log_enabled = $this->debug;

            /* Set Receipt Page */
            add_action('woocommerce_receipt_lunapay', array(&$this,'receipt_page'));

            // Save setting configuration
            add_action('woocommerce_update_options_payment_gateways_lunapay', array($this,'process_admin_options'));

            // Payment listener/API hook
          //  add_action('woocommerce_api_wc_lunapay_gateway', array($this,'check_ipn_response'));
          //  add_action('woocommerce_api_wc_lunapay_gateway', 'test');
            add_action('woocommerce_api_wc_lunapay_gateway', array($this,'check_ipn_response'));


            /* Display error if API Key is not set */
            $this->client_id == '' ? add_action('admin_notices', array(&$this,'client_id_missing_message')) : '';

            /* Display warning if Collection ID is not set */
            $this->client_secret == '' ? add_action('admin_notices', array(&$this,'lunakey_missing_message')) : '';

        }

        public static function settings_value($settings)
        {
            return $settings;
        }

        public function url($url)
        {
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                return $url . '?auto_submit=true';
            }
            return $url;
        }

        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use()
        {
            if (!in_array(get_woocommerce_currency(), array(
                    'MYR'
                ))) {
                return false;
            }
            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options()
        {
            ?>
            <h3><?php
                _e('lunapay Payment Gateway', 'lpw'); ?></h3>
            <p><?php
                _e('lunapay Payment Gateway works by sending the user to lunapay for payment. ', 'lpw'); ?></p>
            <p><?php
                _e('To immediately reduce stock on add to cart, we strongly recommend you to use this plugin. ', 'lpw'); ?><a href="http://bit.ly/1UDOQKi" target="_blank">
                    WooCommerce Cart Stock Reducer</a></p>
            <p><?php
                _e('You may do a bill requery in-case order is not updated. ', 'lpw'); ?><a href="options-general.php?page=lpw-requery-tool" target="_blank">
                    lpw Tool</a></p>
            <table class="form-table">
                <?php
                    $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Gateway Settings Form Fields.
         *
         */
        public function init_form_fields()
        {
            $this->form_fields = include('includes/settings-lunaPay.php');
        }

        public function payment_fields()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }
            if (isset($this->has_fields) && $this->has_fields === 'yes') {
                $rbody = get_option('lunapay_fpx_banks');
                $date = get_option('lunapay_fpx_banks_last');

                if (!$rbody || ($date !== date('d/m/Y/H'))) {
					 $connect = new lunapayWooCommerceWPConnect($this->client_secret, $this->current_url);
                    $lunapay = new lunapayWooCommerceAPI($connect);
                    list($rheader, $rbody) = $lunapay->toArray($lunapay->getFpxBanks());

                    update_option('lunapay_fpx_banks', $rbody);
                    update_option('lunapay_fpx_banks_last', date('d/m/Y/H'));
                }
            
                $bank_name = apply_filters('lpw_bank_name_list', lunapayBankName::get());
                
                /* Allow theme/plugin to override the way form is represented */
                if (has_action('lpw_payment_fields')) :
                    do_action('lpw_payment_fields', $rbody, $bank_name);
                else :
                    ?>
                <p class="form-row validate-required">
                    <label><?php echo 'Choose Bank'; ?> <span class="required">*</span></label>
                    <select name="lunapay_bank">
                        <option value="" disabled selected>Choose your bank</option>
                    <?php
                    foreach ($bank_name as $key => $value) {
                        foreach ($rbody['banks'] as $bank) {
                            if ($bank['name'] === $key && $bank['active']) {
                                ?><option value="<?php echo $bank['name']; ?>"><?php echo $bank_name[$bank['name']] ? strtoupper($bank_name[$bank['name']]) : $bank['name']; ?></option><?php
                            }
                        }
                    }
                    ?>
                    <option value="OTHERS">OTHERS</option>
                    </select>
                </p> 
                    <?php
                endif;
            }
        }

        /**
         * This to maintain compatibility with WooCommerce 2.x
         * @return array string
         */
        public static function get_order_data($order)
        {
            global $woocommerce;
            if (version_compare($woocommerce->version, '3.0', "<")) {
                $data = array(
                    'first_name' => !empty($order->billing_first_name) ? $order->billing_first_name : $order->shipping_first_name,
                    'last_name' => !empty($order->billing_last_name) ? $order->billing_last_name : $order->shipping_last_name,
                    'email' => $order->billing_email,
                    'phone' => $order->billing_phone,
                    'total' => $order->order_total,
                    'id' => $order->id,
                );
            } else {
                $data = array(
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'total' => $order->get_total(),
                    'id' => $order->get_id(),
                );
                $data['first_name'] = empty($data['first_name']) ? $order->get_shipping_first_name() : $data['first_name'];
                $data['last_name'] = empty($data['last_name']) ? $order->get_shipping_last_name() : $data['last_name'];
            }

            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);

            /*
             * Compatibility with some themes
             */
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('_shipping_email');
            $data['email'] = !empty($data['email']) ? $data['email'] : $order->get_meta('shipping_email');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('_shipping_phone');
            $data['phone'] = !empty($data['phone']) ? $data['phone'] : $order->get_meta('shipping_phone');

            return $data;
        }

        /**
         * Logging method.
         * @param string $message
         */
        public static function log($message)
        {
            if (self::$log_enabled) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('lunapay', $message);
            }
        }

        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function lunapay_order_error($order)
        {
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'lpw') . '</p>';
            $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'lpw') . '</a>';
            return $html;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            

            global $woocommerce;

            $connect = new lunapayWooCommerceWPConnect($this->client_secret, $this->current_url);
            $lunapay = new lunapayWooCommerceAPI($connect);

            if ($this->clearcart === 'yes') {
                /* WC()->cart->empty_cart(); */
                $woocommerce->cart->empty_cart();
            }

            $order = new WC_Order($order_id);
            $order_data = self::get_order_data($order);
            $order_currency = $order->get_currency();
            $order_key = $order->get_order_key();
			$quantity_looping = 0;

            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item) {
                    if ($item['qty']) {
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
						$quantity_looping = $quantity_looping + 1;
						
                    }
                }
            }

            $description = sprintf(__('Order %s', 'woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);

            $tokenParameter = array(
                'client_id' => $this->client_id,
				'client_secret' => $this->client_secret
            );

            $token = $lunapay->getToken($tokenParameter);
			
			
//  			var_dump($token);
//  			exit();

				 $paymentParameter = array(
					'amount' => $order_data['total'],
					'currency' => $order_currency,
					'reference_no' => $order_data['id'],
					'item'=> mb_substr(apply_filters('lpw_description', $description), 0, 199),
					'quantity' => $quantity_looping,
					'description' => implode('|',$item_names),
					'phone'=> $order_data['phone'],
					'email' => $order_data['email'],
					'name' => $order_data['name'],
					'callback_url' => home_url('?wc-api=WC_lunapay_Gateway'),
					'redirect_url' => home_url('?wc-api=WC_lunapay_Gateway'),
					'cancel_url' => home_url('?wc-api=WC_lunapay_Gateway')
				 );
			
			if(!empty($this->group_id)){
				
				$paymentParameter['group_id'] = $this->group_id;
				
			}
			
			
			
			if (!empty($this->luna_signature)) {
				
			  list($payment_url, $payment_id) = $lunapay->sentPaymentSecure($token, $paymentParameter, $this->luna_signature);

			}else{
		
			  list($payment_url, $payment_id) = $lunapay->sentPayment($token, $paymentParameter);
				
        	}
			 list($paymentID, $status) = $lunapay->getPaymentStatus($token, $payment_id);
				return array(
					'result' => 'success',
					'redirect' => apply_filters('lpw_url', $payment_url)
				);
	}

        /**
         * Check for lunapay Response
         *
         * @access public
         * @return void
         */

        public function check_ipn_response()
        {

             @ob_clean();
         //   global $woocommerce;

            $data = lunapayWooCommerceWPConnect::afterpayment();
			
//  			var_dump($data);
//  			exit();

            $connect = new lunapayWooCommerceWPConnect($this->client_secret, $this->current_url);
            $lunapay = new lunapayWooCommerceAPI($connect);


            if($data['status'] === 'Paid'){

             $order_id = $data['order_id'];
             $payment_id = $data['payment_id'];


              $tokenParameter = array(
                'client_id' => $this->client_id,
				'client_secret' => $this->client_secret
            );

            $token = $lunapay->getToken($tokenParameter);

            list($paymentID, $status) = $lunapay->getPaymentStatus($token, $payment_id);

            if($status === 'Paid'){

             $order = new WC_Order( $order_id );
             $order->update_status( 'completed' );
             $redirectpath = $order->get_checkout_order_received_url();

             $data ='redirect';

             if ($data === 'redirect') {
                //echo 'wq';
                wp_redirect($redirectpath);
                exit;
            } else {
                echo 'RECEIVEOK';
                exit;
            }
            }else{
				
				 $order_id = $data['order_id'];
                 $payment_id = $data['payment_id'];


                $order = new WC_Order( $order_id );
                $order->add_order_note('Payment Status: CANCELLED BY USER' . '<br>Payment ID: ' . $data['payment_id']);
                wc_add_notice(__('ERROR: ', 'woothemes') . $this->custom_error, 'error');
                $redirectpath = $order->get_cancel_order_url();

                $data ='redirect';

                if ($data === 'redirect') {
                //echo 'wq';
                wp_redirect($redirectpath);
                exit;
                } else {
                    echo 'RECEIVEOK';
                    exit;
                }	
			}


            }
            else{

             $order_id = $data['order_id'];
             $payment_id = $data['payment_id'];


                $order = new WC_Order( $order_id );
                $order->add_order_note('Payment Status: CANCELLED BY USER' . '<br>Payment ID: ' . $data['payment_id']);
                wc_add_notice(__('ERROR: ', 'woothemes') . $this->custom_error, 'error');
                $redirectpath = $order->get_cancel_order_url();

                $data ='redirect';

                if ($data === 'redirect') {
                //echo 'wq';
                wp_redirect($redirectpath);
                exit;
                } else {
                    echo 'RECEIVEOK';
                    exit;
                }


            }

        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && 'offline' === $order->get_payment_method() && $order->has_status('on-hold')) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }

      
        /**
         * Adds error message when not configured the Client ID.
         *
         */
        public function client_id_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Notice!</strong> Please enter your LunaPay Client ID. %s Learn how to get client ID %s', 'lpw'), '<a href="https://docs.lunapay.com/doc/flow">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }
		
		 public function lunakey_missing_message()
        {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Notice!</strong> Please enter your Lunakey. %s Learn how to get Lunakey %s', 'lpw'), '<a href="https://docs.lunapay.com/doc/flow">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }
    }
}
add_action('plugins_loaded', 'lpw_load', 0);

function lpw_clear_cron()
{
    /* Removed hook that registered from previous version */
    wp_clear_scheduled_hook('lunapay_bills_invalidator');
}
register_deactivation_hook(__FILE__, 'lpw_clear_cron');
add_action('upgrader_process_complete', 'lpw_clear_cron', 10, 2);

/*
 * Display lunapay Bills URL on the Order Admin Page
 */
function lpw_add_bill_id($order)
{
    $order_data = WC_lunapay_Gateway::get_order_data($order);
    $bill_id = get_post_meta($order_data['id'], '_transaction_id', true);?>
    <span class="description"><?php echo wc_help_tip(__('You may refer to Custom Fields to get more information', 'lpw')); ?> <?php echo 'Bill ID: ' . $bill_id; ?></span><?php
}
add_action('woocommerce_order_item_add_action_buttons', 'lpw_add_bill_id');
