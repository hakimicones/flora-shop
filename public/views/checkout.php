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

                <!-- Identité du client : prénom, nom, email et téléphone (champs requis). -->

                <div class="flora-form-row flora-form-col-2">
                    <div class="flora-form-field">
                        <label for="first_name"><?php esc_html_e( 'Prénom', 'flora-shop' ); ?> *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="flora-form-field">
                        <label for="last_name"><?php esc_html_e( 'Nom', 'flora-shop' ); ?> *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="flora-form-row flora-form-col-2">
                    <div class="flora-form-field">
                        <label for="email"><?php esc_html_e( 'Email', 'flora-shop' ); ?> *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="flora-form-field">
                        <label for="phone"><?php esc_html_e( 'Téléphone', 'flora-shop' ); ?> *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>

                <div class="flora-form-row">
                    <div class="flora-form-field">
                        <label for="address"><?php esc_html_e( 'Adresse', 'flora-shop' ); ?> *</label>
                        <textarea id="address" name="address" rows="2" required></textarea>
                    </div>
                </div>

                <!-- Wilaya (liste préchargée) et commune (dépendante de la wilaya, remplie en JS). -->
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
                    <div class="flora-form-field">
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
