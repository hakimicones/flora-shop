<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_Cart {

    private static $initialized = false;

    public static function init() {
        if ( self::$initialized ) {
            return;
        }
        self::$initialized = true;
    }

    private static function cart_cookie_name() {
        return 'flora_cart';
    }

    private static function ensure_cart() {
        $cart = self::read_cookie_cart();

        if ( ! $cart ) {
            $cart = self::migrate_legacy_session();
        }

        if ( ! $cart ) {
            $cart = array(
                'items'       => array(),
                'wilaya_code' => 0,
                'commune_id'  => 0,
            );
        }

        if ( isset( $cart['wilaya_id'] ) && ! isset( $cart['wilaya_code'] ) ) {
            $cart['wilaya_code'] = absint( $cart['wilaya_id'] );
            unset( $cart['wilaya_id'] );
            self::save_cart( $cart );
        }

        return $cart;
    }

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

    public static function get_items() {
        $cart = self::ensure_cart();
        return $cart['items'];
    }

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

    public static function set_location( $wilaya_code, $commune_id = 0 ) {
        $cart = self::ensure_cart();
        $cart['wilaya_code'] = absint( $wilaya_code );
        $cart['commune_id']  = absint( $commune_id );
        self::save_cart( $cart );
        self::recalculate();
        return $cart;
    }

    public static function clear() {
        $empty_cart = array(
            'items'       => array(),
            'wilaya_code' => 0,
            'commune_id'  => 0,
        );

        self::save_cart( $empty_cart );

        $path = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
        unset( $_COOKIE[ self::cart_cookie_name() ] ); // phpcs:ignore WordPress.VIP.SuperGlobalInputUsage
        setcookie( self::cart_cookie_name(), '', time() - 3600, $path );
    }

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

    private static function find_item_index( $type, $id ) {
        $cart = self::ensure_cart();
        foreach ( $cart['items'] as $index => $item ) {
            if ( $item['type'] === $type && $item['id'] === absint( $id ) && 'free_item' !== $item['type'] && 'free_pack' !== $item['type'] ) {
                return $index;
            }
        }
        return false;
    }

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

            $times = (int) floor( $product_counts[ $trigger_key ] / $trigger_qty );

            if ( $limit > 0 && $times > $limit ) {
                $times = $limit;
            }

            if ( $times <= 0 ) {
                continue;
            }

            $trigger_name = self::resolve_item_name( $trigger_type, $promo->trigger_product_id );

            if ( 'percent' === $reward_type ) {
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

            $free_type = isset( $promo->free_type ) && $promo->free_type ? $promo->free_type : 'product';
            $free_id   = absint( $promo->free_product_id );
            $free_qty  = absint( $promo->free_qty );

            if ( $free_id <= 0 || $free_qty <= 0 ) {
                continue;
            }

            $free_item_type = 'pack' === $free_type ? 'free_pack' : 'free_item';
            $total_free     = $times * $free_qty;
            $already_free   = 0;
            $free_promo     = sprintf( __( 'Achetez %d × %s et recevez %d × %s offert(s)', 'flora-shop' ), $trigger_qty, $trigger_name, $total_free, self::resolve_item_name( $free_type, $free_id ) );

            foreach ( $cart['items'] as $item ) {
                if ( $item['type'] === $free_item_type && $item['id'] === $free_id ) {
                    $already_free = $item['quantity'];
                }
            }

            $found = false;
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

            if ( $total_free <= 0 ) {
                foreach ( $cart['items'] as $i => $item ) {
                    if ( $item['type'] === $free_item_type && $item['id'] === $free_id ) {
                        unset( $cart['items'][ $i ] );
                        $cart['items'] = array_values( $cart['items'] );
                        break;
                    }
                }
            }
        }

        return $promo_discounts;
    }

    private static function resolve_item_name( $type, $id ) {
        $db = Flora_DB::get_instance();

        if ( 'pack' === $type ) {
            $pack = $db->get_pack( $id );
            return $pack ? $pack->name : __( 'Pack', 'flora-shop' );
        }

        $product = $db->get_product( $id );
        return $product ? $product->name : __( 'Produit', 'flora-shop' );
    }

    private static function resolve_item_price( $type, $id ) {
        $db = Flora_DB::get_instance();

        if ( 'pack' === $type ) {
            $pack = $db->get_pack( $id );
            return $pack ? (float) $pack->pack_price : 0;
        }

        $product = $db->get_product( $id );
        return $product ? (float) $product->price : 0;
    }

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

    private static function calculate_shipping( $cart, $total_weight ) {
        $free_threshold = (float) get_option( 'flora_free_shipping_threshold', 0 );

        $totals  = isset( $cart['totals'] ) ? $cart['totals'] : array();
        $subtotal = isset( $totals['subtotal'] ) ? $totals['subtotal'] : 0;

        if ( $free_threshold > 0 && $subtotal >= $free_threshold ) {
            return 0;
        }

        if ( empty( $cart['wilaya_code'] ) || $cart['wilaya_code'] <= 0 ) {
            return 0;
        }

        $db   = Flora_DB::get_instance();
        $rate = $db->get_shipping_rate( $cart['wilaya_code'], $cart['commune_id'] );

        if ( ! $rate ) {
            return 0;
        }

        $fee = (float) $rate->base_fee + ( $total_weight * (float) $rate->per_kg_fee );
        return $fee;
    }

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
            'order_number'   => Flora_Helpers::generate_order_number(),
            'customer_name'  => Flora_Helpers::sanitize_text( $billing_data['first_name'] . ' ' . $billing_data['last_name'] ),
            'email'          => Flora_Helpers::sanitize_email( $billing_data['email'] ),
            'phone'          => Flora_Helpers::sanitize_text( $billing_data['phone'] ),
            'address'        => Flora_Helpers::sanitize_text( $billing_data['address'] ),
            'wilaya_code'    => $cart['wilaya_code'],
            'commune_id'     => $cart['commune_id'],
            'subtotal'       => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'shipping_fee'   => $totals['shipping_fee'],
            'total'          => $totals['total'],
            'status'         => 'pending',
            'notes'          => isset( $billing_data['notes'] ) ? Flora_Helpers::sanitize_text( $billing_data['notes'] ) : '',
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

    public static function get_cart_json() {
        $db     = Flora_DB::get_instance();
        $cart   = self::ensure_cart();
        $totals = self::get_totals();

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
            'items'       => $items,
            'item_count'  => self::get_count(),
            'totals'      => $totals,
            'promotions'  => $promotions,
            'wilaya_code' => $cart['wilaya_code'],
            'commune_id'  => $cart['commune_id'],
        );
    }
}
