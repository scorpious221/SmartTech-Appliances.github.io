/* SmartTech shared search — isama sa lahat ng page: <script src="search.js"></script> */
(function () {
  // Kumpletong listahan (para sa mga page na walang products.js). Sa products.html, ang live na PRODUCTS ang gagamitin.
  const CAT = { household: 'Household Appliances', entertainment: 'Entertainment Appliances', kitchen: 'Kitchen Appliances' };
  const RAW = [
    ['smoke','Smoke Detector',2500,'smokedetector.JPG','household','Security Devices'],
    ['motion','Motion Sensor',5330,'motionsensor.jpg','household','Security Devices'],
    ['doorknob','Smart Doorknob',10350,'smartdoorknob.jpg','household','Security Devices'],
    ['doorbell','Video Doorbell',13500,'vidbell.jpg','household','Security Devices'],
    ['cctv','CCTV Camera',8900,'cctv.jpg','household','Security Devices'],
    ['kettle','Electric Kettle',1080,'kettle.jpg','household','Utilities'],
    ['solar','Solar Panel',1590,'solarpanel.jpg','household','Utilities'],
    ['sewing','Sewing Machine',38000,'sewingmachine.jpg','household','Utilities'],
    ['avr','Voltage Regulator',18899,'voltagereg.jpg','household','Utilities'],
    ['generator','Portable Generator',10000,'portablegen.jpg','household','Utilities'],
    ['printer','Printer',23000,'printer.jpg','household','Home Office'],
    ['shredder','Paper Shredder',16888,'papershredder.jpg','household','Home Office'],
    ['laminator','Laminator',4550,'laminator.jpg','household','Home Office'],
    ['photocopy','Photocopy Machine',28999,'photocopy.jpg','household','Home Office'],
    ['multiport','Multi-Port Hubs',7999,'multiport.jpg','household','Home Office'],
    ['lcd','LCD Monitor',8900,'lcd.jpg','entertainment','Televisions & Displays'],
    ['smarttv','Smart TV',10679,'stv.jpg','entertainment','Televisions & Displays'],
    ['projector','Projector',36000,'proj.jpg','entertainment','Televisions & Displays'],
    ['cmon','Computer Monitor',30000,'computermonitor.jpg','entertainment','Televisions & Displays'],
    ['oled','OLED TV',17988,'oled.jpg','entertainment','Televisions & Displays'],
    ['karaoke','Karaoke Machine',18199,'karaokemachine.jpg','entertainment','Audio & Music'],
    ['radio','Portable Radio',3651,'radio.jpg','entertainment','Audio & Music'],
    ['amplifier','Amplifier',2950,'ampli.jpg','entertainment','Audio & Music'],
    ['turntable','Turn Table',12999,'turntable.jpg','entertainment','Audio & Music'],
    ['headphones','Wireless Headphones',2599,'headphones.jpg','entertainment','Audio & Music'],
    ['ps','PlayStation',44150,'ps5.jpg','entertainment','Gaming Gadgets'],
    ['nsw2','Nintendo Switch 2',27693,'nsw2.jpg','entertainment','Gaming Gadgets'],
    ['rwheel','Racing Wheel Simulator',14995,'rwheel.jpg','entertainment','Gaming Gadgets'],
    ['controller','Gaming Controller',1970,'controller.jpg','entertainment','Gaming Gadgets'],
    ['vr','VR Headset',30334,'vr.jpg','entertainment','Gaming Gadgets'],
    ['fridge','Refrigerator',96000,'ref.jpg','kitchen','Refrigeration'],
    ['icecream','Ice Cream Maker',18995,'icecream.jpg','kitchen','Refrigeration'],
    ['icemaker','Ice Maker',7699,'icemaker.jpg','kitchen','Refrigeration'],
    ['dispenser','Water Dispenser',5898,'dispenser.jpg','kitchen','Refrigeration'],
    ['winecooler','Wine Cooler',6590,'wine.jpg','kitchen','Refrigeration'],
    ['airfryer','Air Fryer',2599,'afryer.jpg','kitchen','Cooking Appliances'],
    ['ricecook','Rice Cooker',2649,'ricecooker.jpg','kitchen','Cooking Appliances'],
    ['stove','Stove',99999,'stove.jpg','kitchen','Cooking Appliances'],
    ['oven','Oven',89995,'oven.jpg','kitchen','Cooking Appliances'],
    ['toaster','Toaster',1475,'toaster.jpg','kitchen','Cooking Appliances'],
    ['blender','Blender',3085,'blender.jpg','kitchen','Food Preparation'],
    ['fprocessor','Food Processor',285,'fprocessor.jpg','kitchen','Food Preparation'],
    ['mgrinder','Meat Grinder',8790,'mgrinder.jpg','kitchen','Food Preparation'],
    ['cgrinder','Coffee Grinder',8790,'cgrinder.jpg','kitchen','Food Preparation'],
    ['smixer','Stand Mixer',8790,'mixer.jpg','kitchen','Food Preparation']
  ];
  const FALLBACK = RAW.map(r => ({ id: r[0], name: r[1], price: r[2], img: 'images/' + r[3], category: r[4], sub: r[5] }));
  const PRODUCTS_PAGE = 'products.html';
  const onProductsPage = () => typeof PRODUCTS !== 'undefined' && typeof showDetails === 'function' && typeof renderProducts === 'function';
  const catalog = () => (typeof PRODUCTS !== 'undefined' ? PRODUCTS : FALLBACK);

  const css = `
  .stq-btn{background:none;border:0;color:inherit;padding:0;font-size:1.05rem;line-height:1;cursor:pointer;opacity:.9;display:inline-flex;align-items:center}
  .stq-btn:hover{opacity:1}
  .stq-btn svg{width:1em;height:1em}
  .stq-float{position:fixed;top:calc(16px + env(safe-area-inset-top,0px));right:5%;z-index:1500;color:#fff;background:#101a33;border-radius:50%;width:38px;height:38px;justify-content:center}
  .stq-ov{position:fixed;inset:0;z-index:3000;background:rgba(10,14,26,.6);display:none;align-items:flex-start;justify-content:center;padding:10vh 16px 16px}
  .stq-ov.open{display:flex}
  .stq-box{background:#fff;border-radius:14px;width:100%;max-width:540px;padding:16px;box-shadow:0 20px 50px rgba(0,0,0,.3);font-family:"Roboto",system-ui,sans-serif;color:#1b2033;max-height:78vh;overflow:auto}
  .stq-in{width:100%;font-size:1.1rem;padding:12px 14px;border:1px solid #d5d8e0;border-radius:10px;margin-bottom:8px;outline:none}
  .stq-in:focus{border-color:#c88f2a}
  .stq-hit{display:flex;gap:12px;align-items:center;padding:8px 10px;border-radius:10px;width:100%;border:0;background:none;text-align:left;cursor:pointer;font:inherit;color:inherit}
  .stq-hit:hover,.stq-hit:focus{background:#f7f5f0;outline:none}
  .stq-hit img{width:46px;height:46px;object-fit:contain;background:#f1f2f6;border-radius:8px;padding:4px;flex-shrink:0}
  .stq-hit .stq-t{flex:1;min-width:0}
  .stq-hit small{display:block;color:#6b7280}
  .stq-hit .stq-p{font-weight:700;color:#c88f2a;white-space:nowrap}
  .stq-none{text-align:center;color:#6b7280;margin:16px 0}`;
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  const overlay = document.createElement('div');
  overlay.className = 'stq-ov';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-label', 'Search products');
  overlay.innerHTML = '<div class="stq-box"><input class="stq-in" type="search" placeholder="Search products (e.g. air fryer, kitchen, TV)" autocomplete="off"><div class="stq-res"></div></div>';
  document.body.appendChild(overlay);
  const input = overlay.querySelector('.stq-in');
  const res = overlay.querySelector('.stq-res');
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function render() {
    const tokens = input.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
    const list = catalog().filter(p => {
      const hay = (p.name + ' ' + p.sub + ' ' + p.category + ' ' + (CAT[p.category] || '')).toLowerCase();
      return tokens.every(t => hay.includes(t));
    });
    res.innerHTML = list.length
      ? list.map(p => `<button class="stq-hit" data-id="${esc(p.id)}"><img src="${esc(p.img)}" alt="" onerror="this.style.visibility='hidden'"><span class="stq-t"><strong>${esc(p.name)}</strong><small>${esc(CAT[p.category] || p.category)} · ${esc(p.sub)}</small></span><span class="stq-p">₱${Number(p.price).toLocaleString()}</span></button>`).join('')
      : '<p class="stq-none">No matches. Try another keyword.</p>';
  }
  function open() { overlay.classList.add('open'); input.value = ''; render(); setTimeout(() => input.focus(), 30); }
  function close() { overlay.classList.remove('open'); }

  function showProduct(p) {
    currentCategory = p.category; currentSubcat = p.sub;
    renderTopCats(); renderSubcats(); renderProducts();
    showDetails(p.id);
  }
  function choose(id) {
    const p = catalog().find(x => x.id === id);
    if (!p) return;
    close();
    if (onProductsPage()) showProduct(p);
    else location.href = PRODUCTS_PAGE + '?product=' + encodeURIComponent(p.id);
  }

  input.addEventListener('input', render);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') { const f = res.querySelector('.stq-hit'); if (f) choose(f.dataset.id); } });
  res.addEventListener('click', e => { const b = e.target.closest('.stq-hit'); if (b) choose(b.dataset.id); });
  overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

  // Kapag galing sa ibang page (products.html?product=id) buksan ang product details
  window.addEventListener('load', () => {
    const id = new URLSearchParams(location.search).get('product');
    if (!id || !onProductsPage()) return;
    const p = PRODUCTS.find(x => x.id === id);
    if (p) showProduct(p);
  });

  // Search button sa navbar
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'stq-btn';
  btn.setAttribute('aria-label', 'Search');
  btn.innerHTML = '<svg viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M416 208c0 45.9-14.9 88.3-40 122.7l126.6 126.7c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0s208 93.1 208 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>';
  btn.addEventListener('click', open);
  const icons = document.querySelector('.st-icons');
  if (icons) icons.insertBefore(btn, icons.firstChild);
  else { btn.classList.add('stq-float'); document.body.appendChild(btn); }
})();