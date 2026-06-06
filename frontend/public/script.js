const API_BASE = '/backend/php/api';
let allProducts = [];
let selectedCategories = [];
let priceMin = 0;
let priceMax = 5000;

function makeLinkToProduct(p){
  return `product.html?id=${encodeURIComponent(p.id)}`;
}

async function fetchProducts(id){
  try{
    const url = new URL(`${API_BASE}/products.php`, window.location.origin);
    if(id) url.searchParams.set('id', id);
    const res = await fetch(url.href);
    const data = await res.json();
    return data;
  }catch(e){
    console.error(e);
    return null;
  }
}

function formatPrice(value){
  return `R${Number(value||0).toFixed(2)}`;
}

function isWishlisted(productId){
  const wishlist = JSON.parse(localStorage.getItem('wishlist')||'[]');
  return wishlist.includes(productId);
}

function toggleWishlist(productId){
  let wishlist = JSON.parse(localStorage.getItem('wishlist')||'[]');
  if(wishlist.includes(productId)){
    wishlist = wishlist.filter(id => id !== productId);
  } else {
    wishlist.push(productId);
  }
  localStorage.setItem('wishlist', JSON.stringify(wishlist));
  updateWishlistUI();
}

function updateWishlistUI(){
  document.querySelectorAll('[data-wishlist-btn]').forEach(btn => {
    const productId = btn.getAttribute('data-wishlist-btn');
    if(isWishlisted(productId)){
      btn.classList.add('active');
      btn.textContent = '❤️';
    } else {
      btn.classList.remove('active');
      btn.textContent = '🤍';
    }
  });
}

function buildProductCard(p){
  const card = document.createElement('article');
  card.className = 'product-card';
  const wishlisted = isWishlisted(p.id);
  card.innerHTML = `
    <div class="product-image"><span>${p.category || 'Couples'}</span></div>
    <div class="product-body">
      <span class="product-category">${p.category || 'Featured'}</span>
      <h4>${p.name}</h4>
      <p class="product-desc">${p.short_description || p.description || 'Premium couples item for evening plans.'}</p>
      <div class="product-foot">
        <div>
          <div class="product-price">${formatPrice(p.price)}</div>
          <div class="product-meta">${p.age_restricted ? '18+ product' : 'All ages'}</div>
        </div>
      </div>
      <button class="add-cart" data-id="${p.id}" data-name="${encodeURIComponent(p.name)}" data-price="${p.price||0}">Add to cart</button>
    </div>
    <button class="product-wish ${wishlisted?'active':''}" data-wishlist-btn="${p.id}">${wishlisted?'❤️':'🤍'}</button>
  `;
  const wishBtn = card.querySelector('[data-wishlist-btn]');
  wishBtn.addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
  });
  return card;
}

function filterProducts(){
  const filtered = allProducts.filter(p => {
    const priceOk = (p.price || 0) >= priceMin && (p.price || 0) <= priceMax;
    const categoryOk = selectedCategories.length === 0 || selectedCategories.includes(p.category);
    return priceOk && categoryOk;
  });
  renderProducts(filtered);
}

function renderProducts(list){
  const out = document.getElementById('products');
  if(!out) return;
  out.innerHTML = '';
  if(!list || !list.length){
    out.innerHTML = '<p class="message">No products found. Try adjusting your filters.</p>';
    return;
  }
  list.forEach(product => out.appendChild(buildProductCard(product)));
  bindAddToCartButtons();
  updateWishlistUI();
}

function bindAddToCartButtons(){
  document.querySelectorAll('.add-cart').forEach(b => b.addEventListener('click', async e => {
    const id = e.target.getAttribute('data-id');
    const name = decodeURIComponent(e.target.getAttribute('data-name'));
    const price = parseFloat(e.target.getAttribute('data-price')||0);
    const cart = JSON.parse(localStorage.getItem('cart')||'[]');
    const found = cart.find(item => item.id == id);
    if(found) found.qty += 1; else cart.push({id, name, price, qty: 1});
    localStorage.setItem('cart', JSON.stringify(cart));
    try{
      await fetch(`${API_BASE}/cart.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id, name, price, qty:1})});
    }catch(e){ console.warn('Cart sync failed', e); }
    alert('Added to cart');
  }));
}

function setupCategoryFilters(){
  const categories = [...new Set(allProducts.map(p => p.category).filter(Boolean))];
  const filterList = document.getElementById('category-filters');
  if(!filterList) return;
  filterList.innerHTML = '';
  categories.forEach(cat => {
    const label = document.createElement('label');
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.value = cat;
    input.addEventListener('change', (e) => {
      if(e.target.checked){
        if(!selectedCategories.includes(cat)) selectedCategories.push(cat);
      } else {
        selectedCategories = selectedCategories.filter(c => c !== cat);
      }
      filterProducts();
    });
    label.appendChild(input);
    label.appendChild(document.createTextNode(cat));
    filterList.appendChild(label);
  });
}

function setupPriceFilter(){
  const applyBtn = document.getElementById('apply-price');
  if(applyBtn){
    applyBtn.addEventListener('click', () => {
      priceMin = parseFloat(document.getElementById('price-min').value || 0);
      priceMax = parseFloat(document.getElementById('price-max').value || 5000);
      filterProducts();
    });
  }
}

function renderProductDetail(p){
  const out = document.getElementById('product-details');
  if(!out) return;
  out.innerHTML = '';
  const wishlisted = isWishlisted(p.id);
  const el = document.createElement('div');
  el.className = 'product-card';
  el.innerHTML = `
    <div class="product-image"><span>${p.category || 'Couples'}</span></div>
    <div class="product-body">
      <span class="product-category">${p.category || 'Featured'}</span>
      <h2>${p.name}</h2>
      <p class="product-desc">${p.description || p.short_description || 'Detailed product description unavailable.'}</p>
      <div class="product-foot">
        <div>
          <div class="product-price">${formatPrice(p.price)}</div>
          <div class="product-meta">${p.age_restricted ? '18+ product' : 'All ages'}</div>
        </div>
        <button id="add-to-cart-detail">Add to cart</button>
      </div>
      <a href="shop.html">&larr; Back to shop</a>
    </div>
    <button class="product-wish ${wishlisted?'active':''}" data-wishlist-btn="${p.id}" style="position:absolute;top:1rem;right:1rem">${wishlisted?'❤️':'🤍'}</button>
  `;
  out.appendChild(el);
  const wishBtn = el.querySelector('[data-wishlist-btn]');
  wishBtn.addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
    wishBtn.classList.toggle('active');
    wishBtn.textContent = wishBtn.classList.contains('active') ? '❤️' : '🤍';
  });
  if(p.age_restricted){ showAgeGate(); }
}

function showAgeGate(){
  const modal = document.getElementById('age-gate');
  if(!modal) return;
  modal.style.display = 'flex';
}

function hideAgeGate(){
  const modal = document.getElementById('age-gate');
  if(!modal) return;
  modal.style.display = 'none';
}

function getQueryParam(name){
  const params = new URLSearchParams(window.location.search);
  return params.get(name);
}

function applySearchFilter(value){
  const text = (value || '').toLowerCase();
  const filtered = allProducts.filter(p => {
    return [p.name, p.category, p.short_description, p.description].some(field => field && field.toLowerCase().includes(text));
  });
  renderProducts(filtered);
}

document.addEventListener('DOMContentLoaded', async () => {
  if(document.getElementById('products')){
    const list = await fetchProducts();
    allProducts = list || [];
    setupCategoryFilters();
    setupPriceFilter();
    renderProducts(allProducts);
    const searchInput = document.getElementById('search-input');
    if(searchInput){
      searchInput.addEventListener('input', () => applySearchFilter(searchInput.value));
      const q = getQueryParam('q');
      if(q){ searchInput.value = q; applySearchFilter(q); }
    }
  }

  if(document.getElementById('product-details')){
    const id = getQueryParam('id');
    if(!id){
      document.getElementById('product-details').innerText = 'Product ID missing.';
      return;
    }
    const p = await fetchProducts(id);
    if(!p){
      document.getElementById('product-details').innerText = 'Failed to load product.';
      return;
    }
    renderProductDetail(p);
    const addBtn = document.getElementById('add-to-cart-detail');
    if(addBtn){
      addBtn.addEventListener('click', async () => {
        const id = p.id;
        const name = p.name;
        const price = p.price || 0;
        const cart = JSON.parse(localStorage.getItem('cart')||'[]');
        const found = cart.find(item => item.id == id);
        if(found) found.qty += 1; else cart.push({id, name, price, qty: 1});
        localStorage.setItem('cart', JSON.stringify(cart));
        try{
          await fetch(`${API_BASE}/cart.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id, name, price, qty:1})});
        }catch(e){ console.warn('Cart sync failed', e); }
        alert('Added to cart');
      });
    }
  }
});
