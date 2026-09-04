<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flora_REST_Controller {

    private $namespace = 'flora-shop/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/cart', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_cart' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/cart/add', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'add_to_cart' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'type'     => array( 'required' => true, 'type' => 'string', 'enum' => array( 'product', 'pack' ) ),
                'id'       => array( 'required' => true, 'type' => 'integer' ),
                'quantity' => array( 'required' => false, 'type' => 'integer', 'default' => 1 ),
            ),
        ) );

        register_rest_route( $this->namespace, '/cart/update', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_cart' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'index'    => array( 'required' => true, 'type' => 'integer' ),
                'quantity' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        register_rest_route( $this->namespace, '/cart/remove', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'remove_from_cart' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'index' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        register_rest_route( $this->namespace, '/cart/clear', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'clear_cart' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/cart/location', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'set_location' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'wilaya_code' => array( 'required' => true, 'type' => 'integer' ),
                'commune_id'  => array( 'required' => false, 'type' => 'integer', 'default' => 0 ),
            ),
        ) );

        register_rest_route( $this->namespace, '/checkout', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'checkout' ),
            'permission_callback' => array( $this, 'check_nonce' ),
        ) );

        register_rest_route( $this->namespace, '/orders/(?P<number>[A-Za-z0-9-]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_order' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/wilayas', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_wilayas' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/communes/(?P<wilaya_code>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_communes' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/products', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_products' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/packs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_packs' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/admin/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'admin_stats' ),
            'permission_callback' => array( $this, 'admin_permission' ),
        ) );
    }

    public function check_nonce( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        return $nonce && wp_verify_nonce( $nonce, 'wp_rest' );
    }

    public function admin_permission() {
        return current_user_can( 'manage_options' );
    }

    public function get_cart( $request ) {
        return rest_ensure_response( Flora_Cart::get_cart_json() );
    }

    public function add_to_cart( $request ) {
        $type     = $request->get_param( 'type' );
        $id       = $request->get_param( 'id' );
        $quantity = $request->get_param( 'quantity' );

        Flora_Cart::add_item( $type, $id, $quantity );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    public function update_cart( $request ) {
        $index    = $request->get_param( 'index' );
        $quantity = $request->get_param( 'quantity' );

        Flora_Cart::update_quantity( $index, $quantity );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    public function remove_from_cart( $request ) {
        $index = $request->get_param( 'index' );
        Flora_Cart::remove_item( $index );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    public function clear_cart( $request ) {
        Flora_Cart::clear();

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    public function set_location( $request ) {
        $wilaya_code = $request->get_param( 'wilaya_code' );
        $commune_id  = $request->get_param( 'commune_id' );

        Flora_Cart::set_location( $wilaya_code, $commune_id );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    public function checkout( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['billing'] ) ) {
            return new WP_Error( 'missing_billing', __( 'Informations de facturation manquantes.', 'flora-shop' ), array( 'status' => 400 ) );
        }

        $billing = $params['billing'];
        $required_fields = array( 'first_name', 'last_name', 'email', 'phone', 'address', 'wilaya_code' );

        foreach ( $required_fields as $field ) {
            if ( empty( $billing[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Le champ %s est requis.', 'flora-shop' ), $field ), array( 'status' => 400 ) );
            }
        }

        if ( ! is_email( $billing['email'] ) ) {
            return new WP_Error( 'invalid_email', __( 'Adresse email invalide.', 'flora-shop' ), array( 'status' => 400 ) );
        }

        $result = Flora_Cart::process_checkout( $billing );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'order'   => $result,
        ), 201 );
    }

    public function get_order( $request ) {
        $number = $request->get_param( 'number' );
        $db     = Flora_DB::get_instance();
        $order  = $db->get_order_by_number( $number );

        if ( ! $order ) {
            return new WP_Error( 'not_found', __( 'Commande introuvable.', 'flora-shop' ), array( 'status' => 404 ) );
        }

        $details = $db->get_order_details( $order->id );

        return rest_ensure_response( array(
            'order'   => $order,
            'details' => $details,
        ) );
    }

    public function get_wilayas( $request ) {
        $db      = Flora_DB::get_instance();
        $wilayas = $db->get_wilayas();
        return rest_ensure_response( $wilayas );
    }

    public function get_communes( $request ) {
        $db          = Flora_DB::get_instance();
        $wilaya_code = $request->get_param( 'wilaya_code' );
        $communes    = $db->get_communes( absint( $wilaya_code ) );
        return rest_ensure_response( $communes );
    }

    public function get_products( $request ) {
        $db       = Flora_DB::get_instance();
        $products = $db->get_products();
        return rest_ensure_response( $products );
    }

    public function get_packs( $request ) {
        $db   = Flora_DB::get_instance();
        $packs = $db->get_packs();
        return rest_ensure_response( $packs );
    }

    public function admin_stats( $request ) {
        $db = Flora_DB::get_instance();

        return rest_ensure_response( array(
            'total_orders'   => $db->count_orders(),
            'pending_orders' => $db->count_orders( 'pending' ),
            'revenue'        => $db->get_revenue(),
            'today_revenue'  => $db->get_revenue( current_time( 'Y-m-d' ), current_time( 'Y-m-d' ) ),
            'top_products'   => $db->get_top_products( 5 ),
            'chart_data'     => $db->get_orders_by_date( 30 ),
        ) );
    }
}
