<?php
/**
 * Plugin Name: Flora Shop
 * Plugin URI: https://flora-shop.dz
 * Description: Solution e-commerce complète avec gestion de produits, packs, promotions BXGY, transport dynamique (Wilaya/Commune) et deux méthodes de livraison.
 * Version: 1.6.8
 * Author: A.Hakim NOUAS
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

// Constantes globales : version, chemins et basename du plugin.
define( 'FLORA_SHOP_VERSION', '1.6.8' );
define( 'FLORA_SHOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'FLORA_SHOP_URL', plugin_dir_url( __FILE__ ) );
define( 'FLORA_SHOP_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLORA_SHOP_FILE', __FILE__ );

// Chargement des fichiers communs (helpers, BDD, panier, activator, importateur).
require_once FLORA_SHOP_PATH . 'inc/class-flora-helpers.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-db.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-cart.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-activator.php';
require_once FLORA_SHOP_PATH . 'inc/class-flora-importer.php';

// Chargement conditionnel du contrôleur admin (back-office uniquement).
if ( is_admin() ) {
    require_once FLORA_SHOP_PATH . 'inc/class-flora-updates.php';
    require_once FLORA_SHOP_PATH . 'admin/class-flora-admin.php';
}

// Chargement conditionnel du thème public (front-office uniquement).
if ( ! is_admin() ) {
    require_once FLORA_SHOP_PATH . 'public/class-flora-public.php';
}

// Chargement du contrôleur REST API (accessible admin + public).
require_once FLORA_SHOP_PATH . 'api/class-flora-rest-controller.php';

// Hooks d'activation / désactivation du plugin.
register_activation_hook( __FILE__, array( 'Flora_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Flora_Activator', 'deactivate' ) );

// Chargement du fichier de traduction (text domain) au démarrage de WordPress.
add_action( 'init', function () {
    load_plugin_textdomain( 'flora-shop', false, dirname( FLORA_SHOP_BASENAME ) . '/languages' );
} );

// Enregistrement des routes de l'API REST Flora.
add_action( 'rest_api_init', function () {
    $controller = new Flora_REST_Controller();
    $controller->register_routes();
} );

// Initialisation du panier Flora et vérification de la mise à jour de version.
add_action( 'plugins_loaded', array( 'Flora_Cart', 'init' ) );
add_action( 'plugins_loaded', array( 'Flora_Activator', 'maybe_upgrade' ) );

// Vérification des mises à jour du plugin via les Releases GitHub (update checker).
if ( is_admin() ) {
    new Flora_Updates();
}
