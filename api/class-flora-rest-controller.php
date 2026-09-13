<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Contrôleur REST de flora-shop.
 *
 * Enregistre toutes les routes de l'API sous le namespace « flora-shop/v1 ».
 *
 * Sécurité :
 * - Les routes de lecture (GET) sont ouvertes au public (permission_callback => __return_true) ;
 * - La route /checkout est protégée par le nonce WordPress « wp_rest » (en-tête X-WP-Nonce) ;
 * - La route /admin/stats est réservée aux utilisateurs ayant la capacité « manage_options ».
 */
class Flora_REST_Controller {

    private $namespace = 'flora-shop/v1';

    public function register_routes() {
        // GET /cart — Retourne le contenu actuel du panier de la session.
        register_rest_route( $this->namespace, '/cart', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_cart' ),
            'permission_callback' => '__return_true',
        ) );

        // POST /cart/add — Ajoute un article au panier (type : 'product'|'pack', id, quantity).
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

        // POST /cart/update — Met à jour la quantité de l'article situé à l'index donné (index, quantity).
        register_rest_route( $this->namespace, '/cart/update', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_cart' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'index'    => array( 'required' => true, 'type' => 'integer' ),
                'quantity' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        // POST /cart/remove — Retire du panier l'article situé à l'index donné.
        register_rest_route( $this->namespace, '/cart/remove', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'remove_from_cart' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'index' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        // POST /cart/clear — Vide entièrement le panier de la session.
        register_rest_route( $this->namespace, '/cart/clear', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'clear_cart' ),
            'permission_callback' => '__return_true',
        ) );

        // POST /cart/location — Enregistre la wilaya et la commune de livraison choisies (wilaya_code, commune_id).
        register_rest_route( $this->namespace, '/cart/location', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'set_location' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'wilaya_code' => array( 'required' => true, 'type' => 'integer' ),
                'commune_id'  => array( 'required' => false, 'type' => 'integer', 'default' => 0 ),
            ),
        ) );

        // POST /cart/method — Enregistre la méthode de livraison choisie : 'home' (domicile) ou 'liaison' (bureau).
        register_rest_route( $this->namespace, '/cart/method', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'set_shipping_method' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'method' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'home', 'liaison' ) ),
            ),
        ) );

        // GET /shipping-methods — Liste des méthodes de livraison actives (alimente le sélecteur du checkout).
        register_rest_route( $this->namespace, '/shipping-methods', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_shipping_methods' ),
            'permission_callback' => '__return_true',
        ) );

        // POST /checkout — Valide la commande ; nécessite le nonce « wp_rest » vérifié par check_nonce().
        register_rest_route( $this->namespace, '/checkout', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'checkout' ),
            'permission_callback' => array( $this, 'check_nonce' ),
        ) );

        // GET /orders/{number} — Récupère une commande à partir de son numéro (utilisé par la confirmation).
        register_rest_route( $this->namespace, '/orders/(?P<number>[A-Za-z0-9-]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_order' ),
            'permission_callback' => '__return_true',
        ) );

        // GET /wilayas — Liste des wilayas disponibles pour la livraison.
        register_rest_route( $this->namespace, '/wilayas', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_wilayas' ),
            'permission_callback' => '__return_true',
        ) );

        // GET /communes/{wilaya_code} — Communes de la wilaya dont le code est passé en paramètre.
        register_rest_route( $this->namespace, '/communes/(?P<wilaya_code>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_communes' ),
            'permission_callback' => '__return_true',
        ) );

        // GET /products — Liste des produits du catalogue.
        register_rest_route( $this->namespace, '/products', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_products' ),
            'permission_callback' => '__return_true',
        ) );

        // GET /packs — Liste des packs du catalogue.
        register_rest_route( $this->namespace, '/packs', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_packs' ),
            'permission_callback' => '__return_true',
        ) );

        // GET /admin/stats — Statistiques d'administration (réservé aux administrateurs, voir admin_permission()).
        register_rest_route( $this->namespace, '/admin/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'admin_stats' ),
            'permission_callback' => array( $this, 'admin_permission' ),
        ) );
    }

    // check_nonce : vérifie le nonce WordPress « wp_rest » présent dans l'en-tête X-WP-Nonce de la requête.
    public function check_nonce( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        return $nonce && wp_verify_nonce( $nonce, 'wp_rest' );
    }

    // admin_permission : autorise l'accès uniquement aux utilisateurs pouvant gérer les options du site (administrateurs).
    public function admin_permission() {
        return current_user_can( 'manage_options' );
    }

    // get_cart : renvoie le panier de la session au format JSON.
    public function get_cart( $request ) {
        return rest_ensure_response( Flora_Cart::get_cart_json() );
    }

    // add_to_cart : ajoute un produit ou un pack au panier et renvoie le panier mis à jour.
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

    // update_cart : applique la nouvelle quantité à l'article situé à l'index indiqué.
    public function update_cart( $request ) {
        $index    = $request->get_param( 'index' );
        $quantity = $request->get_param( 'quantity' );

        Flora_Cart::update_quantity( $index, $quantity );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    // remove_from_cart : supprime du panier l'article situé à l'index indiqué.
    public function remove_from_cart( $request ) {
        $index = $request->get_param( 'index' );
        Flora_Cart::remove_item( $index );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    // clear_cart : supprime tous les articles du panier de la session.
    public function clear_cart( $request ) {
        Flora_Cart::clear();

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    // set_location : mémorise la wilaya et la commune de livraison dans le panier (calcul du transport).
    public function set_location( $request ) {
        $wilaya_code = $request->get_param( 'wilaya_code' );
        $commune_id  = $request->get_param( 'commune_id' );

        Flora_Cart::set_location( $wilaya_code, $commune_id );

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    // set_shipping_method : mémorise la méthode de livraison (domicile ou bureau) dans le panier.
    public function set_shipping_method( $request ) {
        $method = $request->get_param( 'method' );

        $result = Flora_Cart::set_shipping_method( $method );

        if ( false === $result ) {
            return new WP_Error( 'invalid_method', __( 'Méthode de livraison invalide.', 'flora-shop' ), array( 'status' => 400 ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'cart'    => Flora_Cart::get_cart_json(),
        ) );
    }

    // get_shipping_methods : renvoie la liste des méthodes de livraison actives (clé, libellé, description).
    public function get_shipping_methods( $request ) {
        $methods = array();
        foreach ( Flora_Helpers::flora_shipping_methods() as $key => $cfg ) {
            if ( Flora_Helpers::is_method_enabled( $key ) ) {
                $methods[] = array(
                    'method'      => $key,
                    'label'       => Flora_Helpers::shipping_method_label( $key ),
                    'description' => Flora_Helpers::shipping_method_description( $key ),
                );
            }
        }
        return rest_ensure_response( $methods );
    }

    // checkout : valide les informations de facturation puis lance le traitement de la commande (retour 201 si succès).
    public function checkout( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['billing'] ) ) {
            return new WP_Error( 'missing_billing', __( 'Informations de facturation manquantes.', 'flora-shop' ), array( 'status' => 400 ) );
        }

        $billing = $params['billing'];

        // Champ email requis uniquement si le formulaire l'affiche (option « Confirmation » active).
        $show_email = (bool) get_option( 'flora_show_order_email', 1 );

        // L'adresse est requise uniquement pour la livraison à domicile ; pour le
        // bureau de liaison, seul le choix de la wilaya (tarif) est nécessaire.
        $required_fields = array( 'full_name', 'phone', 'wilaya_code' );
        if ( $show_email ) {
            $required_fields[] = 'email';
        }
        if ( 'home' === Flora_Cart::get_shipping_method() ) {
            $required_fields[] = 'address';
        }

        foreach ( $required_fields as $field ) {
            if ( empty( $billing[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Le champ %s est requis.', 'flora-shop' ), $field ), array( 'status' => 400 ) );
            }
        }

        if ( $show_email && ! empty( $billing['email'] ) && ! is_email( $billing['email'] ) ) {
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

    // get_order : charge une commande et ses lignes de détail pour l'écran de confirmation.
    public function get_order( $request ) {
        $number = $request->get_param( 'number' );
        $db     = Flora_DB::get_instance();
        $order  = $db->get_order_by_number( $number );

        if ( ! $order ) {
            return new WP_Error( 'not_found', __( 'Commande introuvable.', 'flora-shop' ), array( 'status' => 404 ) );
        }

        $details = $db->get_order_details( $order->id );

        // L'email est retiré de la réponse publique s'il est masqué par la configuration admin.
        $order->email = get_option( 'flora_show_order_email', 1 ) ? $order->email : '';

        return rest_ensure_response( array(
            'order'   => $order,
            'details' => $details,
        ) );
    }

    // get_wilayas : renvoie la liste des wilayas (alimente le menu déroulant du checkout).
    public function get_wilayas( $request ) {
        $db      = Flora_DB::get_instance();
        $wilayas = $db->get_wilayas();
        return rest_ensure_response( $wilayas );
    }

    // get_communes : renvoie les communes de la wilaya dont le code est passé en paramètre.
    public function get_communes( $request ) {
        $db          = Flora_DB::get_instance();
        $wilaya_code = $request->get_param( 'wilaya_code' );
        $communes    = $db->get_communes( absint( $wilaya_code ) );
        return rest_ensure_response( $communes );
    }

    // get_products : renvoie la liste des produits du catalogue, localisés dans la langue active.
    public function get_products( $request ) {
        $db       = Flora_DB::get_instance();
        $lang     = Flora_Helpers::get_active_lang();
        $products = $db->hydrate_languages( 'product', $db->get_products(), $lang );
        return rest_ensure_response( $products );
    }

    // get_packs : renvoie la liste des packs du catalogue, localisés dans la langue active.
    public function get_packs( $request ) {
        $db    = Flora_DB::get_instance();
        $lang  = Flora_Helpers::get_active_lang();
        $packs = $db->hydrate_languages( 'pack', $db->get_packs(), $lang );
        return rest_ensure_response( $packs );
    }

    // admin_stats : agrège les statistiques (commandes, chiffre d'affaires, meilleures ventes, graphique 30 jours).
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
