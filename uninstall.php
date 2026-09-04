<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$prefix = $wpdb->prefix . 'flora_';

$tables = array(
    $prefix . 'order_details',
    $prefix . 'orders',
    $prefix . 'shipping_rates',
    $prefix . 'promotions',
    $prefix . 'pack_products',
    $prefix . 'packs',
    $prefix . 'products',
    $prefix . 'communes',
    $prefix . 'wilayas',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

$options = array(
    'flora_shop_version',
    'flora_currency',
    'flora_free_shipping_threshold',
    'flora_product_discounts',
    'flora_cart_discounts',
    'flora_last_order_seq',
);

foreach ( $options as $option ) {
    delete_option( $option );
}
