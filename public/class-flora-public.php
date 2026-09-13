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
        add_action( 'template_redirect', array( $this, 'no_cache_confirm' ) );
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

        // CSS personnalisé administré : injecté en ligne après les styles du plugin.
        $custom_css = get_option( 'flora_custom_css', '' );
        if ( $custom_css ) {
            wp_add_inline_style( 'flora-public', $custom_css );
        }

        wp_localize_script( 'flora-public', 'floraShop', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'flora-shop/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'currency'    => Flora_Helpers::currency(),
            'currencyRtl' => 'ar' === Flora_Helpers::get_active_lang() ? 1 : 0,
            'lang'        => Flora_Helpers::get_active_lang(),
            'pageUrls' => array(
                'checkout'      => Flora_Helpers::get_page_url( 'checkout' ),
                'orderConfirm'  => Flora_Helpers::get_page_url( 'order_confirm' ),
                'cart'          => Flora_Helpers::get_page_url( 'cart' ),
                'shop'          => Flora_Helpers::get_page_url( 'shop' ),
                'product'       => Flora_Helpers::get_page_url( 'product' ),
            ),
            'i18n'    => array(
                'added'         => __( 'Ajouté au panier !', 'flora-shop' ),
                'updated'       => __( 'Panier mis à jour.', 'flora-shop' ),
                'removed'       => __( 'Article retiré.', 'flora-shop' ),
                'error'         => __( 'Une erreur est survenue.', 'flora-shop' ),
                'confirm'       => __( 'Voulez-vous vraiment vider le panier ?', 'flora-shop' ),
                'processing'    => __( 'Traitement en cours...', 'flora-shop' ),
                'added_free'    => __( 'Article offert ajouté', 'flora-shop' ),
                'empty'         => __( 'Votre panier est vide.', 'flora-shop' ),
                'cart_title'    => __( 'Mon panier', 'flora-shop' ),
                'close'         => __( 'Fermer', 'flora-shop' ),
                'place_order'   => Flora_Helpers::ui( __( 'Passer la commande', 'flora-shop' ), 'إتمام الطلب' ),
                'view_cart'     => Flora_Helpers::ui( __( 'Voir le panier complet', 'flora-shop' ), 'عرض السلة الكاملة' ),
                'view_products' => Flora_Helpers::ui( __( 'Voir les produits', 'flora-shop' ), 'عرض المنتجات' ),
                'clear'         => Flora_Helpers::ui( __( 'Vider le panier', 'flora-shop' ), 'إفراغ السلة' ),
                'total'         => Flora_Helpers::ui( __( 'Total', 'flora-shop' ), 'المجموع' ),
                'subtotal'      => Flora_Helpers::ui( __( 'Sous-total', 'flora-shop' ), 'المجموع الفرعي' ),
                'discounts'     => Flora_Helpers::ui( __( 'Remises', 'flora-shop' ), 'التخفيضات' ),
                'shipping'      => Flora_Helpers::ui( __( 'Transport', 'flora-shop' ), 'التوصيل' ),
                'free'          => Flora_Helpers::ui( __( 'Gratuit', 'flora-shop' ), 'مجاني' ),
                'empty_items'   => Flora_Helpers::ui( __( 'Aucun article dans le panier.', 'flora-shop' ), 'لا توجد منتجات في السلة.' ),
                'remove'        => Flora_Helpers::ui( __( 'Supprimer', 'flora-shop' ), 'حذف' ),
            ),
        ) );
    }

    // shortcode_products : charge les produits et packs (filtrés par type, catégorie et étiquettes)
    // puis affiche la grille de la boutique (view product-listing).
    public function shortcode_products( $atts ) {
        $atts = shortcode_atts( array(
            'limit'    => 12,
            'type'     => 'both',
            'category' => '',
            'tag'      => '',
        ), $atts );

        // Whitelist du type : 'products' (produits seuls), 'packs' (packs seuls) ou 'both' (les deux, défaut).
        $type     = in_array( $atts['type'], array( 'products', 'packs', 'both' ), true ) ? $atts['type'] : 'both';
        $category = $atts['category'];
        $tag      = $atts['tag'];

        $db       = Flora_DB::get_instance();
        $lang     = Flora_Helpers::get_active_lang();
        $products = array();
        $packs    = array();

        // Catégorie et étiquette servent de filtres pour les deux types (logique ET entre elles).

        if ( 'packs' !== $type ) {
            $products = $db->get_products( array(
                'limit'    => absint( $atts['limit'] ),
                'category' => $category,
                'tag'      => $tag,
            ) );
        }

        if ( 'products' !== $type ) {
            $packs = $db->get_packs( array(
                'category' => $category,
                'tag'      => $tag,
            ) );
        }

        // Localisation des noms et descriptions selon la langue active (hydratation en masse).
        $products = $db->hydrate_languages( 'product', $products, $lang );
        $packs    = $db->hydrate_languages( 'pack', $packs, $lang );

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

        // Branche « pack » : charge le pack (localisé), ses produits et ses promotions.
        if ( $pack_slug ) {
            $pack            = $db->localize_item( $db->get_pack_by_slug( $pack_slug ), 'pack' );
            $pack_products   = array();
            $pack_promotions = array();
            $promo_config    = array();

            if ( $pack ) {
                $pack_products = $db->get_pack_products( $pack->id );
                $promo_config  = self::build_promo_config( $db->get_promotions( true ), 'pack', $pack->id, $pack->name );
                // Ajout des remises catalogue (catégorie / type / tag) applicables à ce pack.
                $promo_config  = array_merge( $promo_config, self::build_catalog_promo_config( $db->get_catalog_discounts( true ), 'pack', $pack ) );
                foreach ( $promo_config as $entry ) {
                    $pack_promotions[] = (object) $entry;
                }
            }

            ob_start();
            include FLORA_SHOP_PATH . 'public/views/pack-detail.php';
            return ob_get_clean();
        }

        // Branche « produit » : charge le produit (localisé) et ses promotions.
        $product = null;
        if ( $product_slug ) {
            $product = $db->localize_item( $db->get_product_by_slug( $product_slug ), 'product' );
        }

        $product_promotions = array();
        $promo_config       = array();
        if ( $product ) {
            $promo_config = self::build_promo_config( $db->get_promotions( true ), 'product', $product->id, $product->name );
            // Ajout des remises catalogue (catégorie / type / tag) applicables à ce produit.
            $promo_config = array_merge( $promo_config, self::build_catalog_promo_config( $db->get_catalog_discounts( true ), 'product', $product ) );
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
                    $free_name = $free_pack ? $db->localize_item( $free_pack, 'pack' )->name : __( 'Pack', 'flora-shop' );
                } else {
                    $free_prod = $db->get_product( $free_id );
                    $free_name = $free_prod ? $db->localize_item( $free_prod, 'product' )->name : __( 'Produit', 'flora-shop' );
                }

                $entry['title']   = sprintf( __( 'Achetez %d × %s et recevez %d × %s offert(s)', 'flora-shop' ), $trigger_qty, $trigger_name, $free_qty, $free_name );
                $entry['value']   = $free_qty;
                $entry['is_free'] = true;
            }

            $config[] = $entry;
        }

        return $config;
    }

    // build_catalog_promo_config : transforme les remises catalogue (catégorie / type / tag)
    // applicables au produit ou pack affiché en configuration lisible par le récapitulatif de prix.
    // Le champ « scope » enrichit chaque entrée pour que le récapitulatif groupe les paliers par cible.
    private static function build_catalog_promo_config( $discounts, $item_type, $item ) {
        $db      = Flora_DB::get_instance();
        $config  = array();
        $item_id = absint( $item->id );

        foreach ( $discounts as $promo ) {
            $scope       = $promo->scope ? $promo->scope : 'category';
            $target_id   = absint( $promo->target_id );
            $promo_item  = $promo->item_type ? $promo->item_type : 'both';
            $trigger_qty = absint( $promo->trigger_qty );
            $reward_type = $promo->reward_type ? $promo->reward_type : 'percent';
            $limit       = absint( $promo->limit_per_order );

            if ( $trigger_qty <= 0 || ! in_array( $scope, array( 'category', 'type', 'tag' ), true ) ) {
                continue;
            }

            if ( 'type' === $scope ) {
                $matches = ( $promo_item === $item_type );
            } elseif ( 'category' === $scope ) {
                $matches = ( ( 'both' === $promo_item || $promo_item === $item_type )
                    && absint( $item->category_id ) === $target_id );
            } else {
                $item_tags = array_map( 'absint', $db->get_item_tags( $item_type, $item_id ) );
                $matches   = ( ( 'both' === $promo_item || $promo_item === $item_type )
                    && in_array( $target_id, $item_tags, true ) );
            }

            if ( ! $matches ) {
                continue;
            }

            $target_label = Flora_Cart::catalog_target_label( $scope, $target_id, $promo_item );

            $entry = array(
                'id'          => absint( $promo->id ),
                'trigger_qty' => $trigger_qty,
                'limit'       => $limit,
                'reward_type' => $reward_type,
                'is_free'     => false,
                'scope'       => $scope,
            );

            if ( 'percent' === $reward_type ) {
                $percent = (float) $promo->discount_percent;
                if ( $percent <= 0 ) {
                    continue;
                }
                $entry['title'] = sprintf( __( '%s%% de réduction sur %s', 'flora-shop' ), rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' ), $target_label );
                $entry['value'] = $percent;
            } elseif ( 'amount' === $reward_type ) {
                $amount = (float) $promo->discount_amount;
                if ( $amount <= 0 ) {
                    continue;
                }
                $entry['title'] = sprintf( __( 'Réduction de %s sur %s', 'flora-shop' ), Flora_Helpers::format_price( $amount ), $target_label );
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
                    $free_name = $free_pack ? $db->localize_item( $free_pack, 'pack' )->name : __( 'Pack', 'flora-shop' );
                } else {
                    $free_prod = $db->get_product( $free_id );
                    $free_name = $free_prod ? $db->localize_item( $free_prod, 'product' )->name : __( 'Produit', 'flora-shop' );
                }

                $entry['title']   = sprintf( __( 'Achetez %d articles de %s et recevez %d × %s offert(s)', 'flora-shop' ), $trigger_qty, $target_label, $free_qty, $free_name );
                $entry['value']   = $free_qty;
                $entry['is_free'] = true;
            }

            $config[] = $entry;
        }

        return $config;
    }

    public function shortcode_cart() {
        ob_start();
        include FLORA_SHOP_PATH . 'public/views/cart.php';
        return ob_get_clean();
    }

    // shortcode_checkout : récupère les wilayas et les méthodes de livraison, puis affiche le formulaire de commande (view checkout).
    public function shortcode_checkout() {
        $db              = Flora_DB::get_instance();
        $wilayas         = $db->get_wilayas();
        $shipping_methods = Flora_Helpers::flora_shipping_methods();

        ob_start();
        include FLORA_SHOP_PATH . 'public/views/checkout.php';
        return ob_get_clean();
    }

    // Entête anti-cache sur la page de confirmation : contient des données personnelles
    // (email, adresse) dont l'affichage est conditionnel selon la configuration admin.
    // Envoi via template_redirect (avant toute sortie HTML) pour être effectif.
    public function no_cache_confirm() {
        $confirm_id = Flora_Helpers::get_page_id( 'order_confirm' );
        if ( $confirm_id && is_page( $confirm_id ) ) {
            nocache_headers();
        }
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
