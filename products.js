/* ---------- PRODUCT DATA (all products from your program) ---------- */
/* Each product: { id, name, price, img, category, sub } */

function needLogin() {
    if (localStorage.getItem('st_session')) return false;
    const page = location.pathname.split('/').pop() || 'products.html';
    location.href = 'login.html?next=' + encodeURIComponent(page);
    return true;
}
/* ---------- UI & state ---------- */
const FREE_SHIP_MIN = 3000;
const shippingFor = (subtotal) => subtotal >= FREE_SHIP_MIN ? 0 : 150;
let currentCategory = 'household';
let currentSubcat = null;

function renderTopCats() {
    $('.cat-box').each(function () {
        $(this).toggleClass(
            'active',
            $(this).data('cat') === currentCategory
        );
    });
}

function renderSubcats() {
    const $area = $('#subcat-area');
    $area.empty();

    const subs = [
        ...new Set(
            PRODUCTS
                .filter(p => p.category === currentCategory)
                .map(p => p.sub)
        )
    ];

    if (!subs.length) return;

    const $wrapper = $('<div>').addClass('subcats');

    $('<button>')
        .addClass('subcat-btn')
        .toggleClass('active', currentSubcat === null)
        .attr('data-sub', '')
        .text('All')
        .appendTo($wrapper);

    $.each(subs, function (_, sub) {
        $('<button>')
            .addClass('subcat-btn')
            .toggleClass('active', currentSubcat === sub)
            .attr('data-sub', sub)
            .text(sub)
            .appendTo($wrapper);
    });

    $area.append($wrapper);
}

function renderProducts() {
    const $grid = $('#product-grid');

    $grid.empty();

    const list = PRODUCTS.filter(function (product) {
        return (
            product.category === currentCategory &&
            (
                currentSubcat === null ||
                product.sub === currentSubcat
            )
        );
    });

    $.each(list, function (_, product) {
        const card = `
            <div class="product-card">
                <div class="img">
                    <img
                        src="${product.img}"
                        alt="${product.name}"
                        onerror="this.style.display='none'"
                    >
                </div>

                <div class="product-name">
                    ${product.name}
                </div>

                <div class="product-price">
                    ₱${product.price.toLocaleString()}
                </div>

                <div style="color:var(--muted);font-size:13px;margin-bottom:8px;">
                    ${product.sub}
                </div>

                <div class="product-actions">
                    <button
                        class="btn-add"
                        data-add="${product.id}"
                    >
                        Add to Cart
                    </button>

                    <button
                        class="btn-buy"
                        data-buy="${product.id}"
                    >
                        Buy Now
                    </button>

                    <button
                        class="btn-view"
                        data-view="${product.id}"
                    >
                        View Details
                    </button>
                </div>
            </div>
        `;

        $grid.append(card);
    });
}

let cart = JSON.parse(localStorage.getItem('st_cart')) || [];

function saveCart() {
    localStorage.setItem(
        'st_cart',
        JSON.stringify(cart)
    );

    updateCartCount();
}

function updateCartCount() {
    const count = cart.reduce(function (total, item) {
        return total + (item.qty || 1);
    }, 0);

    $('#cart-count').text(count);
}

function findProduct(id) {
    return PRODUCTS.find(p => p.id === id);
}

function addToCart(id) {
    if (needLogin()) return;
    const product = findProduct(id);

    if (!product) {
        alert('Product not found');
        return;
    }

    const index = cart.findIndex(
        item => item.id === id
    );

    if (index > -1) {
        cart[index].qty =
            (cart[index].qty || 1) + 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            qty: 1,
            img: product.img
        });
    }

    saveCart();
    renderCart();
}

function buyNow(id) {
    if (needLogin()) return;
    addToCart(id);
    try { sessionStorage.setItem('st_buy', id); } catch (e) {}
    location.href = 'cart.html';
}

function renderCart() {
    const $container = $('#cart-items');

    $container.empty();

    if (!cart.length) {
        $container.html(`
            <p style="text-align:center;color:#777;">
                Your cart is empty.
            </p>
        `);

        $('#cart-total').text('Total: ₱0');

        updateCartCount();

        return;
    }

    let total = 0;

    $.each(cart, function (index, item) {
        total += item.price * item.qty;

        const row = `
            <div class="cart-item">

                <div class="cart-left">

                    <img
                        src="${item.img}"
                        alt="${item.name}"
                        onerror="this.style.display='none'"
                    >

                    <div class="cart-meta">

                        <div style="font-weight:bold;">
                            ${item.name}
                        </div>

                        <div style="color:var(--muted);font-size:13px;">
                            ₱${item.price.toLocaleString()}
                        </div>

                    </div>

                </div>

                <div style="display:flex;align-items:center;gap:12px;">

                    <div class="qty-controls">

                        <button
                            class="qty-minus"
                            data-index="${index}"
                        >
                            -
                        </button>

                        <span style="min-width:24px;text-align:center;">
                            ${item.qty}
                        </span>

                        <button
                            class="qty-plus"
                            data-index="${index}"
                        >
                            +
                        </button>

                    </div>

                    <div style="min-width:100px;text-align:right;">
                        ₱${(
                            item.price * item.qty
                        ).toLocaleString()}
                    </div>

                    <button
                        class="remove-btn"
                        data-remove="${index}"
                    >
                        Remove
                    </button>

                </div>

            </div>
        `;

        $container.append(row);
    });

    $('#cart-total').text(
        'Total: ₱' + total.toLocaleString()
    );

    updateCartCount();
}

function changeQty(index, amount) {
    cart[index].qty =
        Math.max(
            1,
            (cart[index].qty || 1) + amount
        );

    saveCart();
    renderCart();
}

function removeItem(index) {
    if (!confirm('Remove this item?')) return;

    cart.splice(index, 1);

    saveCart();
    renderCart();
}

function clearCart() {
    if (!confirm('Clear entire cart?')) return;

    cart = [];

    saveCart();
    renderCart();
}

function scrollToCart() {
    renderCart();

    const $cart = $('#cart-section');

    if (!$cart.length) return;

    $('html, body')
        .stop(true)
        .animate(
            {
                scrollTop:
                    $cart.offset().top - 20
            },
            500
        );
}

function scrollToTop() {
    $('html, body')
        .stop(true)
        .animate(
            {
                scrollTop: 0
            },
            500
        );
}

function openPayment() {
    if (!cart.length) {
        alert('Cart empty. Add items first.');
        return;
    }

    const $payItems = $('#pay-items');

    $payItems.empty();

    let total = 0;

    $.each(cart, function (_, item) {
        total += item.price * item.qty;

        $payItems.append(`
            <div style="padding:6px 0;">
                ${item.name}
                x${item.qty}
                —
                ₱${(
                    item.price * item.qty
                ).toLocaleString()}
            </div>
        `);
    });

    $('#pay-total').text(
        '₱' + total.toLocaleString()
    );

    $('#payment')
        .stop(true, true)
        .slideDown(400);

    setTimeout(function () {
        $('html, body')
            .stop(true)
            .animate(
                {
                    scrollTop:
                        $('#payment').offset().top - 20
                },
                500
            );
    }, 150);
}

function closePayment() {
    $('#payment')
        .stop(true, true)
        .slideUp(400);
}

function completePayment() {
    const address =
        $('#address').val().trim();

    const method =
        $('input[name="paymethod"]:checked').val();

    if (!address) {
        alert('Please add delivery address.');
        return;
    }

    if (!method) {
        alert('Please choose a payment method.');
        return;
    }

    if (method === 'Card') {
        const name =
            $('#card-name').val().trim();

        const cardNumber =
            $('#card-number')
                .val()
                .replace(/\D/g, '');

        const expiry =
            $('#card-exp').val().trim();

        const cvv =
            $('#card-cvv').val().trim();

        if (
            !name ||
            !cardNumber ||
            !expiry ||
            !cvv
        ) {
            alert('Please fill card details.');
            return;
        }

        if (cardNumber.length < 13) {
            alert('Enter a valid card number.');
            return;
        }
    }

    let subtotal = 0;

    const cartItems = cart.map(function (item) {
        subtotal +=
            item.price * item.qty;

        return {
            name: item.name,
            price: item.price,
            quantity: item.qty
        };
    });

    const shippingFee = shippingFor(subtotal);
    const grandTotal =
        subtotal + shippingFee;

    const orderData = {
        orderNumber:
            'ORD-' +
            Math.floor(
                Math.random() * 900000 +
                100000
            ),

        orderDate:
            new Date().toLocaleString(),

        paymentMethod: method,

        address: address,

        cartItems: cartItems,

        subtotal: subtotal,

        grandTotal: grandTotal
    };

    localStorage.setItem(
        'lastOrder',
        JSON.stringify(orderData)
    );

    cart = [];

    saveCart();

    window.location.href =
        'receipt.html';
}

function showDetails(id) {
    const product =
        PRODUCTS.find(p => p.id === id);

    if (!product) return;

    $('#descContent').html(`
        <img
            src="${product.img}"
            alt="${product.name}"
        >

        <h2>
            ${product.name}
        </h2>

        <p>
            <strong>
                ₱${product.price.toLocaleString()}
            </strong>
        </p>

        <p style="color:#555;">
            ${product.sub}
        </p>

        <div
            style="
                text-align:left;
                margin:10px auto;
                max-width:600px;
            "
        >
            ${product.desc}
        </div>

        <div style="margin-top:15px;">

            <button
                class="btn-add"
                data-add="${product.id}"
            >
                Add to Cart
            </button>

            <button
                class="btn-buy"
                data-buy="${product.id}"
            >
                Buy Now
            </button>

        </div>
    `);

    $('#descOverlay')
        .css('display', 'flex')
        .hide()
        .fadeIn(300);
}

function closeOverlay() {
    $('#descOverlay')
        .stop(true, true)
        .fadeOut(300);
}

function init() {
    renderTopCats();
    renderSubcats();
    renderProducts();
    renderCart();
    updateCartCount();
}

$(document).ready(function () {

    init();

    $('.cat-box').on('click', function () {
        currentCategory =
            $(this).data('cat');

        currentSubcat = null;

        renderTopCats();
        renderSubcats();
        renderProducts();
        scrollToTop();
    });


    $(document).on(
        'click',
        '.subcat-btn',
        function () {

            const sub =
                $(this).attr('data-sub');

            currentSubcat =
                sub === ''
                    ? null
                    : sub;

            renderSubcats();
            renderProducts();
        }
    );


        $(document).on(
        'click',
        '.btn-add',
        function () {

            const id =
                $(this).data('add');

            if (!id) return;
            addToCart(id);
        }
    );


    $(document).on(
        'click',
        '.btn-buy',
        function () {

            const id =
                $(this).data('buy');

            if (!id) return;
            buyNow(id);
        }
    );


    $(document).on(
        'click',
        '.btn-view',
        function () {

            const id =
                $(this).data('view');

            showDetails(id);
        }
    );


    $(document).on(
        'click',
        '.qty-minus',
        function () {

            const index =
                Number(
                    $(this).data('index')
                );

            changeQty(index, -1);
        }
    );


    $(document).on(
        'click',
        '.qty-plus',
        function () {

            const index =
                Number(
                    $(this).data('index')
                );

            changeQty(index, 1);
        }
    );


    $(document).on(
        'click',
        '.remove-btn',
        function () {

            const index =
                Number(
                    $(this).data('remove')
                );

            removeItem(index);
        }
    );


    $('input[name="paymethod"]').on(
        'change',
        function () {

            const method =
                $(
                    'input[name="paymethod"]:checked'
                ).val();

            if (method === 'Card') {
                $('#card-fields')
                    .stop(true, true)
                    .slideDown(300);
            } else {
                $('#card-fields')
                    .stop(true, true)
                    .slideUp(300);
            }
        }
    );


    $('#descOverlay').on(
        'click',
        function (event) {

            if (
                event.target === this
            ) {
                closeOverlay();
            }
        }
    );

});



