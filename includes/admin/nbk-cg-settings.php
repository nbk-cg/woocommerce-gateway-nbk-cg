<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wc_Nbk_Cg_settings',
	[
		'enabled'                             => [
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-nbk-cg' ),
			'label'       => __( 'Enable Nbk-CG', 'woocommerce-gateway-nbk-cg' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		],
		'title'                               => [
			'title'       => __( 'Title', 'woocommerce-gateway-nbk-cg' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-nbk-cg' ),
			'default'     => __( 'Credit Card', 'woocommerce-gateway-nbk-cg' ),
			'desc_tip'    => true,
		],
		'description'                         => [
			'title'       => __( 'Description', 'woocommerce-gateway-nbk-cg' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-nbk-cg' ),
			'default'     => __( 'Pay with your credit card via Nbk-CG.', 'woocommerce-gateway-nbk-cg' ),
			'desc_tip'    => true,
		],

		'account_id'                     => [
			'title' => __( 'Nbk-CG Account Id', 'woocommerce-gateway-nbk-cg' ),
			'type'  => 'text',
		],

		'testmode'                            => [
			'title'       => __( 'Test mode', 'woocommerce-gateway-nbk-cg' ),
			'label'       => __( 'Enable Test Mode', 'woocommerce-gateway-nbk-cg' ),
			'type'        => 'checkbox',
			'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce-gateway-nbk-cg' ),
			'default'     => 'yes',
			'desc_tip'    => true,
		],

        'test_client_id'                => [
            'title'       => __( 'Test Client Id', 'woocommerce-gateway-nbk-cg' ),
            'type'        => 'text',
            'description' => __( 'Get your API keys from your Nbk-CG account. Invalid values will be rejected. Only values starting with "pk_test_" will be saved.', 'woocommerce-gateway-nbk-cg' ),
            'default'     => '',
            'desc_tip'    => true,
        ],
        'test_client_secret'                     => [
            'title'       => __( 'Test Client Secret', 'woocommerce-gateway-nbk-cg' ),
            'type'        => 'password',
            'description' => __( 'Get your API keys from your Nbk-CG account. Invalid values will be rejected. Only values starting with "sk_test_" or "rk_test_" will be saved.', 'woocommerce-gateway-nbk-cg' ),
            'default'     => '',
            'desc_tip'    => true,
        ],
        'client_id'                     => [
            'title'       => __( 'Live Client Id', 'woocommerce-gateway-nbk-cg' ),
            'type'        => 'text',
            'description' => __( 'Get your API keys from your Nbk-CG account. Invalid values will be rejected. Only values starting with "pk_live_" will be saved.', 'woocommerce-gateway-nbk-cg' ),
            'default'     => '',
            'desc_tip'    => true,
        ],
        'client_secret'                          => [
            'title'       => __( 'Live Client Secret', 'woocommerce-gateway-nbk-cg' ),
            'type'        => 'password',
            'description' => __( 'Get your API keys from your Nbk-CG account. Invalid values will be rejected. Only values starting with "sk_live_" or "rk_live_" will be saved.', 'woocommerce-gateway-nbk-cg' ),
            'default'     => '',
            'desc_tip'    => true,
        ],
        'regions'                          => [
            'title'       => __( 'Region', 'woocommerce-gateway-nbk-cg' ),
            'type'        => 'text',
            'description' => __( 'Get your API keys from your Nbk-CG account. Invalid values will be rejected. Only values starting with "sk_live_" or "rk_live_" will be saved.', 'woocommerce-gateway-nbk-cg' ),
            'default'     => '',
            'desc_tip'    => true,
        ],
	]
);
