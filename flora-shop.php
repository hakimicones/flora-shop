<?php
/**
 * Plugin Name: Flora Shop
 * Plugin URI: https://flora-shop.dz
 * Description: Solution e-commerce complète avec gestion de produits, packs, promotions BXGY, transport dynamique (Wilaya/Commune).
 * Version: 1.1.0
 * Author: icones software
 * Author URI: https://icones-software.dz
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flora-shop
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FLORA_SHOP_VERSION', '1.2.0' );
define( 'FLORA_SHOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLORA_SHOP_URL', plugin_dir_url( __FILE__ ) );
define( 'FLORA_SHOP_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLORA_SHOP_PAGE_ID', 7157 );
define( 'FLORA_CHECKOUT_PAGE_ID', 7159 );
define( 'FLORA_ORDER_CONFIRM_PAGE_ID', 7160 );
define( 'FLORA_CART_PAGE_ID', 7158 );
define( 'FLORA_PRODUCT_PAGE_ID', 7161 );

require_once FLORA_SHOP_PATH . 'inc/class-flora-helpers.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-db.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-cart.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-activator.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-importer.php';

if ( is_admin() ) {
    require_once FLORA_SHOP_PATH . 'admin/class-flora-admin.php';
}

if ( ! is_admin() ) {
    require_once FLORA_SHOP_PATH . 'public/class-flora-public.php';
}

require_once FLORA_SHOP_PATH . 'api/class-flora-rest-controller.php';

register_activation_hook( __FILE__, array( 'Flora_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Flora_Activator', 'deactivate' ) );

add_action( 'init', function () {
    load_plugin_textdomain( 'flora-shop', false, dirname( FLORA_SHOP_BASENAME ) . '/languages' );
} );

add_action( 'rest_api_init', function () {
    $controller = new Flora_REST_Controller();
    $controller->register_routes();
} );

add_action( 'plugins_loaded', array( 'Flora_Cart', 'init' ) );
add_action( 'plugins_loaded', array( 'Flora_Activator', 'maybe_upgrade' ) );
