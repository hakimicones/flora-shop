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

    // Retourne le libellé de la devise selon la langue active (défaut : DZD).
    // La langue arabe peut définir son propre libellé (ex. « دج ») via l'option flora_currency_ar.
    public static function currency() {
        if ( 'ar' === self::get_active_lang() ) {
            $currency_ar = get_option( 'flora_currency_ar', '' );
            if ( '' !== (string) $currency_ar ) {
                return $currency_ar;
            }
        }
        return get_option( 'flora_currency', 'DZD' );
    }

    // Formate un montant avec le libellé de devise de la langue active.
    public static function format_price( $amount ) {
        $currency = self::currency();
        // Espace insécable comme séparateur de milliers afin d'éviter le retour à la ligne ;
        // les isolates bidi (LTR) empêchent le réordonnancement du prix dans les contextes RTL (arabe).
        $number = number_format( (float) $amount, 2, ',', "\u{a0}" );

        // En arabe, le symbole de la devise est placé à gauche du montant : l'isolate RTL
        // fait remonter le montant à droite et le symbole à gauche (ordre logique montant + symbole).
        if ( 'ar' === self::get_active_lang() ) {
            return "\u{2067}" . $number . ' ' . $currency . "\u{2069}";
        }

        return "\u{2066}" . $number . ' ' . $currency . "\u{2069}";
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
    // Si Polylang est actif, résout la traduction de la page dans la langue active :
    // la navigation (boutique, panier, fiche, checkout...) suit ainsi la langue courante.
    public static function get_page_url( $key ) {
        $id = self::get_page_id( $key );
        if ( ! $id ) {
            return '';
        }

        if ( self::is_polylang_active() && function_exists( 'pll_get_post' ) ) {
            $translated = pll_get_post( $id, self::get_active_lang() );
            if ( ! $translated || 'publish' !== get_post_status( $translated ) ) {
                $translated = pll_get_post( $id, self::default_language() );
            }
            if ( $translated && 'publish' === get_post_status( $translated ) ) {
                $id = (int) $translated;
            }
        }

        return get_permalink( $id );
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
                'enabled'        => 1,
                'label'          => __( 'Livraison à domicile', 'flora-shop' ),
                'label_ar'       => 'التوصيل إلى المنزل',
                'description'    => __( 'Livraison à l\'adresse indiquée (wilaya / commune).', 'flora-shop' ),
                'description_ar' => 'التوصيل إلى العنوان المحدد (الولاية / البلدية).',
            ),
            'liaison' => array(
                'enabled'        => 1,
                'label'          => __( 'Livraison au bureau de liaison', 'flora-shop' ),
                'label_ar'       => 'التوصيل إلى مكتب الربط',
                'description'    => __( 'Retrait de la commande au bureau de liaison de votre wilaya.', 'flora-shop' ),
                'description_ar' => 'استلام الطلب من مكتب الربط الخاص بولايتك.',
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

    // Retourne la traduction d'une chaîne d'interface selon la langue active.
    // Le plugin ne charge pas de fichiers .mo : la version arabe est gérée par
    // cet helper (FR → $ar) en fonction de Flora_Helpers::get_active_lang().
    public static function ui( $fr, $ar ) {
        return 'ar' === self::get_active_lang() ? $ar : $fr;
    }

    // Retourne le libellé d'une méthode de livraison dans la langue active.
    public static function shipping_method_label( $method ) {
        $methods = self::flora_shipping_methods();
        if ( ! isset( $methods[ $method ] ) ) {
            return '';
        }
        $cfg = $methods[ $method ];
        if ( 'ar' === self::get_active_lang() && ! empty( $cfg['label_ar'] ) ) {
            return $cfg['label_ar'];
        }
        return isset( $cfg['label'] ) && $cfg['label'] ? $cfg['label'] : '';
    }

    // Retourne la description d'une méthode de livraison dans la langue active.
    public static function shipping_method_description( $method ) {
        $methods = self::flora_shipping_methods();
        if ( ! isset( $methods[ $method ] ) ) {
            return '';
        }
        $cfg = $methods[ $method ];
        if ( 'ar' === self::get_active_lang() && ! empty( $cfg['description_ar'] ) ) {
            return $cfg['description_ar'];
        }
        return isset( $cfg['description'] ) ? $cfg['description'] : '';
    }

    // Retourne le libellé du bouton/lien panier selon la langue active.
    // Valeurs administrées (options flora_cart_button_fr / _ar) ; en arabe,
    // si le libellé personnalisé est vide, on retombe sur le titre de page arabe.
    public static function cart_button_label() {
        $lang = self::get_active_lang();

        if ( 'ar' === $lang ) {
            $label = get_option( 'flora_cart_button_ar', '' );
            if ( '' !== trim( (string) $label ) ) {
                return $label;
            }
            return self::flora_page_title( 'cart', 'ar' );
        }

        $label = get_option( 'flora_cart_button_fr', '' );
        return '' !== trim( (string) $label ) ? $label : __( 'Mon panier', 'flora-shop' );
    }

    // Retourne le libellé du bouton « Ajouter au panier » selon la langue active.
    // Valeurs administrées (options flora_add_to_cart_fr / _ar).
    public static function add_to_cart_label() {
        $lang = self::get_active_lang();

        $default_fr = __( 'Ajouter au panier', 'flora-shop' );
        $default_ar = 'أضف إلى السلة';

        if ( 'ar' === $lang ) {
            $label = get_option( 'flora_add_to_cart_ar', '' );
            return '' !== trim( (string) $label ) ? $label : $default_ar;
        }

        $label = get_option( 'flora_add_to_cart_fr', '' );
        return '' !== trim( (string) $label ) ? $label : $default_fr;
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

    // Liste des langues par défaut de la boutique (français + arabe), utilisée au premier install.
    public static function default_languages() {
        return array(
            array( 'code' => 'fr', 'label' => 'Français', 'enabled' => 1 ),
            array( 'code' => 'ar', 'label' => 'العربية', 'enabled' => 1 ),
        );
    }

    // Retourne les langues actives du shop sous forme de tableau [code => libellé].
    // Garantit toujours au moins une langue active (repli : français).
    public static function flora_languages() {
        $languages = get_option( 'flora_languages' );
        if ( ! is_array( $languages ) || empty( $languages ) ) {
            $languages = self::default_languages();
        }

        $active = array();
        foreach ( $languages as $lang ) {
            if ( empty( $lang['code'] ) || empty( $lang['label'] ) ) {
                continue;
            }
            // Une langue « désactivée » n'apparaît pas (case à cocher absente du POST).
            if ( isset( $lang['enabled'] ) && empty( $lang['enabled'] ) ) {
                continue;
            }
            $active[ sanitize_title( $lang['code'] ) ] = $lang['label'];
        }

        if ( empty( $active ) ) {
            $active['fr'] = 'Français';
        }

        return $active;
    }

    // Retourne le code de la langue par défaut (langue des données en base) ; toujours dans les langues actives.
    public static function default_language() {
        $languages = array_keys( self::flora_languages() );
        $default   = sanitize_title( (string) get_option( 'flora_default_language', 'fr' ) );

        return in_array( $default, $languages, true ) ? $default : $languages[0];
    }

    // Retourne les langues secondaires (actives, hors langue par défaut) : destinations des traductions produits/packs.
    public static function secondary_languages() {
        $all     = self::flora_languages();
        $default = self::default_language();
        unset( $all[ $default ] );
        return $all;
    }

    // Indique si Polylang est présent. Le plugin reste fonctionnel sans Polylang :
    // les appels ci-dessous sont toujours conditionnés par la présence de ses fonctions.
    public static function is_polylang_active() {
        return function_exists( 'pll_current_language' ) || function_exists( 'pll_get_post_language' );
    }

    // Retourne la langue active du front. Priorité : langue Polylang courante (si active),
    // puis paramètre « ?lang=xx » (repli plugin-indépendant), puis langue par défaut.
    // Ne plante jamais en l'absence de Polylang.
    public static function get_active_lang() {
        $languages = array_keys( self::flora_languages() );

        // Paramètre explicite « ?lang=xx » : prioritaire quand il est présent, uniquement si la langue est active.
        // (Cette priorité permet aussi d'imposer une langue côté appels REST/AJAX.)
        if ( isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $lang = sanitize_title( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            if ( in_array( $lang, $languages, true ) ) {
                return $lang;
            }
        }

        // Langue Polylang courante (bascules du sélecteur du site), si elle correspond à une langue du shop.
        if ( self::is_polylang_active() && function_exists( 'pll_current_language' ) ) {
            $pll = pll_current_language( 'slug' );
            if ( $pll && in_array( (string) $pll, $languages, true ) ) {
                return (string) $pll;
            }
        }

        return self::default_language();
    }

    // Retourne le titre d'une page Flora dans une langue donnée (repli : libellé de la langue par défaut).
    public static function flora_page_title( $key, $lang = '' ) {
        $titles = array(
            'ar' => array(
                'shop'          => 'بوتيك',
                'cart'          => 'سلة التسوق',
                'checkout'      => 'إتمام الطلب',
                'order_confirm' => 'تأكيد الطلب',
                'product'       => 'المنتج',
            ),
        );

        $pages = self::flora_pages();

        if ( isset( $titles[ $lang ][ $key ] ) ) {
            return $titles[ $lang ][ $key ];
        }

        return isset( $pages[ $key ]['title'] ) ? $pages[ $key ]['title'] : $key;
    }

    // Extrait et assainit les traductions soumises via POST (nom / slug / description par langue).
    // Le slug est déduit du nom traduit. Retourne un tableau [lang => ['name', 'slug', 'description']],
    // uniquement pour les langues pourvues d'un nom non vide.
    public static function extract_translations( $post ) {
        $translations = array();

        if ( empty( $post['translations'] ) || ! is_array( $post['translations'] ) ) {
            return $translations;
        }

        foreach ( $post['translations'] as $lang => $entry ) {
            if ( ! is_array( $entry ) || empty( $entry['name'] ) ) {
                continue;
            }

            $translations[ $lang ] = array(
                'name'        => self::sanitize_text( $entry['name'] ),
                'slug'        => sanitize_title( $entry['name'] ),
                'description' => isset( $entry['description'] ) ? wp_kses_post( $entry['description'] ) : '',
            );
        }

        return $translations;
    }
}
