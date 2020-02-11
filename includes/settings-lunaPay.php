<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for LunaPay for WooCommerce.
 */
return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'lpw'),
        'type' => 'checkbox',
        'label' => __('Enable LunaPay', 'lpw'),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'lpw'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'lpw'),
        'default' => __('LunaPay Internet Banking & Boost e-Wallet', 'lpw')
    ),
    'description' => array(
        'title' => __('Description', 'lpw'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'lpw'),
        'default' => __('Pay with <strong>Maybank2u, CIMB Clicks, Bank Islam, RHB, Hong Leong Bank, Bank Muamalat, Public Bank, Alliance Bank, Affin Bank, AmBank, Bank Rakyat, UOB, Standard Chartered, Boost e-Wallet</strong>. ', 'lpw')
    ),
    'client_id' => array(
        'title' => __('Client ID', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Example : 23546345',
        'description' => __('Please enter your LunaPay Client ID. ', 'lpw') . ' ' . sprintf(__('<br/> Login to LunaPay >> Go to the top right of the menu >> Click profile button >> Navigate to API tab', 'lpw'), '', ''),
        'default' => ''
    ),
    'client_secret' => array(
        'title' => __('Lunakey', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Example : df23hjhlv234ug2i53vv32423hj86kjg4k89jasd',
        'description' => __('Please enter your Lunakey.', 'lpw') . ' ' . sprintf(__('<br/> Login to LunaPay >> Go to the top right of the menu >> Click profile button >> Navigate to API tab', 'lpw'), '', ''),
        'default' => ''
    ),
	'luna_signature' => array(
        'title' => __('Luna-signature Key', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Optional',
        'description' => __('', 'lpw') . ' ' . sprintf(__('', 'lpw'), '', ''),
        'default' => '',
		'desc_tip' => 'Enable this will redirect to secure payment page',
    ),
	'group_id' => array(
        'title' => __('Group ID', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Optional',
        'description' => __('', 'lpw') . ' ' . sprintf(__('','lpw'), '', ''),
        'default' => '',
		'desc_tip' => 'Added to default group if group_id is not present',
    ),
    'clearcart' => array(
        'title' => __('Clear Cart Session', 'lpw'),
        'type' => 'checkbox',
        'label' => __('Tick to clear cart session on checkout', 'lpw'),
        'default' => 'no'
    ),
    'debug' => array(
        'title' => __('Debug Log', 'lpw'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'lpw'),
        'default' => 'no',
        'description' => sprintf(__('Log LunaPay events, such as IPN requests, inside <code>%s</code>', 'lpw'), wc_get_log_file_path('LunaPay'))
    ),
    'instructions' => array(
        'title' => __('Instructions', 'lpw'),
        'type' => 'textarea',
        'description' => __('Instructions that will be added to the thank you page and emails.', 'lpw'),
        'default' => '',
        'desc_tip' => true,
    ),
    'custom_error' => array(
        'title' => __('Error Message', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Example : You have cancelled the payment. Please make a payment!',
        'description' => __('Error message that will appear when customer cancel the payment.', 'lpw'),
        'default' => 'You have cancelled the payment. Please make a payment!'
    ),
    'checkout_label' => array(
        'title' => __('Checkout Label', 'lpw'),
        'type' => 'text',
        'placeholder' => 'Example: Pay with LunaPay',
        'description' => __('Button label on checkout.', 'lpw'),
        'default' => 'Pay with LunaPay'
    ),
    'live' => array(
        'title' => __('Live Server', 'lpw'),
        'type' => 'checkbox',
        'label' => __('Enable Live Server, disable will set to default sandbox server', 'lpw'),
        'default' => 'no',
        'desc_tip' => 'Live : https://uat.lunapay.com <br> Sandbox : http://sandbox.lunapay.com',
    ),
);
