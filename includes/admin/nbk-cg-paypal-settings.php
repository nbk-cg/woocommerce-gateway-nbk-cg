<?php

if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_Nbk_Cg_paypal_settings',
    [
        'geo_target' => [
            'description' => __('Customer Geography: China', 'woocommerce-gateway-nbk-cg'),
            'type' => 'title',
        ],
        'guide' => [
            'description' => __('<a href="https://stripe.com/payments/payment-methods-guide#alipay" target="_blank">Payment Method Guide</a>', 'woocommerce-gateway-nbk-cg'),
            'type' => 'title',
        ],
        'activation' => [
            'description' => __('Must be activated from your Stripe Dashboard Settings <a href="https://dashboard.stripe.com/account/payments/settings" target="_blank">here</a>', 'woocommerce-gateway-nbk-cg'),
            'type' => 'title',
        ],
        'enabled' => [
            'title' => __('Enable/Disable', 'woocommerce-gateway-nbk-cg'),
            'label' => __('Enable Nbk-CG PayPal', 'woocommerce-gateway-nbk-cg'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no',
        ],
        'title' => [
            'title' => __('Title', 'woocommerce-gateway-nbk-cg'),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-nbk-cg'),
            'default' => __('PayPal', 'woocommerce-gateway-nbk-cg'),
            'desc_tip' => true,
        ],
        'description' => [
            'title' => __('Description', 'woocommerce-gateway-nbk-cg'),
            'type' => 'text',
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-nbk-cg'),
            'default' => __('You will be redirected to Alipay.', 'woocommerce-gateway-nbk-cg'),
            'desc_tip' => true,
        ],
        'webhook' => [
            'title' => __('Webhook Endpoints', 'woocommerce-gateway-nbk-cg'),
            'type' => 'title',
            /* translators: webhook URL */
            'description' => $this->display_admin_settings_webhook_description(),
        ],
    ]
);
