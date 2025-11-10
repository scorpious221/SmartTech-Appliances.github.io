/* ---------- PRODUCT DATA (all products from your program) ---------- */
/* Each product: { id, name, price, img, category, sub } */
const PRODUCTS = [
  // Household -> Security Devices
  {id:'smoke', name:'Smoke Detector', price:2500, img:'images/smokedetector.JPG', category:'household', sub:'Security Devices', desc:`A smoke detector is a safety device that senses smoke and sounds an alarm to warn of a potential fire.
  <br><br><strong>Features:</strong><br>
  • Reliable fire detection<br>
  • Easy ceiling installation<br>
  • Loud 84dB alarm<br>
  • Long battery life (up to 10 years)<br>
  • Operates effectively from 10°C–50°C.`},
  {id:'motion', name:'Motion Sensor', price:5330, img:'images/motionsensor.jpg', category:'household', sub:'Security Devices', desc:`The sensor is suitable for home lighting, indoor and outdoor lighting fixtures. It can be adjusted to be used all day or at night. When someone enters the sensing range of the switch, the sensor detects changes in the infrared spectrum of the human body, and the switch automatically switches on the load. The light turns on when the person arrives, and the light turns off when the person leaves. It is friendly, convenient, safe, and energy-saving.
<br><br><strong>FEATURES:</strong><br>
Stable performance, intelligent light control, delayed shutdown<br>
High sensitivity, 360 °full range detection without blind spots<br>
Ceiling mounted, bottom bracket design, quick wiring for easy installation and disassembly<br>
Fireproof and flame-retardant material: effectively preventing fire hazards, not deformed, not easily oxidized, safe, durable, and durable<br>
Wide application range: can be used for switch control of lighting fixtures in various buildings, such as stairs, corridors, restrooms, school communities, etc.`},
  {id:'doorknob', name:'Smart Doorknob', price:10350, img:'images/smartdoorknob.jpg', category:'household', sub:'Security Devices'},
  {id:'doorbell', name:'Video Doorbell', price:13500, img:'images/vidbell.jpg', category:'household', sub:'Security Devices'},
  {id:'cctv', name:'CCTV Camera', price:8900, img:'images/cctv.jpg', category:'household', sub:'Security Devices'},

  // Household -> Utilities
  {id:'kettle', name:'Electric Kettle', price:1080, img:'images/kettle.jpg', category:'household', sub:'Utilities'},
  {id:'solar', name:'Solar Panel', price:1590, img:'images/solarpanel.jpg', category:'household', sub:'Utilities'},
  {id:'sewing', name:'Sewing Machine', price:38000, img:'images/sewingmachine.jpg', category:'household', sub:'Utilities'},
  {id:'avr', name:'Voltage Regulator', price:18899, img:'images/voltagereg.jpg', category:'household', sub:'Utilities'},
  {id:'generator', name:'Portable Generator', price:10000, img:'images/portablegen.jpg', category:'household', sub:'Utilities'},

  // Household -> Home Office
  {id:'printer', name:'Printer', price:23000, img:'images/printer.jpg', category:'household', sub:'Home Office'},
  {id:'shredder', name:'Paper Shredder', price:16888, img:'images/papershredder.jpg', category:'household', sub:'Home Office'},
  {id:'laminator', name:'Laminator', price:4550, img:'images/laminator.jpg', category:'household', sub:'Home Office'},
  {id:'photocopy', name:'Photocopy Machine', price:28999, img:'images/photocopy.jpg', category:'household', sub:'Home Office'},
  {id:'multiport', name:'Multi-Port Hubs', price:7999, img:'images/multiport.jpg', category:'household', sub:'Home Office'},

  // Entertainment -> Televisions & Displays
  {id:'lcd', name:'LCD Monitor', price:8900, img:'images/lcd.jpg', category:'entertainment', sub:'Televisions & Displays'},
  {id:'smarttv', name:'Smart TV', price:10679, img:'images/stv.jpg', category:'entertainment', sub:'Televisions & Displays'},
  {id:'projector', name:'Projector', price:36000, img:'images/proj.jpg', category:'entertainment', sub:'Televisions & Displays'},
  {id:'cmon', name:'Computer Monitor', price:30000, img:'images/computermonitor.jpg', category:'entertainment', sub:'Televisions & Displays'},
  {id:'oled', name:'OLED TV', price:17988, img:'images/oled.jpg', category:'entertainment', sub:'Televisions & Displays'},

  // Entertainment -> Audio & Music
  {id:'karaoke', name:'Karaoke Machine', price:18199, img:'images/karaokemachine.jpg', category:'entertainment', sub:'Audio & Music'},
  {id:'radio', name:'Portable Radio', price:3651, img:'images/radio.jpg', category:'entertainment', sub:'Audio & Music'},
  {id:'amplifier', name:'Amplifier', price:2950, img:'images/ampli.jpg', category:'entertainment', sub:'Audio & Music'},
  {id:'turntable', name:'Turn Table', price:12999, img:'images/turntable.jpg', category:'entertainment', sub:'Audio & Music'},
  {id:'headphones', name:'Wireless Headphones', price:2599, img:'images/headphones.jpg', category:'entertainment', sub:'Audio & Music'},

  // Entertainment -> Gaming Gadgets
  {id:'ps', name:'PlayStation', price:44150, img:'images/ps5.jpg', category:'entertainment', sub:'Gaming Gadgets'},
  {id:'nsw2', name:'Nintendo Switch 2', price:27693, img:'images/nsw2.jpg', category:'entertainment', sub:'Gaming Gadgets'},
  {id:'rwheel', name:'Racing Wheel Simulator', price:14995, img:'images/rwheel.jpg', category:'entertainment', sub:'Gaming Gadgets'},
  {id:'controller', name:'Gaming Controller', price:1970, img:'images/controller.jpg', category:'entertainment', sub:'Gaming Gadgets'},
  {id:'vr', name:'VR Headset', price:30334, img:'images/vr.jpg', category:'entertainment', sub:'Gaming Gadgets'},

  // Kitchen -> Refrigeration
  {id:'fridge', name:'Refrigerator', price:96000, img:'images/ref.jpg', category:'kitchen', sub:'Refrigeration'},
  {id:'icecream', name:'Ice Cream Maker', price:18995, img:'images/icecream.jpg', category:'kitchen', sub:'Refrigeration'},
  {id:'icemaker', name:'Ice Maker', price:7699, img:'images/icemaker.jpg', category:'kitchen', sub:'Refrigeration'},
  {id:'dispenser', name:'Water Dispenser', price:5898, img:'images/dispenser.jpg', category:'kitchen', sub:'Refrigeration'},
  {id:'winecooler', name:'Wine Cooler', price:6590, img:'images/wine.jpg', category:'kitchen', sub:'Refrigeration'},

  // Kitchen -> Cooking Appliances
  {id:'airfryer', name:'Air Fryer', price:2599, img:'images/afryer.jpg', category:'kitchen', sub:'Cooking Appliances'},
  {id:'ricecook', name:'Rice Cooker', price:2649, img:'images/ricecooker.jpg', category:'kitchen', sub:'Cooking Appliances'},
  {id:'stove', name:'Stove', price:99999, img:'images/stove.jpg', category:'kitchen', sub:'Cooking Appliances'},
  {id:'oven', name:'Oven', price:89995, img:'images/oven.jpg', category:'kitchen', sub:'Cooking Appliances'},
  {id:'toaster', name:'Toaster', price:1475, img:'images/toaster.jpg', category:'kitchen', sub:'Cooking Appliances'},

  // Kitchen -> Food Preparation
  {id:'blender', name:'Blender', price:3085, img:'images/blender.jpg', category:'kitchen', sub:'Food Preparation'},
  {id:'fprocessor', name:'Food Processor', price:285, img:'images/fprocessor.jpg', category:'kitchen', sub:'Food Preparation'},
  {id:'mgrinder', name:'Meat Grinder', price:8790, img:'images/mgrinder.jpg', category:'kitchen', sub:'Food Preparation'},
  {id:'cgrinder', name:'Coffee Grinder', price:8790, img:'images/cgrinder.jpg', category:'kitchen', sub:'Food Preparation'},
  {id:'smixer', name:'Stand Mixer', price:8790, img:'images/mixer.jpg', category:'kitchen', sub:'Food Preparation'},

  // If you later want duplicates intentionally, you can add them here as separate ids.
];

/* ---------- UI & state ---------- */
let currentCategory = 'household';
let currentSubcat = null;

/* initialize top category boxes */
function renderTopCats(){
  const cats = document.querySelectorAll('.cat-box');
  cats.forEach(el=>{
    el.classList.toggle('active', el.dataset.cat === currentCategory);
    el.onclick = ()=>{
      currentCategory = el.dataset.cat;
      currentSubcat = null;
      renderTopCats();
      renderSubcats();
      renderProducts();
      scrollToTop();
    };
  });
}

/* build subcategory buttons from PRODUCTS for current category */
function renderSubcats(){
  const area = document.getElementById('subcat-area');
  area.innerHTML = '';
  const subs = [...new Set(PRODUCTS.filter(p=>p.category===currentCategory).map(p=>p.sub))];
  if(subs.length===0) return;
  const wrapper = document.createElement('div');
  wrapper.className = 'subcats';
  // "All" button
  const allBtn = document.createElement('button');
  allBtn.className = 'subcat-btn' + (currentSubcat===null ? ' active':'');
  allBtn.textContent = 'All';
  allBtn.onclick = ()=>{ currentSubcat = null; renderSubcats(); renderProducts(); };
  wrapper.appendChild(allBtn);
  subs.forEach(s=>{
    const b = document.createElement('button');
    b.className = 'subcat-btn' + (currentSubcat===s ? ' active':'');
    b.textContent = s;
    b.onclick = ()=>{ currentSubcat = s; renderSubcats(); renderProducts(); };
    wrapper.appendChild(b);
  });
  area.appendChild(wrapper);
}

/* render product cards for category & subcategory */
function renderProducts(){
  const grid = document.getElementById('product-grid');
  grid.innerHTML = '';
  const list = PRODUCTS.filter(p=> p.category===currentCategory && (currentSubcat ? p.sub===currentSubcat : true));
  list.forEach(p=>{
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
      <div class="img"><img src="${p.img}" alt="${p.name}" onerror="this.style.display='none'"></div>
      <div class="product-name">${p.name}</div>
      <div class="product-price">₱${p.price.toLocaleString()}</div>
      <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">${p.sub}</div>
<div class="product-actions">
  <button class="btn-add" onclick="addToCart('${p.id}')">Add to Cart</button>
  <button class="btn-buy" onclick="buyNow('${p.id}')">Buy Now</button>
  <button class="btn-view" onclick="showDetails('${p.id}')">View Details</button>
</div>
    `;
    grid.appendChild(card);
  });
}

/* ---------- CART LOGIC ---------- */
/* cart: array of {id, name, price, qty, img} */
let cart = JSON.parse(localStorage.getItem('st_cart')) || [];

function saveCart(){ localStorage.setItem('st_cart', JSON.stringify(cart)); updateCartCount(); }

function updateCartCount(){
  const count = cart.reduce((s,i)=> s + (i.qty||1), 0);
  document.getElementById('cart-count').textContent = count;
}

/* find product by id in PRODUCTS */
function findProduct(id){ return PRODUCTS.find(p=>p.id===id); }

/* addToCart by product id */
function addToCart(id){
  const p = findProduct(id);
  if(!p) return alert('Product not found');
  const idx = cart.findIndex(i=>i.id===id);
  if(idx>-1) cart[idx].qty = (cart[idx].qty||1) + 1;
  else cart.push({ id:p.id, name:p.name, price:p.price, qty:1, img:p.img });
  saveCart(); renderCart();
}

/* buyNow: add then open cart/payment */
function buyNow(id){
  addToCart(id);
  setTimeout(()=>{ scrollToCart(); openPayment(); }, 150);
}

/* render cart items under "Your Cart" */
function renderCart(){
  const container = document.getElementById('cart-items');
  container.innerHTML = '';
  if(cart.length===0){
    container.innerHTML = '<p style="text-align:center;color:#777;">Your cart is empty.</p>';
    document.getElementById('cart-total').textContent = 'Total: ₱0';
    updateCartCount();
    return;
  }
  let total = 0;
  cart.forEach((it, idx)=>{
    total += it.price * it.qty;
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <div class="cart-left">
        <img src="${it.img}" alt="${it.name}" onerror="this.style.display='none'">
        <div class="cart-meta">
          <div style="font-weight:bold;">${it.name}</div>
          <div style="color:var(--muted); font-size:13px;">₱${it.price.toLocaleString()}</div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="qty-controls">
          <button onclick="changeQty(${idx}, -1)">-</button>
          <span id="qty-${idx}" style="min-width:24px; text-align:center;">${it.qty}</span>
          <button onclick="changeQty(${idx}, 1)">+</button>
        </div>
        <div style="min-width:100px; text-align:right;">₱${(it.price*it.qty).toLocaleString()}</div>
        <button class="remove-btn" onclick="removeItem(${idx})">Remove</button>
      </div>
    `;
    container.appendChild(div);
  });
  document.getElementById('cart-total').textContent = 'Total: ₱' + total.toLocaleString();
  updateCartCount();
}

/* change quantity */
function changeQty(index, delta){
  cart[index].qty = Math.max(1, (cart[index].qty||1) + delta);
  saveCart(); renderCart();
}

/* remove item */
function removeItem(index){
  if(!confirm('Remove this item?')) return;
  cart.splice(index,1);
  saveCart(); renderCart();
}

/* clear cart */
function clearCart(){
  if(!confirm('Clear entire cart?')) return;
  cart = []; saveCart(); renderCart();
}

/* scroll to cart */
function scrollToCart(){ document.getElementById('cart-section').scrollIntoView({behavior:'smooth', block:'start'}); renderCart(); }

/* scroll to top */
function scrollToTop(){ window.scrollTo({top:0, behavior:'smooth'}); }

/* ---------- PAYMENT UI ---------- */
function openPayment(){
  if(cart.length===0){ alert('Cart empty. Add items first.'); return; }
  // populate payment items
  const payItems = document.getElementById('pay-items');
  payItems.innerHTML = '';
  let total = 0;
  cart.forEach(it => { total += it.price * it.qty; payItems.innerHTML += `<div style="padding:6px 0;">${it.name} x${it.qty} — ₱${(it.price*it.qty).toLocaleString()}</div>`; });
  document.getElementById('pay-total').textContent = '₱' + total.toLocaleString();
  document.getElementById('payment').style.display = 'block';
  setTimeout(()=> document.getElementById('payment').scrollIntoView({behavior:'smooth', block:'start'}), 80);
}

/* hide payment */
function closePayment(){ document.getElementById('payment').style.display = 'none'; }

/* show card fields when Card is chosen */
document.addEventListener('change', (e)=>{
  if(e.target.name === 'paymethod'){
    const val = document.querySelector('input[name="paymethod"]:checked')?.value;
    document.getElementById('card-fields').style.display = (val==='Card') ? 'block' : 'none';
  }
});

/* complete payment (simulated) */
function completePayment(){
  const address = document.getElementById('address').value.trim();
  const method = document.querySelector('input[name="paymethod"]:checked')?.value;
  if(!address) return alert('Please add delivery address.');
  if(!method) return alert('Please choose a payment method.');
  if(method === 'Card'){
    const cn = document.getElementById('card-number').value.replace(/\s/g,'');
    const nm = document.getElementById('card-name').value.trim();
    const exp = document.getElementById('card-exp').value.trim();
    const cvv = document.getElementById('card-cvv').value.trim();
    if(!nm||!cn||!exp||!cvv) return alert('Please fill card details.');
    if(cn.replace(/\D/g,'').length < 13) return alert('Enter a valid card number.');
  }
  alert('Payment successful!\nMode: ' + method + '\nDelivery to: ' + address);
  // clear cart & payment fields
  cart = []; saveCart(); renderCart();
  document.getElementById('address').value = '';
  document.querySelectorAll('input[name="paymethod"]').forEach(i=>i.checked=false);
  document.getElementById('card-fields').style.display='none';
  document.getElementById('payment').style.display='none';
}

/* ---------- startup ---------- */
function init(){
  renderTopCats();
  renderSubcats();
  renderProducts();
  renderCart();
  updateCartCount();
}

/* hook up top-cat boxes */
document.querySelectorAll('.cat-box').forEach(box=>{
  box.addEventListener('click', ()=>{
    currentCategory = box.dataset.cat;
    currentSubcat = null;
    renderTopCats();
    renderSubcats();
    renderProducts();
    scrollToTop();
  });
});

function showDetails(id) {
  const p = PRODUCTS.find(pr => pr.id === id);
  if (!p) return;

  const desc = p.desc;
  document.getElementById('descContent').innerHTML = `
    <img src="${p.img}" alt="${p.name}">
    <h2>${p.name}</h2>
    <p><strong>₱${p.price.toLocaleString()}</strong></p>
    <p style="color:#555;">${p.sub}</p>
    <div style="text-align:left; margin:10px auto; max-width:600px;">${desc}</div>
    <div style="margin-top:15px;">
      <button class="btn-add" onclick="addToCart('${p.id}')">Add to Cart</button>
      <button class="btn-buy" onclick="buyNow('${p.id}')">Buy Now</button>
    </div>
  `;

  document.getElementById('descOverlay').style.display = 'flex';
}

function closeOverlay() {
  document.getElementById('descOverlay').style.display = 'none';
}

/* Optional: close when clicking outside the content */
window.addEventListener('click', e => {
  const overlay = document.getElementById('descOverlay');
  if (e.target === overlay) closeOverlay();
});
window.addEventListener('load', init);
