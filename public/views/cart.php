<!--
    Page panier (shortcode [flora_cart]).
    Conteneur de chargement : le contenu du panier est rendu dynamiquement par
    cart.js via l'endpoint REST /cart.
-->
<div class="flora-shop-wrap flora-cart-wrap">
    <!-- Zone injectée : liste des articles, quantités, totaux et boutons d'action. -->
    <div id="flora-cart-content">
        <div class="flora-cart-loading"><span class="flora-spinner"></span><?php esc_html_e( 'Chargement du panier...', 'flora-shop' ); ?></div>
    </div>
</div>
