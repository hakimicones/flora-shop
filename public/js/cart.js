jQuery(document).ready(function($) {
    'use strict';

    var api = floraShop.restUrl;
    var nonce = floraShop.nonce;
    var i18n = floraShop.i18n;

    function floraApi(method, endpoint, data) {
        return $.ajax({
            url: api + endpoint,
            method: method,
            headers: { 'X-WP-Nonce': nonce },
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null
        });
    }

    function showNotification(message, type) {
        type = type || 'success';
        var $notif = $('#flora-cart-notification');
        if ($notif.length === 0) {
            $notif = $('<div id="flora-cart-notification" class="flora-notification"></div>');
            $('body').append($notif);
        }
        $notif.removeClass('flora-notif-success flora-notif-error')
              .addClass('flora-notif-' + type)
              .html(message)
              .fadeIn(300);
        setTimeout(function() { $notif.fadeOut(300); }, 3000);
    }

    // ========== ADD TO CART ==========
    $(document).on('click', '.flora-add-to-cart', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var type = $btn.data('type');
        var id = $btn.data('id');
        var qtyInput = $btn.data('qty-input');
        var qty = 1;

        if (qtyInput) {
            qty = parseInt($('#' + qtyInput).val()) || 1;
        }

        var origText = $btn.text();
        $btn.prop('disabled', true).text(i18n.processing);

        floraApi('POST', 'cart/add', { type: type, id: id, quantity: qty })
            .done(function(response) {
                updateCartCount(response.cart.item_count);
                openCartDrawer(response.cart);
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            })
            .fail(function() {
                showNotification(i18n.error, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).text(origText);
            });
    });

    // ========== QUANTITY CONTROLS ==========
    $(document).on('click', '.flora-qty-minus, .flora-qty-plus', function(e) {
        e.preventDefault();
        var $input = $(this).siblings('.flora-qty-input');
        var val = parseInt($input.val()) || 1;
        if ($(this).hasClass('flora-qty-plus')) {
            $input.val(val + 1);
        } else if (val > 1) {
            $input.val(val - 1);
        }
    });

    // Cart page quantity controls
    $(document).on('click', '.flora-qty-minus-cart', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        var $input = $(this).siblings('.flora-qty-input-cart');
        var val = parseInt($input.val()) || 1;
        if (val > 1) {
            floraApi('POST', 'cart/update', { index: index, quantity: val - 1 })
                .done(function(response) {
                    updateCartCount(response.cart.item_count);
                    renderCartDrawer(response.cart);
                    renderCartPage(response.cart);
                    renderCheckoutSummary(response.cart);
                });
        }
    });

    $(document).on('click', '.flora-qty-plus-cart', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        var $input = $(this).siblings('.flora-qty-input-cart');
        var val = parseInt($input.val()) || 1;
        floraApi('POST', 'cart/update', { index: index, quantity: val + 1 })
            .done(function(response) {
                updateCartCount(response.cart.item_count);
                renderCartDrawer(response.cart);
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            });
    });

    $(document).on('change', '.flora-qty-input-cart', function() {
        var index = $(this).data('index');
        var qty = parseInt($(this).val()) || 1;
        floraApi('POST', 'cart/update', { index: index, quantity: qty })
            .done(function(response) {
                updateCartCount(response.cart.item_count);
                renderCartDrawer(response.cart);
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            });
    });

    // ========== REMOVE / CLEAR ==========
    $(document).on('click', '.flora-remove-item', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        floraApi('POST', 'cart/remove', { index: index })
            .done(function(response) {
                showNotification(i18n.removed, 'success');
                updateCartCount(response.cart.item_count);
                renderCartDrawer(response.cart);
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            });
    });

    $(document).on('click', '.flora-clear-cart', function(e) {
        e.preventDefault();
        if (confirm(i18n.confirm)) {
            floraApi('POST', 'cart/clear')
                .done(function(response) {
                    updateCartCount(response.cart.item_count);
                    renderCartDrawer(response.cart);
                    renderCartPage(response.cart);
                    renderCheckoutSummary(response.cart);
                });
        }
    });

function renderCartPromotions(cart) {
        var promotions = cart && cart.promotions ? cart.promotions : [];
        if (!promotions.length) return '';
        var html = '<div class="flora-cart-promotions"><h4> Promotions appliquées</h4><table>';
        for (var i = 0; i < promotions.length; i++) {
            var p = promotions[i];
            var amountStr = p.free ? 'Article offert ajouté' : '- ' + formatPrice(p.amount);
            html += '<tr><td>' + escapeHtml(p.title) + '</td><td class="flora-promo-amount">' + amountStr + '</td></tr>';
        }
        html += '</table></div>';
        return html;
    }

    function renderCheckoutPromotions(cart) {
        var promotions = cart && cart.promotions ? cart.promotions : [];
        if (!promotions.length) return '';
        var html = '<div class="flora-cart-promotions"><h4>Promotions appliquées</h4><table>';
        for (var i = 0; i < promotions.length; i++) {
            var p = promotions[i];
            var amountStr = p.free ? 'Article offert ajouté' : '- ' + formatPrice(p.amount);
            html += '<tr><td>' + escapeHtml(p.title) + '</td><td class="flora-promo-amount">' + amountStr + '</td></tr>';
        }
        html += '</table></div>';
        return html;
    }

    // ========== CART PAGE RENDERING ==========
    function renderCartPage(cart) {
        var $container = $('#flora-cart-content');
        if ($container.length === 0) return;

        if (!cart) {
            floraApi('GET', 'cart').done(function(data) { renderCartPage(data); });
            return;
        }

        if (!cart.items || cart.items.length === 0) {
            $container.html(
                '<div class="flora-cart-empty">' +
                '<p>' + i18n.empty || 'Votre panier est vide.' + '</p>' +
                '<a href="' + floraShop.restUrl.replace('/wp-json/flora-shop/v1/', '') + '" class="button button-primary">Voir les produits</a>' +
                '</div>'
            );
            return;
        }

        var html = '<table class="flora-cart-table"><thead><tr>' +
            '<th>Article</th><th>Prix</th><th>Quantité</th><th>Total</th><th></th>' +
            '</tr></thead><tbody>';

        for (var i = 0; i < cart.items.length; i++) {
            var item = cart.items[i];
            var freeClass = item.is_free ? ' flora-cart-free-item' : '';
            html += '<tr class="' + freeClass + '">';
            html += '<td>' + escapeHtml(item.name);
            if (item.is_free) html += ' <span class="flora-badge-free">GRATUIT</span>';
            html += '</td>';
            html += '<td>' + (item.is_free ? '—' : formatPrice(item.price)) + '</td>';

            if (item.is_free) {
                html += '<td>' + item.quantity + '</td>';
            } else {
                html += '<td><div class="flora-qty-control">' +
                    '<button type="button" class="flora-qty-minus-cart" data-index="' + item.index + '">−</button>' +
                    '<input type="number" class="flora-qty-input-cart" data-index="' + item.index + '" value="' + item.quantity + '" min="1">' +
                    '<button type="button" class="flora-qty-plus-cart" data-index="' + item.index + '">+</button>' +
                    '</div></td>';
            }

            html += '<td>' + (item.is_free ? 'Gratuit' : formatPrice(item.line_total)) + '</td>';
            html += '<td>' + (item.is_free ? '' : '<button type="button" class="flora-remove-item" data-index="' + item.index + '" title="Supprimer">✕</button>') + '</td>';
            html += '</tr>';
        }
        html += '</tbody></table>';

        if (cart.totals) {
            html += '<div class="flora-cart-totals"><table>';
            html += '<tr><td>Sous-total</td><td>' + formatPrice(cart.totals.subtotal) + '</td></tr>';
            if (parseFloat(cart.totals.discount_total) > 0) {
                html += '<tr class="flora-discount-row"><td>Remises</td><td>- ' + formatPrice(cart.totals.discount_total) + '</td></tr>';
            }
            if (parseFloat(cart.totals.shipping_fee) > 0) {
                html += '<tr><td>Transport</td><td>' + formatPrice(cart.totals.shipping_fee) + '</td></tr>';
            }
            html += '<tr class="flora-total-row"><td><strong>Total</strong></td><td><strong>' + formatPrice(cart.totals.total) + '</strong></td></tr>';
            html += '</table></div>';
        }

        html += renderCartPromotions(cart);

        html += '<div class="flora-cart-actions">' +
            '<a href="#" class="button flora-clear-cart">Vider le panier</a>' +
            '<a href="' + getCheckoutUrl() + '" class="button button-primary">Passer la commande</a>' +
            '</div>';

        $container.html(html);
    }

    // ========== CART DRAWER (OFFCANVAS) ==========
    function injectCartDrawer() {
        if ($('#flora-cart-drawer').length) return;

        var overlay = $('<div class="flora-cart-drawer-overlay"></div>');
        var drawer = $('<aside id="flora-cart-drawer" role="dialog" aria-hidden="true" aria-label="' + i18n.cart_title + '"></aside>');
        drawer.append(
            '<div class="flora-drawer-header">' +
            '<div class="flora-drawer-title"><h3>' + i18n.cart_title + ' <span class="flora-cart-count" style="display:none;">0</span></h3></div>' +
            '<button type="button" class="flora-drawer-close" aria-label="' + i18n.close + '">✕</button>' +
            '</div>' +
            '<div class="flora-drawer-items" id="flora-cart-drawer-items"></div>' +
            '<div class="flora-drawer-footer" id="flora-cart-drawer-footer"></div>'
        );
        $('body').append(overlay, drawer);
    }

    function renderCartDrawer(cart) {
        var $items = $('#flora-cart-drawer-items');
        var $footer = $('#flora-cart-drawer-footer');
        if ($items.length === 0) return;

        if (!cart) {
            floraApi('GET', 'cart').done(function(data) { renderCartDrawer(data); });
            return;
        }

        $items.empty();
        $footer.empty();

        if (!cart.items || cart.items.length === 0) {
            $items.html(
                '<div class="flora-drawer-empty">' +
                '<p>' + (i18n.empty || 'Votre panier est vide.') + '</p>' +
                '<a href="' + floraShop.pageUrls.shop + '" class="button button-primary">' + i18n.view_products + '</a>' +
                '</div>'
            );
            return;
        }

        for (var i = 0; i < cart.items.length; i++) {
            var item = cart.items[i];
            var row = $('<div class="flora-drawer-item"></div>');
            if (item.is_free) row.addClass('flora-drawer-free');
            row.append('<div class="flora-drawer-item-info">' +
                '<div class="flora-drawer-item-name">' + escapeHtml(item.name) +
                (item.is_free ? ' <span class="flora-badge-free">GRATUIT</span>' : '') +
                '</div>');
            if (item.is_free) {
                row.find('.flora-drawer-item-info').append('<div class="flora-drawer-item-qty">x' + item.quantity + '</div>');
            } else {
                row.find('.flora-drawer-item-info').append(
                    '<div class="flora-drawer-item-qty">' +
                    '<span>' + item.quantity + ' × ' + formatPrice(item.price) + '</span>' +
                    '</div>'
                );
                row.find('.flora-drawer-item-info').append(
                    '<div class="flora-qty-control">' +
                    '<button type="button" class="flora-qty-minus-cart" data-index="' + item.index + '">−</button>' +
                    '<input type="number" class="flora-qty-input-cart" data-index="' + item.index + '" value="' + item.quantity + '" min="1">' +
                    '<button type="button" class="flora-qty-plus-cart" data-index="' + item.index + '">+</button>' +
                    '</div>'
                );
            }
            row.append('<div class="flora-drawer-item-price">' + (item.is_free ? 'Gratuit' : formatPrice(item.line_total)) + '</div>');
            if (!item.is_free) {
                row.append('<button type="button" class="flora-remove-item" data-index="' + item.index + '" title="Supprimer">✕</button>');
            }
            $items.append(row);
        }

        var footerHtml = '';
        if (cart.totals) {
            footerHtml += '<div class="flora-drawer-totals">';
            footerHtml += '<div class="flora-drawer-total-row"><span>Sous-total</span><span>' + formatPrice(cart.totals.subtotal) + '</span></div>';
            if (parseFloat(cart.totals.discount_total) > 0) {
                footerHtml += '<div class="flora-drawer-total-row flora-discount-row"><span>Remises</span><span>- ' + formatPrice(cart.totals.discount_total) + '</span></div>';
            }
            footerHtml += '<div class="flora-drawer-total-row"><span>' + escapeHtml(getMethodLabel(cart)) + '</span><span>' + (parseFloat(cart.totals.shipping_fee) > 0 ? formatPrice(cart.totals.shipping_fee) : 'Gratuit') + '</span></div>';
            footerHtml += '<div class="flora-drawer-total-row flora-drawer-grand-total"><span><strong>Total</strong></span><span><strong>' + formatPrice(cart.totals.total) + '</strong></span></div>';
            footerHtml += '</div>';
        }
        footerHtml += '<div class="flora-drawer-actions">' +
            '<a href="' + getCheckoutUrl() + '" class="button button-primary">' + i18n.place_order + '</a>' +
            '<a href="' + floraShop.pageUrls.cart + '">' + i18n.view_cart + '</a>' +
            '<a href="#" class="flora-clear-cart">' + i18n.clear + '</a>' +
            '</div>';
        $footer.html(footerHtml);
    }

    function openCartDrawer(cart) {
        injectCartDrawer();
        renderCartDrawer(cart);
        $('#flora-cart-drawer').addClass('open').attr('aria-hidden', 'false');
        $('.flora-cart-drawer-overlay').addClass('open');
        $('body').addClass('flora-drawer-open');
    }

    function closeCartDrawer() {
        $('#flora-cart-drawer').removeClass('open').attr('aria-hidden', 'true');
        $('.flora-cart-drawer-overlay').removeClass('open');
        $('body').removeClass('flora-drawer-open');
    }

    $(document).on('click', '.flora-cart-open', function(e) {
        e.preventDefault();
        openCartDrawer();
    });

    $(document).on('click', '.flora-cart-drawer-overlay', closeCartDrawer);
    $(document).on('click', '.flora-drawer-close', closeCartDrawer);

$(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            closeCartDrawer();
        }
    });

    // ========== SHIPPING METHOD ==========
    function getMethodLabel(cart) {
        var options = cart.shipping_options || [];
        var current = cart.shipping_method || 'home';
        for (var i = 0; i < options.length; i++) {
            if (options[i].method === current) return options[i].label;
        }
        return 'Transport';
    }

    function toggleShippingFields(method) {
        var isHome = method === 'home';
        $('#flora-address-row').toggle(isHome);
        $('#flora-commune-field').toggle(isHome);
        $('#address').prop('required', isHome);
    }

    function renderShippingFees(cart) {
        // Affiche le frais estimé sous chaque méthode uniquement si une wilaya est choisie.
        if (!cart.wilaya_code || cart.wilaya_code <= 0) return;
        var options = cart.shipping_options || [];
        for (var i = 0; i < options.length; i++) {
            var $radio = $('input[name="shipping_method"][value="' + options[i].method + '"]');
            if ($radio.length === 0) continue;
            var $label = $radio.closest('.flora-shipping-method');
            $label.find('.flora-shipping-fee').remove();
            var feeText = parseFloat(options[i].fee) > 0 ? formatPrice(options[i].fee) : 'Gratuit';
            $label.find('small').first().after(' <span class="flora-shipping-fee">(' + feeText + ')</span>');
        }
    }

    function syncShippingMethod(cart) {
        var method = cart.shipping_method || 'home';
        $('input[name="shipping_method"]').prop('checked', false);
        $('input[name="shipping_method"][value="' + method + '"]').prop('checked', true);
        toggleShippingFields(method);
        renderShippingFees(cart);
    }

    $(document).on('change', 'input[name="shipping_method"]', function() {
        var method = $(this).val();
        toggleShippingFields(method);
        floraApi('POST', 'cart/method', { method: method })
            .done(function(response) {
                renderCheckoutSummary(response.cart);
                renderShippingFees(response.cart);
            })
            .fail(function() {
                showNotification(i18n.error, 'error');
            });
    });

    // ========== CHECKOUT ==========
    $('#wilaya').on('change', function() {
        var wilayaId = $(this).val();
        var $commune = $('#commune');
        $commune.html('<option value="0">-- Choisir votre commune --</option>');
        if (wilayaId) {
            floraApi('GET', 'communes/' + wilayaId)
                .done(function(data) {
                    if (data && data.length) {
                        for (var i = 0; i < data.length; i++) {
                            $commune.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
                        }
                    }
                });
        }
        updateShipping();
    });

    $('#commune').on('change', function() {
        updateShipping();
    });

    function updateShipping() {
        var wilayaCode = $('#wilaya').val() || 0;
        var communeId = $('#commune').val() || 0;
        if (wilayaCode) {
            floraApi('POST', 'cart/location', { wilaya_code: parseInt(wilayaCode), commune_id: parseInt(communeId) })
                .done(function(response) {
                    syncShippingMethod(response.cart);
                    renderCheckoutSummary(response.cart);
                });
        }
    }

    function renderCheckoutSummary(cart) {
        var $container = $('#flora-checkout-summary-content');
        if ($container.length === 0) return;

        if (!cart) {
            floraApi('GET', 'cart').done(function(data) { renderCheckoutSummary(data); });
            return;
        }

        if (!cart.items || cart.items.length === 0) {
            $container.html('<p>Aucun article dans le panier.</p>');
            return;
        }

        var html = '<table class="flora-summary-table">';
        for (var i = 0; i < cart.items.length; i++) {
            var item = cart.items[i];
            html += '<tr class="' + (item.is_free ? 'flora-summary-free' : '') + '">';
            html += '<td>' + escapeHtml(item.name);
            if (item.is_free) html += ' <span class="flora-badge-free">GRATUIT</span>';
            html += '</td>';
            html += '<td>x' + item.quantity + '</td>';
            html += '<td>' + (item.is_free ? 'Gratuit' : formatPrice(item.line_total)) + '</td>';
            html += '</tr>';
        }
        html += '</table>';

        if (cart.totals) {
            html += '<table class="flora-summary-totals">';
            html += '<tr><td>Sous-total</td><td>' + formatPrice(cart.totals.subtotal) + '</td></tr>';
            if (parseFloat(cart.totals.discount_total) > 0) {
                html += '<tr class="flora-discount-row"><td>Remises</td><td>- ' + formatPrice(cart.totals.discount_total) + '</td></tr>';
            }
            if (parseFloat(cart.totals.shipping_fee) > 0) {
                html += '<tr><td>' + escapeHtml(getMethodLabel(cart)) + '</td><td>' + formatPrice(cart.totals.shipping_fee) + '</td></tr>';
            } else {
                html += '<tr><td>' + escapeHtml(getMethodLabel(cart)) + '</td><td>Gratuit</td></tr>';
            }
            html += '<tr class="flora-total-row"><td><strong>Total</strong></td><td><strong>' + formatPrice(cart.totals.total) + '</strong></td></tr>';
            html += '</table>';
        }

        html += renderCheckoutPromotions(cart);

        $container.html(html);
    }

    // Checkout form submit
    $('#flora-checkout-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('.flora-place-order');
        var origText = $btn.text();
        $btn.prop('disabled', true).text(i18n.processing);

        var billing = {
            full_name: $form.find('#full_name').val(),
            email: $form.find('#email').val() || '',
            phone: $form.find('#phone').val(),
            address: $form.find('#address').val() || '',
            wilaya_code: parseInt($form.find('#wilaya').val()) || 0,
            commune_id: parseInt($form.find('#commune').val()) || 0,
            shipping_method: $form.find('input[name="shipping_method"]:checked').val() || 'home',
            notes: $form.find('#notes').val()
        };

        floraApi('POST', 'checkout', { billing: billing })
            .done(function(response) {
                if (response.success && response.order) {
                    var orderConfirmUrl = floraShop.pageUrls.orderConfirm;
                    window.location.href = orderConfirmUrl + (orderConfirmUrl.indexOf('?') !== -1 ? '&' : '?') + 'order=' + response.order.order_number;
                }
            })
            .fail(function(xhr) {
                var msg = i18n.error;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showNotification(msg, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).text(origText);
            });
    });

    // ========== DETAIL: ACCORDION + PRICE RECAP ==========
    $(document).on('click', '.flora-acc-header', function() {
        var $item = $(this).closest('.flora-acc-item');
        var $body = $item.find('.flora-acc-body');
        var opened = $item.hasClass('open');
        $item.toggleClass('open', !opened);
        $body.prop('hidden', opened);
        $(this).attr('aria-expanded', opened ? 'false' : 'true');
    });

    function initRecap() {
        var $data = $('#flora-recap-data');
        if ($data.length === 0) return;

        var config;
        try {
            config = JSON.parse($data.text());
        } catch (e) {
            return;
        }

        var unitPrice = parseFloat(config.unit_price) || 0;
        var promotions = config.promotions || [];
        var $recap = $('#flora-recap');
        if ($recap.length === 0) return;

        var $detail = $recap.closest('.flora-product-detail');
        var $qty = $detail.find('.flora-qty-input');
        if ($qty.length === 0) return;

        function renderRecap() {
            var qty = parseInt($qty.val(), 10) || 1;
            var subtotal = qty * unitPrice;
            var promosHtml = '';
            var discount = 0;

            for (var i = 0; i < promotions.length; i++) {
                var p = promotions[i];
                var triggerQty = parseInt(p.trigger_qty, 10) || 1;
                var times = Math.floor(qty / triggerQty);
                if (times <= 0) continue;

                var limit = parseInt(p.limit, 10) || 0;
                if (limit > 0 && times > limit) times = limit;
                if (times <= 0) continue;

                var amount = 0;
                if (p.reward_type === 'percent') {
                    var percent = parseFloat(p.value) || 0;
                    amount = (times * triggerQty * unitPrice) * (percent / 100);
                } else if (p.reward_type === 'amount') {
                    var perSet = parseFloat(p.value) || 0;
                    var maxDiscount = times * triggerQty * unitPrice;
                    amount = Math.min(times * perSet, maxDiscount);
                }

                if (p.is_free) {
                    promosHtml += '<tr><td>' + escapeHtml(p.title) + '</td><td class="flora-promo-amount">' + i18n.added_free + '</td></tr>';
                } else {
                    discount += amount;
                    promosHtml += '<tr><td>' + escapeHtml(p.title) + '</td><td>- ' + formatPrice(amount) + '</td></tr>';
                }
            }

            $recap.find('[data-cell="unit"]').text(formatPrice(unitPrice));
            $recap.find('[data-cell="subtotal"]').text(formatPrice(subtotal));
            $recap.find('[data-cell="promos"]').html(promosHtml);
            $recap.find('[data-cell="total"]').html('<strong>' + formatPrice(Math.max(0, subtotal - discount)) + '</strong>');
        }

        $qty.on('input change', renderRecap);
        $detail.on('click', '.flora-qty-minus, .flora-qty-plus', function() {
            setTimeout(renderRecap, 0);
        });
        renderRecap();
    }

    // ========== HELPERS ==========
    function formatPrice(amount) {
        return parseFloat(amount || 0).toFixed(2) + ' DZD';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    function getCheckoutUrl() {
        return floraShop.pageUrls.checkout;
    }

    function updateCartCount(count) {
        $('.flora-cart-count').text(count).toggle(count > 0);
    }

    // ========== INIT ==========
    injectCartDrawer();
    renderCartPage();
    renderCheckoutSummary();
    initRecap();

    floraApi('GET', 'cart').done(function(data) {
        updateCartCount(data.item_count);
        syncShippingMethod(data);
    });
});
