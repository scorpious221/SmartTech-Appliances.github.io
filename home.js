/* SmartTech homepage — mga link at Add to Cart para sa main.js. I-load pagkatapos ng main.js */
(function () {
  const $$ = (s) => [...document.querySelectorAll(s)];
  const ITEMS = { 'Air Fryer': ['airfryer', 2599, 'afryer.jpg'], 'CCTV Camera': ['cctv', 8900, 'cctv.jpg'], 'OLED TV': ['oled', 17988, 'oled.jpg'], 'Video Doorbell': ['doorbell', 13500, 'vidbell.jpg'], 'Portable Generator': ['generator', 10000, 'portablegen.jpg'] };
  const CATS = { 'Smart Home': 'household', Kitchen: 'kitchen', Gaming: 'entertainment', Monitors: 'entertainment', Audio: 'entertainment', Security: 'household' };

  $$('.st-tile').forEach((a) => { const c = CATS[(a.querySelector('h3') || {}).textContent]; if (c) a.href = 'products.html?cat=' + c; });
  $$('.st-link, .st-btn-outline, .st-promo .st-btn').forEach((a) => { a.href = 'products.html'; });
  const deal = document.querySelector('.st-deal .st-btn'); if (deal) deal.href = 'products.html?product=fridge';

  document.addEventListener('click', (e) => {
    const b = e.target.closest('.st-cart'); if (!b || b.classList.contains('alt')) return;
    const name = b.closest('.st-card').querySelector('h3').textContent.trim(), m = ITEMS[name]; if (!m) return;
    let cart = []; try { cart = JSON.parse(localStorage.getItem('st_cart')) || []; } catch (x) {}
    const it = cart.find((i) => i.id === m[0]);
    if (it) it.qty = (it.qty || 1) + 1; else cart.push({ id: m[0], name, price: m[1], qty: 1, img: 'images/' + m[2] });
    try { localStorage.setItem('st_cart', JSON.stringify(cart)); } catch (x) {}
    if (window.stCartCount) window.stCartCount();
    b.textContent = 'Added ✓'; setTimeout(() => { b.textContent = 'Add to Cart'; }, 1400);
  });
})();