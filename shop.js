/* SmartTech Shop — load AFTER products.js and catalog-store.js, BEFORE search.js */
(function () {
  const DETAILS_PAGE = '';
  const CATNAMES = { household: 'Household', entertainment: 'Entertainment', kitchen: 'Kitchen' };
  const SORTS = { featured: 'Featured', asc: 'Price: low to high', desc: 'Price: high to low' };
  const BADGES = { cctv: 'Bestseller', doorbell: 'Bestseller', airfryer: 'Bestseller', ps: 'Bestseller', nsw2: 'New arrival', oled: 'New arrival' };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let sortMode = 'featured', quiet = false, toastTimer, drawTimer, wish = [];
  try { wish = JSON.parse(localStorage.getItem('st_wishlist')) || []; } catch (e) {}

  const style = document.createElement('style');
  style.textContent = `.stock-line{font-size:12px;font-weight:700;margin:-4px 0 12px}.stock-line.in{color:#1B6B4A}.stock-line.low{color:#9A6700}.stock-line.out{color:#A3321A}.product-card.is-out .img img{opacity:.48}.product-card.is-out .btn-add,.product-card.is-out .btn-buy{opacity:.55;cursor:not-allowed}`;
  document.head.appendChild(style);

  function stockClass(stock) { return Number(stock) <= 0 ? 'out' : Number(stock) <= 9 ? 'low' : 'in'; }
  function stockText(p) {
    if (p.stock <= 0) return 'Out of stock';
    if (p.stock <= 9) return `Only ${p.stock} left in stock`;
    return `${p.stock} in stock`;
  }
  function canAdd(id) {
    const p = findProduct(id);
    if (!p) { alert('Product not found.'); return false; }
    const inCart = cart.find(i => i.id === id);
    const q = inCart ? Number(inCart.qty || 1) : 0;
    if (Number(p.stock) <= 0) { alert(`${p.name} is out of stock.`); return false; }
    if (q >= Number(p.stock)) { alert(`Only ${p.stock} stock available for ${p.name}.`); return false; }
    return true;
  }

  function card(p) {
    const on = wish.includes(p.id), out = Number(p.stock) <= 0;
    const href = DETAILS_PAGE ? DETAILS_PAGE + '?id=' + encodeURIComponent(p.id) : '?product=' + encodeURIComponent(p.id);
    return `<article class="product-card${out ? ' is-out' : ''}" data-id="${esc(p.id)}">
      <div class="img">${BADGES[p.id] ? `<span class="card-badge">${BADGES[p.id]}</span>` : ''}
        <button class="wish-btn" data-wish="${esc(p.id)}" aria-pressed="${on}" aria-label="${on ? 'Remove' : 'Add'} ${esc(p.name)} ${on ? 'from' : 'to'} wishlist"><i class="bi bi-heart${on ? '-fill' : ''}"></i></button>
        <img src="${esc(p.img)}" alt="${esc(p.name)}" loading="lazy" onerror="this.style.display='none'">
        <span class="view-pill" aria-hidden="true">View details <i class="bi bi-arrow-up-right"></i></span></div>
      <div class="card-body-st"><span class="product-sub">${esc(p.sub)}</span>
        <h3 class="product-name"><a class="card-link" href="${href}">${esc(p.name)}</a></h3>
        <div class="price-row"><span class="product-price">₱${p.price.toLocaleString()}</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></div>
        <p class="stock-line ${stockClass(p.stock)}">${esc(stockText(p))}</p>
        <div class="product-actions"><button class="btn-add" data-add="${esc(p.id)}"${out ? ' disabled' : ''}>${out ? 'Out of Stock' : 'Add to Cart'}</button><button class="btn-buy" data-buy="${esc(p.id)}"${out ? ' disabled' : ''}>${out ? 'Unavailable' : 'Buy Now'}</button></div></div></article>`;
  }

  const skeleton = () => Array(8).fill(`<div class="product-card skel" aria-hidden="true"><div class="img"><i class="bi bi-image"></i></div><div class="card-body-st"><span class="sk sk-s"></span><span class="sk sk-l"></span><span class="sk sk-m"></span><span class="sk sk-xs"></span><span class="sk sk-btn"></span><span class="sk sk-btn"></span></div></div>`).join('');

  function draw() {
    let list = PRODUCTS.filter(p => p.category === currentCategory && (currentSubcat === null || p.sub === currentSubcat));
    if (sortMode === 'asc') list = [...list].sort((a, b) => a.price - b.price);
    if (sortMode === 'desc') list = [...list].sort((a, b) => b.price - a.price);
    const cat = CATNAMES[currentCategory];
    $('#result-count').text(list.length + (list.length === 1 ? ' product' : ' products'));
    $('#result-path').text(cat + ' / ' + (currentSubcat || 'All essentials'));
    $('#end-note b').text(`You've seen all ${list.length} products`);
    $('#product-grid').removeAttr('aria-busy').html(list.length ? list.map(card).join('') :
      `<div class="empty"><span class="e-ico"><i class="bi bi-search"></i></span><h3>No products found</h3><p>We couldn't find a match in ${cat}. Try a different filter or clear your filters to see all ${cat.toLowerCase()} essentials.</p><button class="btn-clay" id="reset-filters">Clear search &amp; filters</button></div>`);
  }

  window.renderProducts = function () {
    $('#product-grid').attr('aria-busy', 'true').html(skeleton());
    clearTimeout(drawTimer); drawTimer = setTimeout(draw, 300);
  };

  function toast(p) {
    const it = cart.find(i => i.id === p.id), q = it ? it.qty : 1;
    $('#toast').html(`<span class="t-ico"><i class="bi bi-check2"></i></span><div class="t-txt"><b>Added to your cart</b><small>${esc(p.name)} • ${q} × ₱${p.price.toLocaleString()}</small></div><button type="button" class="t-view" id="toast-cart">View cart</button><button type="button" class="t-x" id="toast-x" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>`).addClass('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(() => $('#toast').removeClass('show'), 4000);
  }

  const _add = window.addToCart, _buy = window.buyNow, _open = window.openPayment, _details = window.showDetails;
  window.addToCart = function (id) {
    if (needLogin() || !canAdd(id)) return false;
    _add(id);
    const p = findProduct(id); if (p && !quiet) toast(p);
    return true;
  };
  window.buyNow = function (id) {
    if (needLogin() || !canAdd(id)) return false;
    quiet = true; _buy(id); quiet = false;
    return true;
  };
  window.showDetails = function (id) {
    _details(id);
    const p = findProduct(id); if (!p) return;
    const box = document.getElementById('descContent');
    if (!box) return;
    const actions = box.querySelector('div:last-child');
    if (actions) {
      actions.insertAdjacentHTML('beforebegin', `<p class="stock-line ${stockClass(p.stock)}" style="margin:12px 0">${esc(stockText(p))}</p>`);
      if (p.stock <= 0) actions.querySelectorAll('.btn-add,.btn-buy').forEach(b => { b.disabled = true; b.textContent = 'Out of Stock'; });
    }
  };
  window.openPayment = function () {
    _open();
    const sub = cart.reduce((t, i) => t + i.price * i.qty, 0), ship = shippingFor(sub);
    $('#pay-ship').text(ship ? '₱' + ship : 'Free');
    $('#pay-grand').text('₱' + (sub + ship).toLocaleString());
  };

  function setSort(v) {
    sortMode = v; $('#sortLabel').text(SORTS[v]);
    $('#sortList li').each(function () { $(this).attr('aria-selected', this.dataset.v === v); });
  }
  function toggleSort(open) { $('#sortList').prop('hidden', !open); $('#sortBtn').attr('aria-expanded', open); }

  $(function () {
    const q = new URLSearchParams(location.search);
    if (CATNAMES[q.get('cat')]) { currentCategory = q.get('cat'); currentSubcat = null; }
    const pid = q.get('product'), found = pid && findProduct(pid);
    if (found) { currentCategory = found.category; currentSubcat = null; }
    const sub = q.get('sub');
    if (sub && PRODUCTS.some(p => p.category === currentCategory && p.sub === sub)) currentSubcat = sub;
    renderTopCats(); renderSubcats(); renderProducts();
    if (found) showDetails(pid);

    $('#sortBtn').on('click', e => { e.stopPropagation(); toggleSort($('#sortList').prop('hidden')); });
    $('#sortList').on('click', 'li', function () { setSort(this.dataset.v); toggleSort(false); renderProducts(); })
      .on('keydown', 'li', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).click(); } });
    $(document).on('click', () => toggleSort(false)).on('keydown', e => { if (e.key === 'Escape') toggleSort(false); });

    $(document).on('click', '.wish-btn', function () {
      const id = $(this).data('wish'), i = wish.indexOf(id);
      i > -1 ? wish.splice(i, 1) : wish.push(id);
      try { localStorage.setItem('st_wishlist', JSON.stringify(wish)); } catch (e) {}
      draw();
    });
    $(document).on('click', '.card-link', function (e) { if (DETAILS_PAGE) return; e.preventDefault(); showDetails($(this).closest('.product-card').data('id')); });
    $(document).on('click', '#reset-filters', function () { currentSubcat = null; setSort('featured'); renderSubcats(); renderProducts(); });
    $(document).on('click', '#toast-cart', function () { location.href = 'cart.html'; });
    $(document).on('click', '#toast-x', () => $('#toast').removeClass('show'));

    const openSearch = () => { const b = document.querySelector('.stq-btn'); if (b) b.click(); };
    $('.st-search input').on('click keydown', e => { if (e.type === 'click' || e.key === 'Enter') openSearch(); });
    $('#card-number').on('input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim(); });
    $('#card-exp').on('input', function () { let v = this.value.replace(/\D/g, '').slice(0, 4); this.value = v.length > 2 ? v.slice(0, 2) + '/' + v.slice(2) : v; });
    $('#card-cvv').on('input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 4); });
  });
})();
