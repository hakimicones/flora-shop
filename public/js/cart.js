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
                showNotification(
                    `${i18n.added} <a class="button" href="${floraShop.pageUrls.cart}">Voir mon panier</a> <a class="button" style="margin-left:10px;" href="${window.location.href}">Continuer mes achats</a>`,
                    'success'
                );
                updateCartCount(response.cart.item_count);
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
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            });
    });

    $(document).on('change', '.flora-qty-input-cart', function() {
        var index = $(this).data('index');
        var qty = parseInt($(this).val()) || 1;
        floraApi('POST', 'cart/update', { index: index, quantity: qty })
            .done(function(response) {
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
                renderCartPage(response.cart);
                renderCheckoutSummary(response.cart);
            });
    });

    $(document).on('click', '.flora-clear-cart', function(e) {
        e.preventDefault();
        if (confirm(i18n.confirm)) {
            floraApi('POST', 'cart/clear')
                .done(function(response) {
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
                html += '<tr><td>Transport</td><td>' + formatPrice(cart.totals.shipping_fee) + '</td></tr>';
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
            first_name: $form.find('#first_name').val(),
            last_name: $form.find('#last_name').val(),
            email: $form.find('#email').val(),
            phone: $form.find('#phone').val(),
            address: $form.find('#address').val(),
            wilaya_code: parseInt($form.find('#wilaya').val()) || 0,
            commune_id: parseInt($form.find('#commune').val()) || 0,
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
    renderCartPage();
    renderCheckoutSummary();

    floraApi('GET', 'cart').done(function(data) {
        updateCartCount(data.item_count);
    });
});
