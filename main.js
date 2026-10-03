/* SmartTech — data-driven rendering, tabs, countdown, newsletter validation */
const IMG = 'images/';
const $ = (s) => document.querySelector(s);
const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

const shop = (cat, sub) => 'products.html' + (cat ? '?cat=' + cat : '') + (sub ? '&sub=' + encodeURIComponent(sub) : '');
const NAV = [['Home','homelandingpage.html',1],['Household',shop('household')],['Entertainment',shop('entertainment')],['Kitchen',shop('kitchen')]];
const TRUST = [['truck','Free Delivery','Orders over ₱3,000','Over ₱3,000'],['shield-check','1-Year Warranty','Official local warranty','Official local warranty'],['credit-card','Secure Payment','GCash, Maya, cards, COD','GCash, Maya, COD'],['headset','24/7 Support','Real people, always ready','Always ready']];
const REVIEWS = [['Delivery to Cebu was quicker than expected, and the camera setup was genuinely easy.','Mika R.','Cebu City'],['The team helped me pick the right aircon size and installed it the next day. No surprises.','Paolo D.','Quezon City'],['My gaming monitor arrived well packed and exactly on time. SmartTech is now my go-to.','Andre S.','Davao City']];
var drawProg;
const TILES = [
 ['house','Smart Home','Lighting, hubs & automation','wide tall','SmartHomeCategory.png',shop('household')],
 ['cup-hot','Kitchen','Cook faster, live better','tall','KitchenCategory.png',shop('kitchen')],
 ['controller','Gaming','Level up your setup','tall','GamingCategory.png',shop('entertainment','Gaming Gadgets')],
 ['display','Monitors','See every detail','','MonitorsCategory.png',shop('entertainment','Televisions & Displays')],
 ['speaker','Audio','Sound worth feeling','','AudioCategory.png',shop('entertainment','Audio & Music')],
 ['camera-video','Security','Protect what matters','wide','SecurityCategory.png',shop('household','Security Devices')]];
const FEATURED = [
{id:'airfryer',s:4.9,c:328,o:2999,b:'Best seller',t:['best']},
{id:'cctv',s:4.8,c:181,o:9900,b:'New',t:['new']},
{id:'oled',s:4.7,c:96,o:21988,b:'18% off',t:['sale']},
{id:'doorbell',s:4.8,c:244,o:15500,b:'Hot',t:['best','sale']},
{id:'generator',s:4.6,c:137,o:12000,b:'New',t:['new']}
];
const CARDS = FEATURED.map(f => { const b = PRODUCTS.find(p => p.id === f.id); return { ...f, n: b.name, p: b.price, i: b.img.replace('images/', '') }; });
const TABS = [['all','Best Sellers'],['new','New'],['sale','On Sale']];
const BUNDLES = [['Smart Kitchen Set','Air fryer · Rice cooker · Blender',7499,8333,'afryer.jpg',shop('kitchen','Cooking Appliances')],['Gamer Setup','Computer monitor · Controller · Headphones',31990,34569,'computermonitor.jpg',shop('entertainment','Gaming Gadgets')],['Security Kit','CCTV camera · Video doorbell · Smoke detector',22900,24900,'cctv.jpg',shop('household','Security Devices')]];
const BRANDS = ['SAMSUNG','LG','SONY','XIAOMI','JBL','DYSON','TP-Link'];
const SERVICES = [['wrench','Home Service','Professional installation, repair, and maintenance right at your doorstep.','about.html#contact'],['patch-check','Warranty','We offer product warranty and customer support for replacements or repairs.','help.html#returns'],['shield-check','Secure Checkout','Pay your way with cards, e-wallets, or Cash on Delivery, safe and hassle-free.','help.html#payment'],['arrow-counterclockwise','Easy Returns','Simple 30-day returns online or at any SmartTech store.','help.html#returns']];
const PROMOS = [['Ber Months Sale: Up to 50% OFF','Start the season early with savings across appliances, TVs, and smart-home essentials.','Shop the Sale','Promo_image.png','Seasonal savings across appliances, TVs, and smart-home essentials.','products.html'],['Free Delivery on Appliances','Get free delivery on orders over ₱3,000, anywhere in the Philippines.','Shop Appliances','Delivery-Promo.png','Free delivery on orders over ₱3,000, anywhere in the Philippines.',shop('kitchen')]];
const FOOT = [['Shop',[['Smart Home',shop('household')],['Kitchen',shop('kitchen')],['Gaming',shop('entertainment','Gaming Gadgets')],['Monitors',shop('entertainment','Televisions & Displays')],['Audio',shop('entertainment','Audio & Music')]]],['Help',[['Delivery & Pickup','help.html#delivery'],['Returns','help.html#returns'],['Warranty','help.html#returns'],['Track Order','track.html'],['Contact Us','about.html#contact']]],['About',[['Our Story','about.html'],['SmartTech Care','help.html'],['Careers','about.html'],['Stores','about.html#contact'],['Newsroom','about.html']]]];

const peso = (n) => '₱' + n.toLocaleString('en-PH');
const set = (sel, html) => { const el = $(sel); if (el) el.innerHTML = html; };

set('#navList', NAV.map(([l,h,a]) => `<li class="nav-item"><a class="nav-link${a?' active':''}" href="${h}"${a?' aria-current="page"':''}>${l}</a></li>`).join(''));
set('#trustList', TRUST.map(([i,t,d,sh]) => `<li class="col-6 col-lg-3"><span class="st-icon-box"><i class="bi bi-${i}" aria-hidden="true"></i></span><div><b>${t}</b><small><span class="d-none d-lg-inline">${d}</span><span class="d-lg-none">${sh}</span></small></div></li>`).join(''));
set('#bento', TILES.map(([i,t,d,c,img,h]) => `<a class="st-tile ${c}" href="${h}"><img src="${IMG}${img}" alt="" loading="lazy"><span class="st-icon-box"><i class="bi bi-${i}" aria-hidden="true"></i></span><span><h3>${t}</h3><small>${d}</small></span></a>`).join(''));
set('#bundles', BUNDLES.map(([t,d,p,o,img,h]) => `<div class="col-md-6 col-lg-4"><article class="st-bundle"><div class="st-media"><img src="${IMG}${img}" alt="${esc(t)}" loading="lazy"><span class="st-badge">Save ${peso(o-p)}</span></div><div class="st-body"><h3>${t}</h3><p>${d}</p><div class="st-price"><strong>${peso(p)}</strong><s class="st-old">${peso(o)}</s></div><a href="${h}" class="btn st-btn st-btn-outline">View Bundle</a></div></article></div>`).join(''));
set('#brands', BRANDS.map((b) => `<li>${b}</li>`).join(''));
set('#services', SERVICES.map(([i,t,d,h]) => `<div class="col-md-6 col-lg-3"><article class="st-svc"><span class="st-icon-box"><i class="bi bi-${i}" aria-hidden="true"></i></span><h3>${t}</h3><p>${d}</p><a href="${h}">Learn more <i class="bi bi-chevron-right" aria-hidden="true"></i></a></article></div>`).join(''));
set('#promos', PROMOS.map(([t,d,b,img,sh,h]) => `<div class="col-lg-6"><article class="st-promo"><img src="${IMG}${img}" alt="" loading="lazy"><div><h3>${t}</h3><p><span class="d-none d-lg-inline">${d}</span><span class="d-lg-none">${sh}</span></p></div><a href="${h}" class="btn st-btn st-btn-accent align-self-start">${b}</a></article></div>`).join(''));
set('#footCols', FOOT.map(([t,l]) => `<div class="col-4"><h3>${t}</h3><ul>${l.map(([x,h]) => `<li><a href="${h}">${x}</a></li>`).join('')}</ul></div>`).join(''));

/* Products + tabs */
let wish = []; try { wish = JSON.parse(localStorage.getItem('st_wishlist')) || []; } catch (e) {}
function card(p) {
  const on = wish.includes(p.id), href = 'products.html?product=' + p.id;
  return `<article class="st-card${p.out?' out':''}"><div class="st-media"><a href="${href}" tabindex="-1" aria-hidden="true"><img src="${IMG}${p.i}" alt="${esc(p.n)}" loading="lazy"></a><div class="top"><span class="st-badge${p.out?' dark':''}">${p.b}</span><button class="st-wish" data-id="${p.id}" aria-pressed="${on}" aria-label="Add ${esc(p.n)} to wishlist"><i class="bi bi-heart${on?'-fill':''}"></i></button></div></div>
  <div class="st-body"><h3><a href="${href}" style="text-decoration:none">${esc(p.n)}</a></h3><div class="st-rating"><span class="st-stars" aria-hidden="true">★★★★★</span><b>${p.s}</b><span>(${p.c})</span><span class="visually-hidden">Rated ${p.s} out of 5 from ${p.c} reviews</span></div>
  ${p.low?`<p class="st-low">Only ${p.low} left — order soon</p>`:''}<div class="st-price"><strong>${peso(p.p)}</strong><s class="st-old">${peso(p.o)}</s></div><button class="st-cart${p.out?' alt':''}" data-id="${p.id}"${p.out?' data-out="1"':''}>${p.out?'Notify Me':'Add to Cart'}</button></div></article>`;
}
function renderProducts(tab) {
  const list = tab === 'all' ? CARDS : CARDS.filter((p) => p.t.includes(tab));
  set('#rail', list.map(card).join(''));
  if (drawProg) { $('#rail').scrollLeft = 0; drawProg(); }
}
set('#tabs', TABS.map(([k,l],i) => `<button class="st-tab" role="tab" id="tab-${k}" data-tab="${k}" aria-selected="${i===0}" aria-controls="rail" tabindex="${i?-1:0}">${l}</button>`).join(''));
$('#tabs').addEventListener('click', (e) => {
  const b = e.target.closest('.st-tab'); if (!b) return;
  document.querySelectorAll('.st-tab').forEach((t) => { const on = t === b; t.setAttribute('aria-selected', on); t.tabIndex = on ? 0 : -1; });
  renderProducts(b.dataset.tab);
});
function cartCount() {
  let c = []; try { c = JSON.parse(localStorage.getItem('st_cart')) || []; } catch (e) {}
  set('#cart-count', c.reduce((t, i) => t + (i.qty || 1), 0));
}
function cartAdd(id) {
  const p = CARDS.find(x => x.id === id); if (!p) return;
  let c = []; try { c = JSON.parse(localStorage.getItem('st_cart')) || []; } catch (e) {}
  const it = c.find(x => x.id === id);
  it ? it.qty = (it.qty || 1) + 1 : c.push({ id, name: p.n, price: p.p, qty: 1, img: IMG + p.i });
  try { localStorage.setItem('st_cart', JSON.stringify(c)); } catch (e) {}
  cartCount();
}
$('#rail').addEventListener('click', (e) => {
  const w = e.target.closest('.st-wish');
  if (w) {
    const id = w.dataset.id, i = wish.indexOf(id);
    i > -1 ? wish.splice(i, 1) : wish.push(id);
    try { localStorage.setItem('st_wishlist', JSON.stringify(wish)); } catch (e) {}
    const on = wish.includes(id);
    w.setAttribute('aria-pressed', on); w.innerHTML = `<i class="bi bi-heart${on?'-fill':''}"></i>`;
    return;
  }
  const b = e.target.closest('.st-cart');
  if (b && !b.dataset.out)
    if (!localStorage.getItem('st_session')) { location.href = 'login.html?next=homelandingpage.html'; return; }
   { cartAdd(b.dataset.id); b.textContent = 'Added ✓'; setTimeout(() => b.textContent = 'Add to Cart', 1200); }
});
cartCount();
$('#railNext').addEventListener('click', () => $('#rail').scrollBy({ left: 306, behavior: 'smooth' }));
renderProducts('all');

/* Search: buksan ang search.js overlay */
document.querySelector('.st-search input')?.addEventListener('click', () => document.querySelector('.stq-btn')?.click());

/* Countdown (ends 3 days from first load; replace with a real end date) */
const end = Date.now() + (2*864e5 + 14*36e5 + 36*6e4 + 8e3);
function tick() {
  let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
  const u = [['Days',86400],['Hours',3600],['Mins',60],['Secs',1]].map(([l,d]) => { const v = Math.floor(s / d); s %= d; return `<div class="st-cd"><b>${String(v).padStart(2,'0')}</b><small>${l}</small></div>`; });
  set('#countdown', u.join(''));
}
tick(); setInterval(tick, 1000);

/* Newsletter validation */
const form = $('#nlForm'), email = $('#nlEmail'), msg = $('#nlMsg');
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const show = (t, ok) => { msg.textContent = t; msg.className = 'st-msg ' + (ok ? 'ok' : 'err'); email.setAttribute('aria-invalid', String(!ok)); };
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const v = email.value.trim();
  if (!v) return show('Enter your email address.', false);
  if (!EMAIL_RE.test(v)) return show('Enter a valid email, like name@example.com.', false);
  try {
    // Point this at your backend endpoint that inserts into newsletter_subscribers
    const r = await fetch('/api/subscribe', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email: v, source: 'homepage' }) });
    if (!r.ok) throw new Error();
    show('You’re in. Check your inbox for your 10% code.', true); form.reset();
  } catch { show('We couldn’t sign you up right now. Try again in a moment.', false); }
});

/* Reviews */
set('#reviews', REVIEWS.map(([q,n,c]) => `<div class="col-lg-4"><article class="st-review"><p class="st-rating"><span class="st-stars" aria-hidden="true">★★★★★</span><b>5.0</b><span class="visually-hidden">Rated 5 out of 5</span></p><blockquote>“${esc(q)}”</blockquote><footer><span class="st-avatar" aria-hidden="true">${n[0]}</span><div><b>${n}</b><small>${c} · Verified buyer</small></div></footer></article></div>`).join(''));

/* Mobile rail progress dots */
function progress(rail, prog) {
  const draw = () => {
    const n = rail.children.length, i = n ? Math.min(n - 1, Math.round(rail.scrollLeft / (rail.scrollWidth / n))) : 0;
    prog.innerHTML = Array.from({ length: n }, (_, k) => `<span${k === i ? ' class="on"' : ''}></span>`).join('');
  };
  rail.addEventListener('scroll', () => requestAnimationFrame(draw), { passive: true });
  draw(); return draw;
}
drawProg = progress($('#rail'), $('#railProg'));