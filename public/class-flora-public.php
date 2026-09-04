<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Public {

    public function __construct() {
        add_shortcode( 'flora_products', array( $this, 'shortcode_products' ) );
        add_shortcode( 'flora_cart', array( $this, 'shortcode_cart' ) );
        add_shortcode( 'flora_checkout', array( $this, 'shortcode_checkout' ) );
        add_shortcode( 'flora_order_confirm', array( $this, 'shortcode_order_confirm' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        if ( ! is_page() && ! is_short_code() ) {
            global $post;
            if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'flora_products' ) && ! has_shortcode( $post->post_content, 'flora_cart' ) && ! has_shortcode( $post->post_content, 'flora_checkout' ) && ! has_shortcode( $post->post_content, 'flora_order_confirm' ) ) {
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
            ),
            'i18n'    => array(
                'added'      => __( 'Ajouté au panier !', 'flora-shop' ),
                'updated'    => __( 'Panier mis à jour.', 'flora-shop' ),
                'removed'    => __( 'Article retiré.', 'flora-shop' ),
                'error'      => __( 'Une erreur est survenue.', 'flora-shop' ),
                'confirm'    => __( 'Voulez-vous vraiment vider le panier ?', 'flora-shop' ),
                'processing' => __( 'Traitement en cours...', 'flora-shop' ),
            ),
        ) );
    }

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

    public function shortcode_cart() {
        ob_start();
        include FLORA_SHOP_PATH . 'public/views/cart.php';
        return ob_get_clean();
    }

    public function shortcode_checkout() {
        $db      = Flora_DB::get_instance();
        $wilayas = $db->get_wilayas();

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/checkout.php';
        return ob_get_clean();
    }

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
