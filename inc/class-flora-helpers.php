<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe utilitaire regroupant les fonctions d'aide du plugin Flora Shop.
 *
 * Formatage, sanitisation, numérotation des commandes, URLs de pages
 * et gestion des nonces.
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

    // Retourne l'URL permanente d'une page identifiée par son slug.
    public static function get_page_url( $slug ) {
        $page = get_page_by_path( $slug );
        if ( $page ) {
            return get_permalink( $page->ID );
        }
        return '';
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
