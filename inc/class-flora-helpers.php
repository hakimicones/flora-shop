<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe utilitaire regroupant les fonctions d'aide du plugin Flora Shop.
 *
 * Formatage, sanitisation, numérotation des commandes, résolution des pages
 * de la boutique (IDs / URLs) et gestion des nonces.
 */
class Flora_Helpers {

    // Formate un montant avec la devise configurée (défaut : DZD).
    public static function format_price( $amount ) {
        $currency = get_option( 'flora_currency', 'DZD' );
        return number_format( (float) $amount, 2, ',', ' ' ) . ' ' . $currency;
    }

    // Sanitise une chaîne de caractères (texte brut).
    public static function sanitize_text( $input ) {
        return sanitize_text_field( wp_unslash( $input ) );
    }

    // Sanitise une adresse e-mail.
    public static function sanitize_email( $input ) {
        return sanitize_email( wp_unslash( $input ) );
    }

    // Sanitise un nombre entier (valeur absolue).
    public static function sanitize_number( $input ) {
        return absint( $input );
    }

    // Convertit une valeur en nombre à virgule flottante.
    public static function sanitize_float( $input ) {
        return (float) $input;
    }

    // Génère un numéro de commande unique au format FL-AAAAMMJJ-NNNN.
    public static function generate_order_number() {
        $prefix = 'FL';
        $date   = date( 'Ymd' );
        $last   = (int) get_option( 'flora_last_order_seq_' . $date, 0 );
        $seq    = $last + 1;
        update_option( 'flora_last_order_seq_' . $date, $seq );
        return sprintf( '%s-%s-%04d', $prefix, $date, $seq );
    }

    // Retourne la configuration des pages Flora : clé → slug, titre et shortcode à insérer.
    public static function flora_pages() {
        return array(
            'shop'          => array(
                'slug'      => 'flora-boutique',
                'title'     => __( 'Boutique', 'flora-shop' ),
                'shortcode' => '[flora_products]',
            ),
            'cart'          => array(
                'slug'      => 'flora-panier',
                'title'     => __( 'Panier', 'flora-shop' ),
                'shortcode' => '[flora_cart]',
            ),
            'checkout'      => array(
                'slug'      => 'flora-commande',
                'title'     => __( 'Commande', 'flora-shop' ),
                'shortcode' => '[flora_checkout]',
            ),
            'order_confirm' => array(
                'slug'      => 'flora-confirmation',
                'title'     => __( 'Confirmation de commande', 'flora-shop' ),
                'shortcode' => '[flora_order_confirm]',
            ),
            'product'       => array(
                'slug'      => 'flora-produit',
                'title'     => __( 'Produit', 'flora-shop' ),
                'shortcode' => '[flora_product]',
            ),
        );
    }

    // Résout l'ID d'une page Flora : option « flora_page_$key » (paramétrée dans
    // l'admin), sinon page publiée trouvée par son slug de référence.
    public static function get_page_id( $key ) {
        $option = absint( get_option( 'flora_page_' . $key, 0 ) );
        if ( $option && 'publish' === get_post_status( $option ) ) {
            return $option;
        }

        $pages = self::flora_pages();
        if ( ! isset( $pages[ $key ] ) ) {
            return 0;
        }

        $page = get_page_by_path( $pages[ $key ]['slug'] );
        if ( $page && 'publish' === $page->post_status ) {
            return (int) $page->ID;
        }

        return 0;
    }

    // Retourne l'URL permanente d'une page Flora ('' si aucune page n'est résolue).
    public static function get_page_url( $key ) {
        $id = self::get_page_id( $key );
        return $id ? get_permalink( $id ) : '';
    }

    // Récupère toutes les pages publiées, toutes langues confondues. Le critère
    // « lang » => 'all' contourne le filtre de langue Polylang actif en admin,
    // afin de toujours proposer toutes les pages dans les sélecteurs de configuration.
    public static function get_all_pages() {
        $query = new WP_Query( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'lang'           => 'all',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );

        return $query->posts;
    }

    // Retourne la configuration complète des deux méthodes de livraison
    // (« home » : domicile, « liaison » : bureau de liaison) à partir de l'option
    // « flora_shipping_methods », avec repli sur les valeurs par défaut.
    public static function flora_shipping_methods() {
        $defaults = array(
            'home'    => array(
                'enabled'     => 1,
                'label'       => __( 'Livraison à domicile', 'flora-shop' ),
                'description' => __( 'Livraison à l\'adresse indiquée (wilaya / commune).', 'flora-shop' ),
            ),
            'liaison' => array(
                'enabled'     => 1,
                'label'       => __( 'Livraison au bureau de liaison', 'flora-shop' ),
                'description' => __( 'Retrait de la commande au bureau de liaison de votre wilaya.', 'flora-shop' ),
            ),
        );

        $saved = get_option( 'flora_shipping_methods', array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        foreach ( $defaults as $key => $base ) {
            $saved[ $key ] = wp_parse_args( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ? $saved[ $key ] : array(), $base );
        }

        return $saved;
    }

    // Indique si une méthode de livraison est active. Garantit qu'au moins une
    // méthode est toujours disponible : si aucune n'est active, seule « home » l'est.
    public static function is_method_enabled( $method ) {
        $methods = self::flora_shipping_methods();
        $enabled = ! empty( $methods[ $method ]['enabled'] );

        if ( $enabled ) {
            return true;
        }

        $any_enabled = false;
        foreach ( $methods as $cfg ) {
            if ( ! empty( $cfg['enabled'] ) ) {
                $any_enabled = true;
                break;
            }
        }

        // Si aucune méthode active, on retombe toujours sur « home ».
        return ! $any_enabled && 'home' === $method;
    }

    // Retourne le libellé d'une méthode de livraison ('' si inconnue).
    public static function shipping_method_label( $method ) {
        $methods = self::flora_shipping_methods();
        return isset( $methods[ $method ]['label'] ) && $methods[ $method ]['label'] ? $methods[ $method ]['label'] : '';
    }

    // Génère le HTML d'une notice admin WordPress (succès, erreur, etc.).
    public static function get_admin_notice( $message, $type = 'success' ) {
        return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
    }

    // Insère un champ nonce hidden dans le formulaire courant.
    public static function wpnonce_field( $action, $name = '_flora_nonce' ) {
        return wp_nonce_field( $action, $name );
    }

    // Vérifie le nonce soumis ; termine avec wp_kill en cas d'échec.
    public static function verify_nonce( $action, $name = '_flora_nonce' ) {
        if ( ! isset( $_POST[ $name ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ), $action ) ) {
            wp_die( esc_html__( 'Erreur de sécurité. Veuillez réessayer.', 'flora-shop' ) );
        }
    }
}
