<?php
/**
 * Script de désinstallation du plugin Flora Shop.
 *
 * Supprime toutes les tables de la base de données et les options
 * enregistrées lors de la désinstallation complète du plugin.
 */

// Garde : n'exécute le nettoyage que si WordPress invoque bien la désinstallation.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Préfixe des tables gérées par le plugin.
$prefix = $wpdb->prefix . 'flora_';

// Liste de toutes les tables à supprimer (ordre inversé pour respecter les clés étrangères).
$tables = array(
    $prefix . 'pack_translations',
    $prefix . 'product_translations',
    $prefix . 'tag_items',
    $prefix . 'tags',
    $prefix . 'categories',
    $prefix . 'order_details',
    $prefix . 'orders',
    $prefix . 'shipping_rates',
    $prefix . 'promotions',
    $prefix . 'catalog_discounts',
    $prefix . 'pack_products',
    $prefix . 'packs',
    $prefix . 'products',
    $prefix . 'communes',
    $prefix . 'wilayas',
);

// Suppression de chaque table du plugin.
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Liste des options à supprimer du tableau wp_options.
$options = array(
    'flora_shop_version',
    'flora_currency',
    'flora_free_shipping_threshold',
    'flora_product_discounts',
    'flora_cart_discounts',
    'flora_last_order_seq',
    'flora_languages',
    'flora_default_language',
    'flora_show_order_email',
    'flora_custom_css',
    'flora_shipping_methods',
);

// Suppression de chaque option du plugin.
foreach ( $options as $option ) {
    delete_option( $option );
}
