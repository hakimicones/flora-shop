<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Moteur du panier basé sur un cookie.
 *
 * Gère le panier en cookie (ajout, suppression, quantités),
 * le moteur de promotions BXGY, le recalcul des totaux
 * et l'encodage JSON pour l'API REST.
 */
class Flora_Cart {

    private static $initialized = false;

    // Initialisation en mode singleton : protège contre les double appels.
    public static function init() {
        if ( self::$initialized ) {
            return;
        }
        self::$initialized = true;
    }

    // Retourne le nom du cookie utilisé pour le stockage du panier.
    private static function cart_cookie_name() {
        return 'flora_cart';
    }

    // Charge le panier depuis le cookie (ou la session legacy) et retourne le tableau du panier.
    private static function ensure_cart() {
        $cart = self::read_cookie_cart();

        if ( ! $cart ) {
            $cart = self::migrate_legacy_session();
        }

        if ( ! $cart ) {
            $cart = array(
                'items'           => array(),
                'wilaya_code'     => 0,
                'commune_id'      => 0,
                'shipping_method' => self::default_method(),
            );
        }

        // Panier d'ancienne génération sans méthode : on attribue la méthode active par défaut.
        if ( empty( $cart['shipping_method'] ) || ! Flora_Helpers::is_method_enabled( $cart['shipping_method'] ) ) {
            $cart['shipping_method'] = self::default_method();
            self::save_cart( $cart );
        }

        if ( isset( $cart['wilaya_id'] ) && ! isset( $cart['wilaya_code'] ) ) {
            $cart['wilaya_code'] = absint( $cart['wilaya_id'] );
            unset( $cart['wilaya_id'] );
            self::save_cart( $cart );
        }

        return $cart;
    }

    // Lit le cookie du panier, décode le base64 puis le JSON ; retourne le tableau du panier ou null.
    private static function read_cookie_cart() {
        if ( empty( $_COOKIE[ self::cart_cookie_name() ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.VIP.SuperGlobalInputUsage
            return null;
        }

        $raw = wp_unslash( $_COOKIE[ self::cart_cookie_name() ] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.VIP.SuperGlobalInputUsage
        $decoded = base64_decode( $raw, true );

        if ( false === $decoded ) {
            return null;
        }

        $cart = json_decode( $decoded, true );

        if ( ! is_array( $cart ) || ! isset( $cart['items'] ) || ! is_array( $cart['items'] ) ) {
            return null;
        }

        $cart['items'] = array_values( $cart['items'] );

        return $cart;
    }

    // Migre un éventuel panier depuis $_SESSION vers le cookie et retourne le tableau du panier ou null.
    private static function migrate_legacy_session() {
        if ( ! isset( $_SESSION['flora_cart'] ) || ! is_array( $_SESSION['flora_cart'] ) ) { // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage
            return null;
        }

        $cart = $_SESSION['flora_cart']; // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage

        if ( ! isset( $cart['items'] ) || ! is_array( $cart['items'] ) ) {
            return null;
        }

        if ( isset( $_SESSION['flora_cart']['wilaya_id'] ) && ! isset( $_SESSION['flora_cart']['wilaya_code'] ) ) { // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage
            $cart['wilaya_code'] = absint( $_SESSION['flora_cart']['wilaya_id'] ); // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage
            unset( $cart['wilaya_id'] );
        }

        unset( $_SESSION['flora_cart'] ); // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage

        self::save_cart( $cart );

        return $cart;
    }

    // Encode le panier en JSON/base64 et le persiste dans un cookie (30 jours).
    private static function save_cart( $cart ) {
        $json = wp_json_encode( $cart );

        if ( false === $json ) {
            return;
        }

        $value = base64_encode( $json );

        $_COOKIE[ self::cart_cookie_name() ] = $value; // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage

        $path      = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
        $secure    = is_ssl();
        $same_site = 'Lax';

        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie(
                self::cart_cookie_name(),
                $value,
                array(
                    'expires'  => time() + 30 * DAY_IN_SECONDS,
                    'path'     => $path,
                    'domain'   => '',
                    'secure'   => $secure,
                    'httponly' => false,
                    'samesite' => $same_site,
                )
            );
        } else {
            setcookie( self::cart_cookie_name(), $value, time() + 30 * DAY_IN_SECONDS, $path, '', $secure, false );
        }
    }

    // Retourne la liste brute des éléments du panier.
    public static function get_items() {
        $cart = self::ensure_cart();
        return $cart['items'];
    }

    // Retourne la quantité totale d'articles payants (exclut free_item et free_pack).
    public static function get_count() {
        $cart  = self::ensure_cart();
        $count = 0;
        foreach ( $cart['items'] as $item ) {
            if ( 'free_item' !== $item['type'] && 'free_pack' !== $item['type'] ) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }

    // Ajoute un article au panier (ou incrémente la quantité si déjà présent) et recalcule le total.
    public static function add_item( $type, $id, $quantity = 1 ) {
        $cart = self::ensure_cart();

        $existing_index = self::find_item_index( $type, $id );

        if ( $existing_index !== false ) {
            $cart['items'][ $existing_index ]['quantity'] += $quantity;
        } else {
            $cart['items'][] = array(
                'type'     => $type,
                'id'       => absint( $id ),
                'quantity' => absint( $quantity ),
            );
        }

        self::save_cart( $cart );
        self::remove_free_items();
        self::recalculate();
        return $cart;
    }

    // Met à jour la quantité d'un article par son index ; supprime si quantité <= 0, puis recalcule.
    public static function update_quantity( $index, $quantity ) {
        $cart = self::ensure_cart();

        if ( ! isset( $cart['items'][ $index ] ) ) {
            return false;
        }

        if ( absint( $quantity ) <= 0 ) {
            unset( $cart['items'][ $index ] );
            $cart['items'] = array_values( $cart['items'] );
        } else {
            $cart['items'][ $index ]['quantity'] = absint( $quantity );
        }

        self::save_cart( $cart );
        self::remove_free_items();
        self::recalculate();
        return $cart;
    }

    // Supprime un article du panier par son index et recalcule le total.
    public static function remove_item( $index ) {
        $cart = self::ensure_cart();

        if ( ! isset( $cart['items'][ $index ] ) ) {
            return false;
        }

        unset( $cart['items'][ $index ] );
        $cart['items'] = array_values( $cart['items'] );

        self::save_cart( $cart );
        self::remove_free_items();
        self::recalculate();
        return $cart;
    }

    // Définit la wilaya et la commune du panier, puis recalcule les totaux (frais de livraison).
    public static function set_location( $wilaya_code, $commune_id = 0 ) {
        $cart = self::ensure_cart();
        $cart['wilaya_code'] = absint( $wilaya_code );
        $cart['commune_id']  = absint( $commune_id );
        self::save_cart( $cart );
        self::recalculate();
        return $cart;
    }

    // Définit la méthode de livraison du panier (domicile ou bureau de liaison),
    // puis recalcule les totaux. Retourne le panier, ou false si la méthode est inconnue/désactivée.
    public static function set_shipping_method( $method ) {
        if ( ! Flora_Helpers::is_method_enabled( $method ) ) {
            return false;
        }

        $cart = self::ensure_cart();
        $cart['shipping_method'] = $method;
        self::save_cart( $cart );
        self::recalculate();
        return $cart;
    }

    // Retourne la méthode de livraison active du panier (ou celle par défaut).
    public static function get_shipping_method() {
        $cart = self::ensure_cart();
        return isset( $cart['shipping_method'] ) && Flora_Helpers::is_method_enabled( $cart['shipping_method'] ) ? $cart['shipping_method'] : self::default_method();
    }

    // Retourne la première méthode de livraison active ('home' en dernier recours).
    private static function default_method() {
        foreach ( Flora_Helpers::flora_shipping_methods() as $key => $cfg ) {
            if ( Flora_Helpers::is_method_enabled( $key ) ) {
                return $key;
            }
        }
        return 'home';
    }

    // Réinitialise le panier et supprime le cookie côté client.
    public static function clear() {
        $empty_cart = array(
            'items'           => array(),
            'wilaya_code'     => 0,
            'commune_id'      => 0,
            'shipping_method' => self::default_method(),
        );

        self::save_cart( $empty_cart );

        $path = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
        unset( $_COOKIE[ self::cart_cookie_name() ] ); // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage
        setcookie( self::cart_cookie_name(), '', time() - 3600, $path );
    }

    // Retourne les totaux du panier (sous-total, remises, livraison, total, poids) ou les recalcule si absents.
    public static function get_totals() {
        $cart = self::ensure_cart();
        if ( empty( $cart['totals'] ) ) {
            self::recalculate();
            $cart = self::ensure_cart();
        }
        return isset( $cart['totals'] ) ? $cart['totals'] : array(
            'subtotal'       => 0,
            'discount_total' => 0,
            'shipping_fee'   => 0,
            'total'          => 0,
            'total_weight'   => 0,
        );
    }

    // Recherche l'index d'un article payant dans le panier par type et id ; retourne l'index ou false.
    private static function find_item_index( $type, $id ) {
        $cart = self::ensure_cart();
        foreach ( $cart['items'] as $index => $item ) {
            if ( $item['type'] === $type && $item['id'] === absint( $id ) && 'free_item' !== $item['type'] && 'free_pack' !== $item['type'] ) {
                return $index;
            }
        }
        return false;
    }

    // Supprime tous les articles gratuits (free_item, free_pack) du panier avant recalcul.
    private static function remove_free_items() {
        $cart = self::ensure_cart();
        $new_items = array();
        foreach ( $cart['items'] as $item ) {
            if ( 'free_item' !== $item['type'] && 'free_pack' !== $item['type'] ) {
                $new_items[] = $item;
            }
        }
        $cart['items'] = $new_items;
        self::save_cart( $cart );
    }

    // Recalcule l'ensemble des totaux du panier : sous-total, poids, promotions, remises, livraison.
    private static function recalculate() {
        $db   = Flora_DB::get_instance();
        $cart = self::ensure_cart();

        $product_counts = array();
        foreach ( $cart['items'] as $item ) {
            if ( 'free_item' === $item['type'] || 'free_pack' === $item['type'] ) {
                continue;
            }
            $key = $item['type'] . ':' . $item['id'];
            if ( ! isset( $product_counts[ $key ] ) ) {
                $product_counts[ $key ] = 0;
            }
            $product_counts[ $key ] += $item['quantity'];
        }

        $promo_discounts = self::apply_promotions( $cart, $product_counts );
        $cart['promo_discounts'] = $promo_discounts;

        $subtotal       = 0;
        $total_weight   = 0;

        foreach ( $cart['items'] as $item ) {
            if ( 'free_item' === $item['type'] || 'free_pack' === $item['type'] ) {
                continue;
            }

            $price  = 0;
            $weight = 0;

            if ( 'product' === $item['type'] ) {
                $product = $db->get_product( $item['id'] );
                if ( $product ) {
                    $price  = (float) $product->price;
                    $weight = (float) $product->weight;
                }
            } elseif ( 'pack' === $item['type'] ) {
                $pack = $db->get_pack( $item['id'] );
                if ( $pack ) {
                    $price        = (float) $pack->pack_price;
                    $pack_products = $db->get_pack_products( $item['id'] );
                    foreach ( $pack_products as $pp ) {
                        $p = $db->get_product( $pp->product_id );
                        if ( $p ) {
                            $weight += (float) $p->weight * (float) $pp->quantity;
                        }
                    }
                }
            }

            $line_total    = $price * $item['quantity'];
            $subtotal     += $line_total;
            $total_weight += $weight * $item['quantity'];
        }

        $promo_discount_amt = 0;
        foreach ( $promo_discounts as $pd ) {
            $promo_discount_amt += (float) $pd['amount'];
        }

        $discount_total = self::calculate_discounts( $cart, $subtotal, $product_counts ) + $promo_discount_amt;
        $shipping_fee   = self::calculate_shipping( $cart, $total_weight );
        $total          = $subtotal - $discount_total + $shipping_fee;

        if ( $total < 0 ) {
            $total = 0;
        }

        $cart['totals'] = array(
            'subtotal'       => $subtotal,
            'discount_total' => $discount_total,
            'shipping_fee'   => $shipping_fee,
            'total'          => $total,
            'total_weight'   => $total_weight,
        );

        self::save_cart( $cart );
    }

    // Applique les promotions BXGY actives au panier et retourne le tableau des remises promotionnelles.
    private static function apply_promotions( &$cart, $product_counts ) {
        $db             = Flora_DB::get_instance();
        $promotions     = $db->get_promotions( true );
        $promo_discounts = array();

        if ( empty( $promotions ) ) {
            return $promo_discounts;
        }

        foreach ( $promotions as $promo ) {
            $trigger_type = $promo->trigger_type ? $promo->trigger_type : 'product';
            $trigger_key  = $trigger_type . ':' . absint( $promo->trigger_product_id );
            $trigger_qty  = absint( $promo->trigger_qty );
            $reward_type  = $promo->reward_type ? $promo->reward_type : 'free';
            $limit        = absint( $promo->limit_per_order );

            if ( ! isset( $product_counts[ $trigger_key ] ) || $product_counts[ $trigger_key ] < $trigger_qty ) {
                continue;
            }

            // Nombre de lots éligibles = quantité totale / quantité déclencheur (entier inférieur).
            $times = (int) floor( $product_counts[ $trigger_key ] / $trigger_qty );

            // Plafonnement : la promo ne peut pas s'appliquer plus de limit_per_order fois par commande.
            if ( $limit > 0 && $times > $limit ) {
                $times = $limit;
            }

            if ( $times <= 0 ) {
                continue;
            }

            $trigger_name = self::resolve_item_name( $trigger_type, $promo->trigger_product_id );

            if ( 'percent' === $reward_type ) {
                // Branche remise pourcentage : applique discount_percent sur le prix total des articles déclencheurs.
                $percent = (float) $promo->discount_percent;

                if ( $percent <= 0 ) {
                    continue;
                }

                $unit_price  = self::resolve_item_price( $trigger_type, $promo->trigger_product_id );
                $amount      = ( $times * $trigger_qty * $unit_price ) * ( $percent / 100 );

                if ( $amount <= 0 ) {
                    continue;
                }

                $promo_discounts[] = array(
                    'id'     => absint( $promo->id ),
                    'title'  => sprintf( __( '%s%% de réduction sur « %s »', 'flora-shop' ), rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' ), $trigger_name ),
                    'amount' => round( $amount, 2 ),
                );

                continue;
            }

            if ( 'amount' === $reward_type ) {
                // Branche remise montant fixe : applique discount_amount par lot, plafonné au prix réel des articles déclencheurs.
                $discount_per_set = (float) $promo->discount_amount;

                if ( $discount_per_set <= 0 ) {
                    continue;
                }

                $unit_price = self::resolve_item_price( $trigger_type, $promo->trigger_product_id );
                $max_discount = $times * $trigger_qty * $unit_price;
                $amount       = min( $times * $discount_per_set, $max_discount );

                if ( $amount <= 0 ) {
                    continue;
                }

                $promo_discounts[] = array(
                    'id'     => absint( $promo->id ),
                    'title'  => sprintf( __( 'Réduction de %s sur « %s »', 'flora-shop' ), Flora_Helpers::format_price( $discount_per_set ), $trigger_name ),
                    'amount' => round( $amount, 2 ),
                );

                continue;
            }

            $free_type = isset( $promo->free_type ) && $promo->free_type ? $promo->free_type : 'product';
            $free_id   = absint( $promo->free_product_id );
            $free_qty  = absint( $promo->free_qty );

            if ( $free_id <= 0 || $free_qty <= 0 ) {
                continue;
            }

            $free_item_type = 'pack' === $free_type ? 'free_pack' : 'free_item';
            // Quantité totale gratuite à offrir = nombre de lots * quantité gratuite par lot. Toujours >= 1 (times et free_qty sont validés plus haut).
            $total_free = $times * $free_qty;
            $free_promo = sprintf( __( 'Achetez %d × %s et recevez %d × %s offert(s)', 'flora-shop' ), $trigger_qty, $trigger_name, $total_free, self::resolve_item_name( $free_type, $free_id ) );

            $found = false;
            // Mise à jour de l'article gratuit existant dans le panier ou ajout si absent.
            for ( $i = 0; $i < count( $cart['items'] ); $i++ ) {
                if ( $cart['items'][ $i ]['type'] === $free_item_type && $cart['items'][ $i ]['id'] === $free_id ) {
                    $cart['items'][ $i ]['quantity']    = $total_free;
                    $cart['items'][ $i ]['promo_label'] = $free_promo;
                    $found                               = true;
                    break;
                }
            }

            if ( ! $found ) {
                $cart['items'][] = array(
                    'type'        => $free_item_type,
                    'id'          => $free_id,
                    'quantity'    => $total_free,
                    'name'        => self::resolve_item_name( $free_type, $free_id ),
                    'price'       => 0,
                    'promo_label' => $free_promo,
                );
            }
        }

        return $promo_discounts;
    }

    // Résolution du nom d'un article (produit ou pack) à partir de la base de données.
    private static function resolve_item_name( $type, $id ) {
        $db = Flora_DB::get_instance();

        if ( 'pack' === $type ) {
            $pack = $db->get_pack( $id );
            return $pack ? $pack->name : __( 'Pack', 'flora-shop' );
        }

        $product = $db->get_product( $id );
        return $product ? $product->name : __( 'Produit', 'flora-shop' );
    }

    // Résolution du prix unitaire d'un article (produit ou pack) ; retourne un float ou 0.
    private static function resolve_item_price( $type, $id ) {
        $db = Flora_DB::get_instance();

        if ( 'pack' === $type ) {
            $pack = $db->get_pack( $id );
            return $pack ? (float) $pack->pack_price : 0;
        }

        $product = $db->get_product( $id );
        return $product ? (float) $product->price : 0;
    }

    // Calcule les remises classiques (remises par produit et remises par montant du panier) et retourne le total.
    private static function calculate_discounts( $cart, $subtotal, $product_counts ) {
        $discount_total = 0;

        $product_discounts = get_option( 'flora_product_discounts', array() );
        if ( ! empty( $product_discounts ) && is_array( $product_discounts ) ) {
            foreach ( $cart['items'] as $item ) {
                if ( 'free_item' === $item['type'] || 'free_pack' === $item['type'] ) {
                    continue;
                }

                foreach ( $product_discounts as $rule ) {
                    if ( absint( $rule['product_id'] ) === $item['id'] && absint( $item['quantity'] ) >= absint( $rule['min_qty'] ) ) {
                        $db      = Flora_DB::get_instance();
                        $product = $db->get_product( $item['id'] );
                        if ( $product ) {
                            $line_price     = (float) $product->price * $item['quantity'];
                            $discount_total += $line_price * ( absint( $rule['percent'] ) / 100 );
                        }
                    }
                }
            }
        }

        $cart_discounts = get_option( 'flora_cart_discounts', array() );
        if ( ! empty( $cart_discounts ) && is_array( $cart_discounts ) ) {
            foreach ( $cart_discounts as $rule ) {
                if ( $subtotal >= (float) $rule['min_total'] ) {
                    $discount_total += $subtotal * ( absint( $rule['percent'] ) / 100 );
                }
            }
        }

        return $discount_total;
    }

    // Calcule les frais de livraison selon la méthode du panier (gratuit si seuil atteint).
    // Le seuil de livraison gratuite s'applique aux deux méthodes.
    private static function calculate_shipping( $cart, $total_weight ) {
        $free_threshold = (float) get_option( 'flora_free_shipping_threshold', 0 );

        $totals   = isset( $cart['totals'] ) ? $cart['totals'] : array();
        $subtotal = isset( $totals['subtotal'] ) ? $totals['subtotal'] : 0;

        if ( $free_threshold > 0 && $subtotal >= $free_threshold ) {
            return 0;
        }

        $method = isset( $cart['shipping_method'] ) ? $cart['shipping_method'] : self::default_method();

        return self::shipping_fee_for( $cart, $method, $total_weight );
    }

    // Calcule le tarif de transport d'une méthode précise sans appliquer le seuil gratuit.
    // « home » : base + per_kg (commune si définie, sinon wilaya) ;
    // « liaison » : bureau_fee + bureau_per_kg (ligne wilaya uniquement).
    private static function shipping_fee_for( $cart, $method, $total_weight ) {
        if ( empty( $cart['wilaya_code'] ) || $cart['wilaya_code'] <= 0 ) {
            return 0;
        }

        $db = Flora_DB::get_instance();

        if ( 'liaison' === $method ) {
            $rate = $db->get_liaison_rate( $cart['wilaya_code'] );
            if ( ! $rate ) {
                return 0;
            }
            return (float) $rate->bureau_fee + ( $total_weight * (float) $rate->bureau_per_kg_fee );
        }

        $rate = $db->get_shipping_rate( $cart['wilaya_code'], $cart['commune_id'] );
        if ( ! $rate ) {
            return 0;
        }

        return (float) $rate->base_fee + ( $total_weight * (float) $rate->per_kg_fee );
    }

    // Liste des méthodes de livraison actives avec leur libellé, description et frais
    // estimés pour la wilaya actuelle du panier (0 si livraison gratuite ou wilaya manquante).
    private static function shipping_options() {
        $cart           = self::ensure_cart();
        $totals         = self::get_totals();
        $weight         = isset( $totals['total_weight'] ) ? (float) $totals['total_weight'] : 0;
        $free_threshold = (float) get_option( 'flora_free_shipping_threshold', 0 );
        $free           = $free_threshold > 0 && $totals['subtotal'] >= $free_threshold;

        $options = array();
        foreach ( Flora_Helpers::flora_shipping_methods() as $key => $cfg ) {
            if ( ! Flora_Helpers::is_method_enabled( $key ) ) {
                continue;
            }

            $fee = $free ? 0 : self::shipping_fee_for( $cart, $key, $weight );

            $options[] = array(
                'method'      => $key,
                'label'       => $cfg['label'],
                'description' => isset( $cfg['description'] ) ? $cfg['description'] : '',
                'fee'         => round( $fee, 2 ),
            );
        }

        return $options;
    }

    // Traite la validation de commande : crée la commande en base, insère les lignes, décrémente le stock, vide le panier.
    public static function process_checkout( $billing_data ) {
        $db   = Flora_DB::get_instance();
        $cart = self::ensure_cart();

        if ( empty( $cart['items'] ) ) {
            return new WP_Error( 'empty_cart', __( 'Votre panier est vide.', 'flora-shop' ) );
        }

        $cart['wilaya_code'] = absint( $billing_data['wilaya_code'] );
        $cart['commune_id']  = absint( $billing_data['commune_id'] );
        self::save_cart( $cart );

        self::recalculate();
        $cart = self::ensure_cart();

        $totals        = $cart['totals'];
        $items_to_save = $cart['items'];

        $order_data = array(
            'order_number'    => Flora_Helpers::generate_order_number(),
            'customer_name'   => Flora_Helpers::sanitize_text( $billing_data['full_name'] ),
            'email'           => ! empty( $billing_data['email'] ) ? Flora_Helpers::sanitize_email( $billing_data['email'] ) : '',
            'phone'           => Flora_Helpers::sanitize_text( $billing_data['phone'] ),
            'address'         => ! empty( $billing_data['address'] ) ? Flora_Helpers::sanitize_text( $billing_data['address'] ) : '',
            'wilaya_code'     => $cart['wilaya_code'],
            'commune_id'      => $cart['commune_id'],
            'shipping_method' => isset( $cart['shipping_method'] ) ? $cart['shipping_method'] : 'home',
            'subtotal'        => $totals['subtotal'],
            'discount_total'  => $totals['discount_total'],
            'shipping_fee'    => $totals['shipping_fee'],
            'total'           => $totals['total'],
            'status'          => 'pending',
            'notes'           => isset( $billing_data['notes'] ) ? Flora_Helpers::sanitize_text( $billing_data['notes'] ) : '',
        );

        $order_id = $db->insert_order( $order_data );

        if ( ! $order_id ) {
            return new WP_Error( 'order_failed', __( 'Échec de la création de la commande.', 'flora-shop' ) );
        }

        foreach ( $items_to_save as $item ) {
            $item_name  = '';
            $unit_price = 0;
            $line_total = 0;
            $discount   = 0;

            if ( 'product' === $item['type'] ) {
                $product = $db->get_product( $item['id'] );
                if ( $product ) {
                    $item_name  = $product->name;
                    $unit_price = (float) $product->price;
                    $line_total = $unit_price * $item['quantity'];
                    $db->decrement_stock( $item['id'], $item['quantity'] );
                }
            } elseif ( 'pack' === $item['type'] ) {
                $pack = $db->get_pack( $item['id'] );
                if ( $pack ) {
                    $item_name  = $pack->name;
                    $unit_price = (float) $pack->pack_price;
                    $line_total = $unit_price * $item['quantity'];
                }
            } elseif ( 'free_item' === $item['type'] ) {
                $item_name  = isset( $item['name'] ) ? $item['name'] : __( 'Produit gratuit', 'flora-shop' );
                $unit_price = 0;
                $line_total = 0;
                $discount   = 0;
                $db->decrement_stock( $item['id'], $item['quantity'] );
            } elseif ( 'free_pack' === $item['type'] ) {
                $item_name  = isset( $item['name'] ) ? $item['name'] : __( 'Pack gratuit', 'flora-shop' );
                $unit_price = 0;
                $line_total = 0;
                $discount   = 0;
            }

            $db->insert_order_detail( array(
                'order_id'         => $order_id,
                'item_type'        => $item['type'],
                'item_id'          => $item['id'],
                'item_name'        => $item_name,
                'quantity'         => $item['quantity'],
                'unit_price'       => $unit_price,
                'discount_applied' => $discount,
            ) );
        }

        self::clear();

        return array(
            'order_id'     => $order_id,
            'order_number' => $order_data['order_number'],
            'total'        => $totals['total'],
        );
    }

    // Génère le tableau structuré du panier au format JSON pour l'API REST (articles, totaux, promotions).
    public static function get_cart_json() {
        $db     = Flora_DB::get_instance();
        $cart   = self::ensure_cart();
        $totals = self::get_totals();

        // Construction du tableau des articles au format attendu par l'API (index, type, id, quantité, prix, ligne totale, image).
        $items = array();
        foreach ( $cart['items'] as $index => $item ) {
            $data = array(
                'index'      => $index,
                'type'       => $item['type'],
                'id'         => $item['id'],
                'quantity'   => $item['quantity'],
                'name'       => '',
                'price'      => 0,
                'line_total' => 0,
            );

            if ( 'product' === $item['type'] ) {
                $product = $db->get_product( $item['id'] );
                if ( $product ) {
                    $data['name']       = $product->name;
                    $data['price']      = (float) $product->price;
                    $data['line_total'] = $data['price'] * $item['quantity'];
                    $data['image']      = $product->image_url;
                }
            } elseif ( 'pack' === $item['type'] ) {
                $pack = $db->get_pack( $item['id'] );
                if ( $pack ) {
                    $data['name']       = $pack->name;
                    $data['price']      = (float) $pack->pack_price;
                    $data['line_total'] = $data['price'] * $item['quantity'];
                    $data['image']      = $pack->image_url;
                }
            } elseif ( 'free_item' === $item['type'] || 'free_pack' === $item['type'] ) {
                $data['name']        = isset( $item['name'] ) ? $item['name'] : ( 'free_pack' === $item['type'] ? __( 'Pack gratuit', 'flora-shop' ) : __( 'Produit gratuit', 'flora-shop' ) );
                $data['price']       = 0;
                $data['line_total']  = 0;
                $data['is_free']     = true;
                $data['promo_label'] = isset( $item['promo_label'] ) ? $item['promo_label'] : '';
            }

            $items[] = $data;
        }

        // Construction du tableau des promotions : remises calculées + articles gratuits ajoutés par BXGY.
        $promotions = array();
        if ( ! empty( $cart['promo_discounts'] ) && is_array( $cart['promo_discounts'] ) ) {
            $promotions = $cart['promo_discounts'];
        }
        foreach ( $cart['items'] as $item ) {
            if ( 'free_item' === $item['type'] || 'free_pack' === $item['type'] ) {
                $promotions[] = array(
                    'title'  => isset( $item['promo_label'] ) && $item['promo_label'] ? $item['promo_label'] : $item['name'],
                    'amount' => 0,
                    'free'   => true,
                );
            }
        }

        return array(
            'items'            => $items,
            'item_count'       => self::get_count(),
            'totals'           => $totals,
            'promotions'       => $promotions,
            'wilaya_code'      => $cart['wilaya_code'],
            'commune_id'       => $cart['commune_id'],
            'shipping_method'  => isset( $cart['shipping_method'] ) ? $cart['shipping_method'] : self::default_method(),
            'shipping_options' => self::shipping_options(),
        );
    }
}
