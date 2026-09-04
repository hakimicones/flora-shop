<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( $this, 'show_notices' ) );
        add_action( 'init', array( 'Flora_Admin', 'handle_import' ) );

        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-dashboard.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-products.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-packs.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-shipping.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-promotions.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-orders.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-settings.php';
    }

    public static function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['flora_action'] ) || 'import_wilayas' !== $_POST['flora_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        Flora_Helpers::verify_nonce( 'flora_import_wilayas' );

        $sql_file = isset( $_POST['sql_file_path'] ) ? sanitize_text_field( wp_unslash( $_POST['sql_file_path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $result   = Flora_Importer::run( $sql_file );

        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=error' ) );
            exit;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=import_done&wilayas=' . $result['wilayas'] . '&communes=' . $result['communes'] ) );
        exit;
    }

    public function register_menus() {
        add_menu_page(
            __( 'Flora Shop', 'flora-shop' ),
            __( 'Flora Shop', 'flora-shop' ),
            'manage_options',
            'flora-shop',
            array( 'Flora_Admin_Dashboard', 'render' ),
            'dashicons-cart',
            26
        );

        add_submenu_page(
            'flora-shop',
            __( 'Tableau de bord', 'flora-shop' ),
            __( 'Tableau de bord', 'flora-shop' ),
            'manage_options',
            'flora-shop',
            array( 'Flora_Admin_Dashboard', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Produits', 'flora-shop' ),
            __( 'Produits', 'flora-shop' ),
            'manage_options',
            'flora-products',
            array( 'Flora_Admin_Products', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Packs', 'flora-shop' ),
            __( 'Packs', 'flora-shop' ),
            'manage_options',
            'flora-packs',
            array( 'Flora_Admin_Packs', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Promotions', 'flora-shop' ),
            __( 'Promotions', 'flora-shop' ),
            'manage_options',
            'flora-promotions',
            array( 'Flora_Admin_Promotions', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Transport', 'flora-shop' ),
            __( 'Transport', 'flora-shop' ),
            'manage_options',
            'flora-shipping',
            array( 'Flora_Admin_Shipping', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Commandes', 'flora-shop' ),
            __( 'Commandes', 'flora-shop' ),
            'manage_options',
            'flora-orders',
            array( 'Flora_Admin_Orders', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Paramètres', 'flora-shop' ),
            __( 'Paramètres', 'flora-shop' ),
            'manage_options',
            'flora-settings',
            array( 'Flora_Admin_Settings', 'render' )
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'flora-' ) === false ) {
            return;
        }

        wp_enqueue_style( 'flora-admin', FLORA_SHOP_URL . 'admin/css/admin.css', array(), FLORA_SHOP_VERSION );
        wp_enqueue_media();
        wp_enqueue_script( 'flora-admin', FLORA_SHOP_URL . 'admin/js/admin.js', array( 'jquery', 'media-editor' ), FLORA_SHOP_VERSION, true );

        wp_localize_script( 'flora-admin', 'floraAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'flora-shop/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    public function show_notices() {
        if ( isset( $_GET['flora_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $notice = sanitize_text_field( wp_unslash( $_GET['flora_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
            $message = '';
            $type    = 'success';

            switch ( $notice ) {
                case 'product_saved':
                    $message = __( 'Produit enregistré avec succès.', 'flora-shop' );
                    break;
                case 'product_deleted':
                    $message = __( 'Produit supprimé.', 'flora-shop' );
                    break;
                case 'pack_saved':
                    $message = __( 'Pack enregistré avec succès.', 'flora-shop' );
                    break;
                case 'pack_deleted':
                    $message = __( 'Pack supprimé.', 'flora-shop' );
                    break;
                case 'order_updated':
                    $message = __( 'Commande mise à jour.', 'flora-shop' );
                    break;
                case 'shipping_saved':
                    $message = __( 'Tarifs de transport enregistrés.', 'flora-shop' );
                    break;
                case 'promo_saved':
                    $message = __( 'Promotion enregistrée.', 'flora-shop' );
                    break;
                case 'promo_deleted':
                    $message = __( 'Promotion supprimée.', 'flora-shop' );
                    break;
                case 'settings_saved':
                    $message = __( 'Paramètres enregistrés.', 'flora-shop' );
                    break;
                case 'import_done':
                    $wilayas  = isset( $_GET['wilayas'] ) ? absint( $_GET['wilayas'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
                    $communes = isset( $_GET['communes'] ) ? absint( $_GET['communes'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
                    $message  = sprintf( __( 'Import terminé : %d wilayas et %d communes importées.', 'flora-shop' ), $wilayas, $communes );
                    break;
                case 'error':
                    $message = __( 'Une erreur est survenue.', 'flora-shop' );
                    $type    = 'error';
                    break;
            }

            if ( $message ) {
                echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            }
        }
    }
}

new Flora_Admin();
