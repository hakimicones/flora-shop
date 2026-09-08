<!--
    Page de commande (shortcode [flora_checkout]).
    Formulaire de livraison (client + wilaya/commune) et résumé de la commande ;
    la soumission est gérée par cart.js via l'endpoint REST /checkout (nonce wp_rest).
-->
<div class="flora-shop-wrap flora-checkout-wrap">
    <h2><?php esc_html_e( 'Passer la commande', 'flora-shop' ); ?></h2>

    <div class="flora-checkout-layout">
        <!-- Formulaire : coordonnées du client et informations de livraison. -->
        <div class="flora-checkout-form-wrap">
            <form id="flora-checkout-form">
                <h3><?php esc_html_e( 'Informations de livraison', 'flora-shop' ); ?></h3>

                <!-- Choix de la méthode de livraison : domicile ou bureau de liaison (radio). -->
                <div class="flora-form-row">
                    <div class="flora-form-field">
                        <label><?php esc_html_e( 'Méthode de livraison', 'flora-shop' ); ?> *</label>
                        <div class="flora-shipping-methods" id="flora-shipping-methods">
                            <?php foreach ( $shipping_methods as $skey => $scfg ) : ?>
                                <?php if ( ! Flora_Helpers::is_method_enabled( $skey ) ) { continue; } ?>
                                <label class="flora-shipping-method">
                                    <input type="radio" name="shipping_method" value="<?php echo esc_attr( $skey ); ?>" <?php checked( 'home' === $skey ); ?>>
                                    <span >
                                        <strong><?php echo esc_html( $scfg['label'] ); ?></strong>
                                        <?php if ( ! empty( $scfg['description'] ) ) : ?>
                                            <small class="memo"><?php echo esc_html( $scfg['description'] ); ?></small>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Identité du client : nom complet, email et téléphone (champs requis). -->
                <div class="flora-form-row">
                    <div class="flora-form-field">
                        
                        <input type="text"  class="flora-input" id="full_name" placeholder="<?php esc_html_e( 'Nom complet', 'flora-shop' ); ?> *" name="full_name" required autocomplete="name">
                    </div>
                </div>

                <?php $class=  ( get_option( 'flora_show_order_email', 1 ) ) ? 'flora-form-col-2' : ''; ?>
                    
                    
                    
                    
                   

                <div class="flora-form-row <?php echo $class; ?>">
                    <?php if ( get_option( 'flora_show_order_email', 1 ) ) : ?>
                        <div class="flora-form-field">
                            
                            <input class="flora-input" type="email" id="email" placeholder="<?php esc_html_e( 'Email', 'flora-shop' ); ?> *" name="email" required>
                        </div>
                    <?php endif; ?>
                    <div class="flora-form-field">
                        <input class="flora-input" type="tel" id="phone" placeholder="<?php esc_html_e( 'Téléphone', 'flora-shop' ); ?> *" name="phone" required>
                    </div>
                </div>

                <div class="flora-form-row" id="flora-address-row">
                    <div class="flora-form-field">
                        <textarea id="address" name="address" placeholder="<?php esc_html_e( 'Adresse', 'flora-shop' ); ?> *" rows="2" required></textarea>
                    </div>
                </div>

                <!-- Wilaya (liste préchargée) et commune (dépendante de la wilaya, remplie en JS). -->
                <!-- En mode « bureau de liaison », seule la wilaya est demandée : l'adresse et la commune sont masquées. -->
                <div class="flora-form-row flora-form-col-2">
                    <div class="flora-form-field">
                        <label for="wilaya"><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?> *</label>
                        <select id="wilaya" name="wilaya_code" required>
                            <option value=""><?php esc_html_e( '-- Choisir votre wilaya --', 'flora-shop' ); ?></option>
                            <?php foreach ( $wilayas as $w ) : ?>
                                <option value="<?php echo esc_attr( $w->code ); ?>"><?php echo esc_html( $w->code . ' - ' . $w->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flora-form-field" id="flora-commune-field">
                        <label for="commune"><?php esc_html_e( 'Commune', 'flora-shop' ); ?></label>
                        <select id="commune" name="commune_id">
                            <option value="0"><?php esc_html_e( '-- Choisir votre commune --', 'flora-shop' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="flora-form-row">
                    <div class="flora-form-field">
                        <label for="notes"><?php esc_html_e( 'Notes (optionnel)', 'flora-shop' ); ?></label>
                        <textarea id="notes" name="notes" rows="2" placeholder="<?php esc_attr_e( 'Instructions spéciales de livraison...', 'flora-shop' ); ?>"></textarea>
                    </div>
                </div>

                <div class="flora-checkout-submit">
                    <button type="submit" class="button button-primary flora-place-order">
                        <?php esc_html_e( 'Confirmer la commande', 'flora-shop' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Résumé de la commande : récapitulatif du panier et totaux, chargé et affiché par cart.js. -->
        <div class="flora-checkout-summary">
            <h3><?php esc_html_e( 'Résumé de la commande', 'flora-shop' ); ?></h3>
            <div id="flora-checkout-summary-content">
                <p><?php esc_html_e( 'Chargement...', 'flora-shop' ); ?></p>
            </div>
        </div>
    </div>
</div>
