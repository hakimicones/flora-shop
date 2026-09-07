<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe publique de flora-shop.
 *
 * Enregistre les shortcodes front-end du plugin (boutique, fiche produit/pack,
 * panier, checkout, confirmation) ainsi que les assets CSS/JS associés.
 * Les données localisées (URLs des pages, endpoint REST, nonce « wp_rest »
 * et traductions) sont transmises au script cart.js via wp_localize_script().
 */
class Flora_Public {

    // Enregistre les shortcodes et l'action d'ajout des assets front-end.
    public function __construct() {
        add_shortcode( 'flora_products', array( $this, 'shortcode_products' ) );
        add_shortcode( 'flora_product', array( $this, 'shortcode_product' ) );
        add_shortcode( 'flora_cart', array( $this, 'shortcode_cart' ) );
        add_shortcode( 'flora_checkout', array( $this, 'shortcode_checkout' ) );
        add_shortcode( 'flora_order_confirm', array( $this, 'shortcode_order_confirm' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    // enqueue_assets : charge CSS/JS uniquement sur les pages contenant un shortcode du plugin, puis localise la config (URLs, nonce, traductions) pour cart.js.
    public function enqueue_assets() {
        // Les pages Flora sont toujours couvertes. Pour tout autre type de post, on vérifie la présence d'un shortcode du plugin.
        if ( ! is_page() ) {
            global $post;
            if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'flora_products' ) && ! has_shortcode( $post->post_content, 'flora_product' ) && ! has_shortcode( $post->post_content, 'flora_cart' ) && ! has_shortcode( $post->post_content, 'flora_checkout' ) && ! has_shortcode( $post->post_content, 'flora_order_confirm' ) ) {
                return;
            }
        }

        wp_enqueue_style( 'flora-public', FLORA_SHOP_URL . 'public/css/public.css', array(), FLORA_SHOP_VERSION );
        wp_enqueue_script( 'flora-public', FLORA_SHOP_URL . 'public/js/cart.js', array( 'jquery' ), FLORA_SHOP_VERSION, true );

        wp_localize_script( 'flora-public', 'floraShop', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'flora-shop/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'pageUrls' => array(
                'checkout'      => get_permalink( FLORA_CHECKOUT_PAGE_ID ),
                'orderConfirm'  => get_permalink( FLORA_ORDER_CONFIRM_PAGE_ID ),
                'cart'          => get_permalink( FLORA_CART_PAGE_ID ),
                'shop'          => get_permalink( FLORA_SHOP_PAGE_ID ),
                'product'       => get_permalink( FLORA_PRODUCT_PAGE_ID ),
            ),
            'i18n'    => array(
                'added'      => __( 'Ajouté au panier !', 'flora-shop' ),
                'updated'    => __( 'Panier mis à jour.', 'flora-shop' ),
                'removed'    => __( 'Article retiré.', 'flora-shop' ),
                'error'      => __( 'Une erreur est survenue.', 'flora-shop' ),
                'confirm'    => __( 'Voulez-vous vraiment vider le panier ?', 'flora-shop' ),
                'processing' => __( 'Traitement en cours...', 'flora-shop' ),
                'added_free' => __( 'Article offert ajouté', 'flora-shop' ),
            ),
        ) );
    }

    // shortcode_products : charge les produits et packs puis affiche la grille de la boutique (view product-listing).
    public function shortcode_products( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 12,
        ), $atts );

        $db       = Flora_DB::get_instance();
        $products = $db->get_products( array( 'limit' => absint( $atts['limit'] ) ) );
        $packs    = $db->get_packs();

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/product-listing.php';
        return ob_get_clean();
    }

    // shortcode_product : bascule entre la fiche pack (paramètre « flora_pack ») et la fiche produit (paramètre « flora_product »),
    // et construit la config promo (build_promo_config) pour alimenter le récapitulatif de prix.
    public function shortcode_product() {
        $db = Flora_DB::get_instance();

        $product_slug = isset( $_GET['flora_product'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_product'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $pack_slug    = isset( $_GET['flora_pack'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_pack'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        // Branche « pack » : charge le pack, ses produits et ses promotions.
        if ( $pack_slug ) {
            $pack            = $db->get_pack_by_slug( $pack_slug );
            $pack_products   = array();
            $pack_promotions = array();
            $promo_config    = array();

            if ( $pack ) {
                $pack_products = $db->get_pack_products( $pack->id );
                $promo_config  = self::build_promo_config( $db->get_promotions( true ), 'pack', $pack->id, $pack->name );
                foreach ( $promo_config as $entry ) {
                    $pack_promotions[] = (object) $entry;
                }
            }

            ob_start();
            include FLORA_SHOP_PATH . 'public/views/pack-detail.php';
            return ob_get_clean();
        }

        // Branche « produit » : charge le produit et ses promotions.
        $product = null;
        if ( $product_slug ) {
            $product = $db->get_product_by_slug( $product_slug );
        }

        $product_promotions = array();
        $promo_config       = array();
        if ( $product ) {
            $promo_config      = self::build_promo_config( $db->get_promotions( true ), 'product', $product->id, $product->name );
            foreach ( $promo_config as $entry ) {
                $product_promotions[] = (object) $entry;
            }
        }

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/product-detail.php';
        return ob_get_clean();
    }

    // build_promo_config : transforme les promotions actives en configuration lisible par le récapitulatif
    // (réductions en % ou en montant, ou produit/pack offert) pour le produit ou pack déclencheur donné.
    private static function build_promo_config( $promotions, $trigger_type, $trigger_id, $trigger_name ) {
        $db    = Flora_DB::get_instance();
        $config = array();

        foreach ( $promotions as $promo ) {
            $promo_trigger = $promo->trigger_type ? $promo->trigger_type : 'product';
            if ( $promo_trigger !== $trigger_type || absint( $promo->trigger_product_id ) !== (int) $trigger_id ) {
                continue;
            }

            $trigger_qty = absint( $promo->trigger_qty );
            $reward_type = $promo->reward_type ? $promo->reward_type : 'free';
            $limit       = absint( $promo->limit_per_order );

            $entry = array(
                'id'          => absint( $promo->id ),
                'trigger_qty' => $trigger_qty,
                'limit'       => $limit,
                'reward_type' => $reward_type,
                'is_free'     => false,
            );

            if ( 'percent' === $reward_type ) {
                $percent = (float) $promo->discount_percent;
                if ( $percent <= 0 ) {
                    continue;
                }
                $entry['title'] = sprintf( __( '%s%% de réduction sur « %s » dès %d article(s)', 'flora-shop' ), rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' ), $trigger_name, $trigger_qty );
                $entry['value'] = $percent;
            } elseif ( 'amount' === $reward_type ) {
                $amount = (float) $promo->discount_amount;
                if ( $amount <= 0 ) {
                    continue;
                }
                $entry['title'] = sprintf( __( 'Réduction de %s sur « %s » dès %d article(s)', 'flora-shop' ), Flora_Helpers::format_price( $amount ), $trigger_name, $trigger_qty );
                $entry['value'] = $amount;
            } else {
                $free_type = isset( $promo->free_type ) && $promo->free_type ? $promo->free_type : 'product';
                $free_id   = absint( $promo->free_product_id );
                $free_qty  = absint( $promo->free_qty );
                if ( $free_id <= 0 || $free_qty <= 0 ) {
                    continue;
                }

                if ( 'pack' === $free_type ) {
                    $free_pack = $db->get_pack( $free_id );
                    $free_name = $free_pack ? $free_pack->name : __( 'Pack', 'flora-shop' );
                } else {
                    $free_prod = $db->get_product( $free_id );
                    $free_name = $free_prod ? $free_prod->name : __( 'Produit', 'flora-shop' );
                }

                $entry['title']   = sprintf( __( 'Achetez %d × %s et recevez %d × %s offert(s)', 'flora-shop' ), $trigger_qty, $trigger_name, $free_qty, $free_name );
                $entry['value']   = $free_qty;
                $entry['is_free'] = true;
            }

            $config[] = $entry;
        }

        return $config;
    }

    // shortcode_cart : affiche la page panier (contenu rendu dynamiquement par cart.js).
    public function shortcode_cart() {
        ob_start();
        include FLORA_SHOP_PATH . 'public/views/cart.php';
        return ob_get_clean();
    }

    // shortcode_checkout : récupère les wilayas puis affiche le formulaire de commande (view checkout).
    public function shortcode_checkout() {
        $db      = Flora_DB::get_instance();
        $wilayas = $db->get_wilayas();

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/checkout.php';
        return ob_get_clean();
    }

    // shortcode_order_confirm : charge la commande par son numéro (paramètre « order ») puis affiche la confirmation (view order-confirmation).
    public function shortcode_order_confirm() {
        $order_number = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $order = null;

        if ( $order_number ) {
            $db    = Flora_DB::get_instance();
            $order = $db->get_order_by_number( $order_number );
        }

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/order-confirmation.php';
        return ob_get_clean();
    }
}

new Flora_Public();
