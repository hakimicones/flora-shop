<?php /* Vue de gestion du transport : trois onglets pour les wilayas, les communes et les tarifs de livraison. */ ?>

<div class="wrap flora-admin">
    <h1><?php esc_html_e( 'Gestion du transport', 'flora-shop' ); ?></h1>

    <?php /* --- Configuration des deux méthodes de livraison : activation, libellé et description --- */ ?>
    <div class="flora-card" style="background:#f6f7f7;border-left:4px solid #46b450;margin-bottom:15px;">
        <form method="post">
            <?php Flora_Helpers::wpnonce_field( 'flora_save_shipping_methods' ); ?>
            <input type="hidden" name="flora_action" value="save_shipping_methods">
            <h3 style="margin-top:0;"><?php esc_html_e( 'Méthodes de livraison', 'flora-shop' ); ?></h3>
            <p><?php esc_html_e( 'Activez les méthodes proposées au client lors de la commande et personnalisez leur libellé et description.', 'flora-shop' ); ?></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:70px;"><?php esc_html_e( 'Activée', 'flora-shop' ); ?></th>
                        <th style="width:45%;"><?php esc_html_e( 'Libellé', 'flora-shop' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'flora-shop' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $shipping_methods as $key => $cfg ) : ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="shipping_methods[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?>>
                            </td>
                            <td>
                                <input type="text" class="regular-text" name="shipping_methods[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $cfg['label'] ); ?>">
                            </td>
                            <td>
                                <input type="text" class="large-text" name="shipping_methods[<?php echo esc_attr( $key ); ?>][description]" value="<?php echo esc_attr( $cfg['description'] ); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin:10px 0 0;">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer les méthodes', 'flora-shop' ); ?></button>
            </p>
        </form>
    </div>

    <?php /* --- Onglets de navigation entre wilayas, communes et tarifs --- */ ?>
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-shipping&tab=wilayas' ) ); ?>" class="nav-tab <?php echo 'wilayas' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Wilayas', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-shipping&tab=communes' ) ); ?>" class="nav-tab <?php echo 'communes' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Communes', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-shipping&tab=rates' ) ); ?>" class="nav-tab <?php echo 'rates' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Tarifs', 'flora-shop' ); ?></a>
    </h2>

    <?php /* ================= Onglet Wilayas ================= */ ?>
    <?php if ( 'wilayas' === $tab ) : ?>
        <?php /* --- Import des données officielles depuis le fichier SQL de référence --- */ ?>
        <div class="flora-card" style="background:#f0f6fc;border-left:4px solid #2271b1;">
            <h3 style="margin-top:0;"><?php esc_html_e( 'Importer les données officielles', 'flora-shop' ); ?></h3>
            <p><?php esc_html_e( 'Importe les 69 wilayas et 1541 communes depuis le fichier SQL fourni avec le plugin (bouton « Importer » pour reprendre le chemin par défaut).', 'flora-shop' ); ?></p>
            <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <?php Flora_Helpers::wpnonce_field( 'flora_import_wilayas' ); ?>
                <input type="hidden" name="flora_action" value="import_wilayas">
                <div>
                    <label><?php esc_html_e( 'Chemin du fichier SQL', 'flora-shop' ); ?></label><br>
                    <input type="text" name="sql_file_path" class="regular-text" value="<?php echo esc_attr( Flora_Importer::resolve_sql_path() ); ?>">
                    <p class="description" style="margin:2px 0 0;"><?php esc_html_e( 'Laissez vide pour utiliser le fichier intégré au plugin (data/wilayas_communes.sql).', 'flora-shop' ); ?></p>
                </div>
                <div><button type="submit" class="button"><?php esc_html_e( 'Importer', 'flora-shop' ); ?></button></div>
            </form>
        </div>

        <?php /* --- Formulaire d'ajout / modification d'une wilaya --- */ ?>
        <div class="flora-card">
            <h3><?php esc_html_e( 'Ajouter / Modifier une Wilaya', 'flora-shop' ); ?></h3>
            <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <?php Flora_Helpers::wpnonce_field( 'flora_save_wilaya' ); ?>
                <input type="hidden" name="flora_action" value="save_wilaya">
                <div>
                    <label for="wilaya_code"><?php esc_html_e( 'Code', 'flora-shop' ); ?></label><br>
                    <input type="number" id="wilaya_code" name="code" class="small-text" required min="1" max="69" placeholder="16">
                </div>
                <div>
                    <label for="wilaya_name"><?php esc_html_e( 'Nom', 'flora-shop' ); ?></label><br>
                    <input type="text" id="wilaya_name" name="name" class="regular-text" required placeholder="Alger">
                </div>
                <div>
                    <label for="wilaya_name_ar"><?php esc_html_e( 'Nom arabe', 'flora-shop' ); ?></label><br>
                    <input type="text" id="wilaya_name_ar" name="name_ar" class="regular-text" placeholder="الجزائر">
                </div>
                <div>
                    <label for="wilaya_lat"><?php esc_html_e( 'Latitude', 'flora-shop' ); ?></label><br>
                    <input type="text" id="wilaya_lat" name="latitude" class="small-text" placeholder="36.7538">
                </div>
                <div>
                    <label for="wilaya_lng"><?php esc_html_e( 'Longitude', 'flora-shop' ); ?></label><br>
                    <input type="text" id="wilaya_lng" name="longitude" class="small-text" placeholder="3.0578">
                </div>
                <div><button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer', 'flora-shop' ); ?></button></div>
            </form>
        </div>

        <?php /* --- Tableau des wilayas enregistrées, avec export Excel --- */ ?>
        <p style="margin:14px 0 8px;">
            <a class="button button-primary flora-export-btn" href="<?php echo esc_url( Flora_Exporter::export_url( array( 'page' => 'flora-shipping', 'tab' => 'wilayas' ) ) ); ?>"><?php esc_html_e( 'Exporter Excel', 'flora-shop' ); ?></a>
        </p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:70px;"><?php esc_html_e( 'Code', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Nom arabe', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Lat / Lng', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $wilayas ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'Aucune wilaya configurée.', 'flora-shop' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $wilayas as $w ) : ?>
                        <tr>
                            <td><?php echo esc_html( $w->code ); ?></td>
                            <td><?php echo esc_html( $w->name ); ?></td>
                            <td><?php echo esc_html( $w->name_ar ); ?></td>
                            <td><?php echo $w->latitude ? esc_html( $w->latitude . ', ' . $w->longitude ) : '—'; ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette wilaya ?', 'flora-shop' ); ?>');">
                                    <?php wp_nonce_field( 'flora_delete_wilaya', '_flora_nonce' ); ?>
                                    <input type="hidden" name="flora_action" value="delete_wilaya">
                                    <input type="hidden" name="wilaya_code" value="<?php echo esc_attr( $w->code ); ?>">
                                    <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php /* ================= Onglet Communes ================= */ ?>
    <?php elseif ( 'communes' === $tab ) : ?>
        <?php /* --- Formulaire d'ajout d'une commune --- */ ?>
        <div class="flora-card">
            <h3><?php esc_html_e( 'Ajouter une commune', 'flora-shop' ); ?></h3>
            <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <?php Flora_Helpers::wpnonce_field( 'flora_save_commune' ); ?>
                <input type="hidden" name="flora_action" value="save_commune">
                <div>
                    <label><?php esc_html_e( 'Code postal', 'flora-shop' ); ?></label><br>
                    <input type="text" name="post_code" class="small-text" required placeholder="16001">
                </div>
                <div>
                    <label><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></label><br>
                    <select name="wilaya_code" required>
                        <option value=""><?php esc_html_e( '-- Choisir --', 'flora-shop' ); ?></option>
                        <?php foreach ( $wilayas as $w ) : ?>
                            <option value="<?php echo esc_attr( $w->code ); ?>"><?php echo esc_html( $w->code . ' - ' . $w->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e( 'Nom commune', 'flora-shop' ); ?></label><br>
                    <input type="text" name="name" class="regular-text" required placeholder="Bab Ezzouar">
                </div>
                <div>
                    <label><?php esc_html_e( 'Nom arabe', 'flora-shop' ); ?></label><br>
                    <input type="text" name="name_ar" class="regular-text" placeholder="باب الزوار">
                </div>
                <div>
                    <label><?php esc_html_e( 'Daïra', 'flora-shop' ); ?></label><br>
                    <input type="text" name="daira" class="regular-text" placeholder="Dar El Beida">
                </div>
                <div><button type="submit" class="button button-primary"><?php esc_html_e( 'Ajouter', 'flora-shop' ); ?></button></div>
            </form>
        </div>

        <?php /* --- Filtres communes (recherche + wilaya) et export Excel --- */ ?>
        <?php
        $wilaya_options = array();
        foreach ( $wilayas as $w ) {
            $wilaya_options[ $w->code ] = $w->code . ' - ' . $w->name;
        }
        Flora_Admin_List::bar( array(
            'page'   => 'flora-shipping',
            'tab'    => 'communes',
            'search' => $commune_search,
            'filters' => array(
                array(
                    'name'    => 'flora_wilaya',
                    'label'   => __( 'Wilaya', 'flora-shop' ),
                    'options' => $wilaya_options,
                    'current' => '' !== $commune_wilaya ? (string) $commune_wilaya : '',
                ),
            ),
            'export_args' => array(
                'page'        => 'flora-shipping',
                'tab'         => 'communes',
                'flora_s'     => $commune_search,
                'flora_wilaya' => '' !== $commune_wilaya ? (string) $commune_wilaya : '',
            ),
        ) );
        ?>

        <p class="flora-result-count"><?php printf( esc_html( _n( '%d commune trouvée.', '%d communes trouvées.', $total_communes, 'flora-shop' ) ), $total_communes ); ?></p>

        <?php /* --- Tableau des communes enregistrées --- */ ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:80px;"><?php esc_html_e( 'Code postal', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Commune', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Daïra', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( empty( $all_communes ) ) :
                ?>
                    <tr><td colspan="5"><?php esc_html_e( 'Aucune commune configurée.', 'flora-shop' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $all_communes as $c ) :
                        $w_obj = isset( $wilaya_map[ $c->wilaya_code ] ) ? $wilaya_map[ $c->wilaya_code ] : null;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $c->post_code ); ?></td>
                            <td><?php echo $w_obj ? esc_html( $w_obj->code . ' - ' . $w_obj->name ) : esc_html( $c->wilaya_code ); ?></td>
                            <td><?php echo esc_html( $c->name ); ?></td>
                            <td><?php echo esc_html( $c->daira ); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette commune ?', 'flora-shop' ); ?>');">
                                    <?php wp_nonce_field( 'flora_delete_commune', '_flora_nonce' ); ?>
                                    <input type="hidden" name="flora_action" value="delete_commune">
                                    <input type="hidden" name="commune_id" value="<?php echo esc_attr( $c->id ); ?>">
                                    <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $communes_pagination = array( 'page' => 'flora-shipping', 'tab' => 'communes' );
        if ( '' !== $commune_search ) { $communes_pagination['flora_s'] = $commune_search; }
        if ( '' !== $commune_wilaya ) { $communes_pagination['flora_wilaya'] = $commune_wilaya; }
        ?>
        <?php if ( $pagination = Flora_Admin_List::paginate( $total_communes, $communes_pagination ) ) : ?>
        <div class="tablenav bottom"><div class="tablenav-pages"><?php echo $pagination; ?></div></div>
        <?php endif; ?>

    <?php /* ================= Onglet Tarifs ================= */ ?>
    <?php elseif ( 'rates' === $tab ) : ?>
        <?php /* --- Formulaire d'ajout / modification d'un tarif (wilaya, commune optionnelle, frais) --- */ ?>
        <div class="flora-card">
            <h3><?php esc_html_e( 'Ajouter / Modifier un tarif', 'flora-shop' ); ?></h3>
            <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <?php Flora_Helpers::wpnonce_field( 'flora_save_rate' ); ?>
                <input type="hidden" name="flora_action" value="save_rate">
                <div>
                    <label><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></label><br>
                    <select name="wilaya_code" required id="flora-rate-wilaya">
                        <option value=""><?php esc_html_e( '-- Choisir --', 'flora-shop' ); ?></option>
                        <?php foreach ( $wilayas as $w ) : ?>
                            <option value="<?php echo esc_attr( $w->code ); ?>"><?php echo esc_html( $w->code . ' - ' . $w->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e( 'Commune (optionnel = tarif wilaya)', 'flora-shop' ); ?></label><br>
                    <select name="commune_id" id="flora-rate-commune">
                        <option value="0"><?php esc_html_e( '-- Tarif Wilaya --', 'flora-shop' ); ?></option>
                    </select>
                </div>
                <div>
                    <label><?php esc_html_e( 'Frais fixe (domicile)', 'flora-shop' ); ?></label><br>
                    <input type="number" step="0.01" name="base_fee" class="small-text" required value="0.00">
                </div>
                <div>
                    <label><?php esc_html_e( 'Frais/kg (domicile)', 'flora-shop' ); ?></label><br>
                    <input type="number" step="0.01" name="per_kg_fee" class="small-text" required value="0.00">
                </div>
                <div>
                    <label><?php esc_html_e( 'Frais fixe (bureau liaison)', 'flora-shop' ); ?></label><br>
                    <input type="number" step="0.01" name="bureau_fee" class="small-text" required value="0.00">
                </div>
                <div>
                    <label><?php esc_html_e( 'Frais/kg (bureau liaison)', 'flora-shop' ); ?></label><br>
                    <input type="number" step="0.01" name="bureau_per_kg_fee" class="small-text" required value="0.00">
                </div>
                <div><button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer', 'flora-shop' ); ?></button></div>
            </form>
        </div>

        <?php /* --- Tableau des tarifs enregistrés (wilaya/commune avec rubriques domicile et bureau) --- */ ?>
        <p style="margin:14px 0 8px;">
            <a class="button button-primary flora-export-btn" href="<?php echo esc_url( Flora_Exporter::export_url( array( 'page' => 'flora-shipping', 'tab' => 'rates' ) ) ); ?>"><?php esc_html_e( 'Exporter Excel', 'flora-shop' ); ?></a>
        </p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Wilaya', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Commune', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Frais fixe (domicile)', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Frais/kg (domicile)', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Frais fixe (bureau)', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Frais/kg (bureau)', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $all_communes_map = array();
                foreach ( $wilayas as $ww ) {
                    $ccs = $db->get_communes( $ww->code );
                    foreach ( $ccs as $cc ) {
                        $all_communes_map[ $cc->id ] = $cc->name;
                    }
                }

                if ( empty( $rates ) ) :
                ?>
                    <tr><td colspan="7"><?php esc_html_e( 'Aucun tarif configuré.', 'flora-shop' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $rates as $r ) :
                        $w_name = isset( $wilaya_map[ $r->wilaya_code ] ) ? $wilaya_map[ $r->wilaya_code ]->name : $r->wilaya_code;
                        $c_name = $r->commune_id > 0 && isset( $all_communes_map[ $r->commune_id ] ) ? $all_communes_map[ $r->commune_id ] : '—';
                    ?>
                        <tr>
                            <td><?php echo esc_html( $w_name ); ?></td>
                            <td><?php echo esc_html( $c_name ); ?></td>
                            <td><?php echo esc_html( Flora_Helpers::format_price( $r->base_fee ) ); ?></td>
                            <td><?php echo esc_html( Flora_Helpers::format_price( $r->per_kg_fee ) ); ?></td>
                            <td><?php echo esc_html( Flora_Helpers::format_price( $r->bureau_fee ) ); ?></td>
                            <td><?php echo esc_html( Flora_Helpers::format_price( $r->bureau_per_kg_fee ) ); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer ce tarif ?', 'flora-shop' ); ?>');">
                                    <?php wp_nonce_field( 'flora_delete_rate', '_flora_nonce' ); ?>
                                    <input type="hidden" name="flora_action" value="delete_rate">
                                    <input type="hidden" name="rate_id" value="<?php echo esc_attr( $r->id ); ?>">
                                    <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php /* --- Chargement AJAX des communes de la wilaya sélectionnée pour le formulaire de tarif --- */ ?>
        <script>
        jQuery(document).ready(function($) {
            $('#flora-rate-wilaya').on('change', function() {
                var wilayaCode = $(this).val();
                var $commune = $('#flora-rate-commune');
                $commune.html('<option value="0"><?php esc_html_e( "-- Tarif Wilaya --", "flora-shop" ); ?></option>');
                if (wilayaCode) {
                    $.ajax({
                        url: floraAdmin.restUrl + 'communes/' + wilayaCode,
                        headers: { 'X-WP-Nonce': floraAdmin.nonce },
                        success: function(data) {
                            if (data && data.length) {
                                $.each(data, function(i, c) {
                                    $commune.append('<option value="' + c.id + '">' + c.name + '</option>');
                                });
                            }
                        }
                    });
                }
            });
        });
        </script>
    <?php endif; ?>
</div>
