<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe principale de l'administration : enregistre les menus et les pages
 * admin du plugin (tableau de bord, produits, packs, promotions, transport,
 * commandes, paramètres), charge les classes filles, gère l'import des wilayas
 * depuis l'interface, et affiche les notifications de retour (success/erreur).
 * Contrôle d'accès par capability 'manage_options' ; nonce vérifié via
 * Flora_Helpers::verify_nonce() pour les traitements POST.
 */
class Flora_Admin {

    public function __construct() {
        // Initialise les hooks admin et charge les classes des pages admin filles.
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( $this, 'show_notices' ) );
        add_action( 'init', array( 'Flora_Admin', 'handle_import' ) );

        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-dashboard.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-products.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-packs.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-categories.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-tags.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-shipping.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-promotions.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-catalog-discounts.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-orders.php';
        require_once FLORA_SHOP_PATH . 'admin/class-flora-admin-settings.php';
    }

    public static function handle_import() {
        // Point d'entrée (init) de l'import SQL des wilayas/communes déclenché depuis la page Transport.
        // Vérifie la capability avant toute action.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Attend une action POST dédiée à l'import ; sinon on n'entre pas dans le traitement.
        if ( ! isset( $_POST['flora_action'] ) || 'import_wilayas' !== $_POST['flora_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        // Vérification du nonce qui protège le formulaire d'import.
        Flora_Helpers::verify_nonce( 'flora_import_wilayas' );

        // Assainit le chemin du fichier SQL transmis via POST avant de le passer à l'importer.
        $sql_file = isset( $_POST['sql_file_path'] ) ? sanitize_text_field( wp_unslash( $_POST['sql_file_path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $result   = Flora_Importer::run( $sql_file );

        if ( is_wp_error( $result ) ) {
            // Conserve le détail de l'erreur (message du WP_Error) pour l'afficher après redirection.
            set_transient( 'flora_import_error', $result->get_error_message(), 60 );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=error' ) );
            exit;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=import_done&wilayas=' . $result['wilayas'] . '&communes=' . $result['communes'] ) );
        exit;
    }

    public function register_menus() {
        // Enregistre le menu principal et les sous-menus admin (tous protégés par 'manage_options').
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
            __( 'Catégories', 'flora-shop' ),
            __( 'Catégories', 'flora-shop' ),
            'manage_options',
            'flora-categories',
            array( 'Flora_Admin_Categories', 'render' )
        );

        add_submenu_page(
            'flora-shop',
            __( 'Étiquettes', 'flora-shop' ),
            __( 'Étiquettes', 'flora-shop' ),
            'manage_options',
            'flora-tags',
            array( 'Flora_Admin_Tags', 'render' )
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
        // Charge les assets CSS/JS (et la médiathèque) uniquement sur les écrans d'admin du plugin.
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
        // Affiche les messages de retour admin (succès/erreur) passés via le paramètre 'flora_notice' de l'URL.
        if ( isset( $_GET['flora_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            // Lecture en GET non fiable : on assainit la valeur avant de l'utiliser dans un switch.
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
                case 'catalog_discount_saved':
                    $message = __( 'Remise enregistrée.', 'flora-shop' );
                    break;
                case 'catalog_discount_deleted':
                    $message = __( 'Remise supprimée.', 'flora-shop' );
                    break;
                case 'category_saved':
                    $message = __( 'Catégorie enregistrée.', 'flora-shop' );
                    break;
                case 'category_deleted':
                    $message = __( 'Catégorie supprimée.', 'flora-shop' );
                    break;
                case 'tag_saved':
                    $message = __( 'Étiquette enregistrée.', 'flora-shop' );
                    break;
                case 'tag_deleted':
                    $message = __( 'Étiquette supprimée.', 'flora-shop' );
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
                    // Défaut : message générique, remplacé par le détail stocké lors d'un échec d'import SQL.
                    $message = __( 'Une erreur est survenue.', 'flora-shop' );
                    $type    = 'error';

                    $import_error = get_transient( 'flora_import_error' );
                    if ( $import_error ) {
                        delete_transient( 'flora_import_error' );
                        $message = $import_error;
                    }
                    break;
            }

            if ( $message ) {
                echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            }
        }
    }
}

new Flora_Admin();
