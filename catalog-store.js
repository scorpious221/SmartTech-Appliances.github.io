/* SmartTech catalog + inventory storage.
   Load AFTER products-data.js and BEFORE scripts that render PRODUCTS. */
(function () {
  const KEY = 'st_catalog';
  if (typeof PRODUCTS === 'undefined') return;

  const clone = v => JSON.parse(JSON.stringify(v));
  const normalize = p => ({
    ...p,
    id: String(p.id || '').trim(),
    name: String(p.name || '').trim(),
    price: Math.max(0, Number(p.price) || 0),
    regularPrice: Math.max(0, Number(p.regularPrice ?? p.regular_price ?? p.price) || 0),
    isSale: Boolean(p.isSale ?? p.is_sale),
    isBestseller: Boolean(p.isBestseller ?? p.is_bestseller),
    averageRating: Math.max(0, Number(p.averageRating ?? p.average_rating) || 0),
    reviewCount: Math.max(0, Math.floor(Number(p.reviewCount ?? p.review_count) || 0)),
    img: String(p.img || '').trim(),
    category: String(p.category || 'household').trim(),
    sub: String(p.sub || 'Other').trim(),
    desc: String(p.desc || '').trim(),
    stock: Math.max(0, Math.floor(Number(p.stock == null ? 20 : p.stock) || 0))
  });

  function readSaved() {
    try {
      const v = JSON.parse(localStorage.getItem(KEY));
      return Array.isArray(v) ? v.map(normalize).filter(p => p.id && p.name) : null;
    } catch (e) {
      return null;
    }
  }

  function sync(list) {
    const clean = list.map(normalize).filter(p => p.id && p.name);
    PRODUCTS.splice(0, PRODUCTS.length, ...clean);
    return clean;
  }

  function save(list) {
    const clean = sync(list);
    localStorage.setItem(KEY, JSON.stringify(clean));
    window.dispatchEvent(new CustomEvent('st:catalog-changed'));
    return clean;
  }

  const saved = readSaved();
  if (saved) sync(saved);
  else save(PRODUCTS.map(p => ({ ...p, stock: p.stock == null ? 20 : p.stock })));

  function all() { return PRODUCTS; }
  function find(id) { return PRODUCTS.find(p => p.id === id) || null; }
  function status(stock) {
    const n = Number(stock) || 0;
    if (n <= 0) return 'Out of Stock';
    if (n <= 9) return 'Low Stock';
    return 'In Stock';
  }
  function add(product) {
    const p = normalize(product);
    if (!p.id || !p.name) throw new Error('Product ID and name are required.');
    if (find(p.id)) throw new Error('Product ID already exists.');
    save([...PRODUCTS, p]);
    return p;
  }
  function update(id, changes) {
    const i = PRODUCTS.findIndex(p => p.id === id);
    if (i < 0) throw new Error('Product not found.');
    const next = PRODUCTS.map((p, x) => x === i ? normalize({ ...p, ...changes, id }) : p);
    save(next);
    return find(id);
  }
  function remove(id) {
    if (!find(id)) return false;
    save(PRODUCTS.filter(p => p.id !== id));
    return true;
  }
  function setStock(id, qty) {
    return update(id, { stock: Math.max(0, Math.floor(Number(qty) || 0)) });
  }
  function adjustStock(id, amount) {
    const p = find(id);
    if (!p) return null;
    return setStock(id, p.stock + Number(amount || 0));
  }
  function canFulfill(items) {
    for (const item of items) {
      const p = find(item.id);
      const q = Math.max(1, Number(item.quantity != null ? item.quantity : item.qty) || 1);
      if (!p) return { ok: false, item, reason: 'Product is no longer available.' };
      if (p.stock < q) return { ok: false, item, product: p, reason: `Only ${p.stock} stock left for ${p.name}.` };
    }
    return { ok: true };
  }
  function deduct(items) {
    const check = canFulfill(items);
    if (!check.ok) return check;
    const next = PRODUCTS.map(p => {
      const item = items.find(i => i.id === p.id);
      if (!item) return p;
      const q = Math.max(1, Number(item.quantity != null ? item.quantity : item.qty) || 1);
      return { ...p, stock: Math.max(0, p.stock - q) };
    });
    save(next);
    return { ok: true };
  }
  function restore(items) {
    const next = PRODUCTS.map(p => {
      const item = items.find(i => i.id === p.id);
      if (!item) return p;
      const q = Math.max(1, Number(item.quantity != null ? item.quantity : item.qty) || 1);
      return { ...p, stock: p.stock + q };
    });
    save(next);
    return { ok: true };
  }
  function resetToOriginal() {
    localStorage.removeItem(KEY);
    location.reload();
  }

  window.STCatalog = { KEY, all, find, status, save, add, update, remove, setStock, adjustStock, canFulfill, deduct, restore, resetToOriginal, clone };

  fetch('php/get_storefront_content.php', { headers: { Accept: 'application/json' } })
    .then(response => {
      if (!response.ok) throw new Error(`Product catalog request failed: ${response.status}`);
      return response.json();
    })
    .then(content => {
      if (!Array.isArray(content.products)) throw new Error('Product catalog response is invalid.');
      save(content.products.map(product => ({
        id: product.id,
        name: product.name,
        price: product.price,
        regularPrice: product.regular_price,
        isSale: product.is_sale,
        isBestseller: product.is_bestseller,
        averageRating: product.average_rating,
        reviewCount: product.review_count,
        img: product.img,
        category: product.category,
        sub: product.sub,
        desc: product.description,
        stock: product.stock
      })));
    })
    .catch(error => console.error('Unable to refresh the product catalog.', error));
})();
