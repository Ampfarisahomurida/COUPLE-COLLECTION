const API_BASE = '/backend/php/api';

const FALLBACK_PRODUCTS = [
  {
    id: 'date-night-kit',
    name: 'Date Night Kit',
    category: 'Date Night',
    short_description: 'Everything needed for a memorable evening together.',
    description: 'Everything needed for a memorable evening together.',
    price: 499.99,
    image: 'images/1348f53552fdf085e6f5a9f8c6bf1669.jpg'
  },
  {
    id: 'massage-oil-set',
    name: 'Massage Oil Set',
    category: 'Wellness',
    short_description: 'Relaxing aromatherapy oils for couples.',
    description: 'Relaxing aromatherapy oils for couples.',
    price: 249.99,
    image: 'images/491e5580dbe04ce2e66f7f54a55563fa.jpg'
  },
  {
    id: 'luxury-candle-set',
    name: 'Luxury Candle Set',
    category: 'Home',
    short_description: 'Premium scented candles for romantic moments.',
    description: 'Premium scented candles for romantic moments.',
    price: 199.99,
    image: 'images/candle.jpg'
  },
  {
    id: 'chocolate-gift-box',
    name: 'Chocolate Gift Box',
    category: 'Gifts',
    short_description: 'Delicious gourmet chocolates for sharing.',
    description: 'Delicious gourmet chocolates for sharing.',
    price: 299.99,
    image: 'images/chocolate gift box.jpg'
  },
  {
    id: 'couples-wellness-box',
    name: 'Couples Wellness Box',
    category: 'Wellness',
    short_description: 'Self-care essentials for relaxation and comfort.',
    description: 'Self-care essentials for relaxation and comfort.',
    price: 599.99,
    image: 'images/Couples Wellness Box.jpg'
  },
  {
    id: 'anniversary-gift-collection',
    name: 'Anniversary Gift Collection',
    category: 'Gifts',
    short_description: 'Elegant gifts designed for special celebrations.',
    description: 'Elegant gifts designed for special celebrations.',
    price: 799.99,
    image: 'images/Anniversary Gift Collection.jpg'
  },
  {
    id: 'matching-couple-mugs',
    name: 'Matching Couple Mugs',
    category: 'Home',
    short_description: 'Stylish matching mugs for everyday moments.',
    description: 'Stylish matching mugs for everyday moments.',
    price: 179.99,
    image: 'images/Matching Couple Mugs.jpg'
  },
  {
    id: 'personalized-photo-frame',
    name: 'Personalized Photo Frame',
    category: 'Gifts',
    short_description: 'Display your favorite memories together.',
    description: 'Display your favorite memories together.',
    price: 349.99,
    image: 'images/Personalized Photo Frame.jpg'
  }
];

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
    if(!res.ok){
      throw new Error(data.error || 'Product request failed');
    }

    if(id){
      if(Array.isArray(data)){
        return data.find(product => product.id == id) || FALLBACK_PRODUCTS.find(product => product.id == id) || null;
      }
      return data || FALLBACK_PRODUCTS.find(product => product.id == id) || null;
    }

    return Array.isArray(data) && data.length ? data : FALLBACK_PRODUCTS;
  }catch(e){
    console.warn('Product API unavailable, using local products.', e);
    return id ? FALLBACK_PRODUCTS.find(product => product.id == id) || null : FALLBACK_PRODUCTS;
  }
}

function formatPrice(value){
  return `R${Number(value || 0).toFixed(2)}`;
}

function getStoredArray(key){
  try{
    const value = JSON.parse(localStorage.getItem(key) || '[]');
    return Array.isArray(value) ? value : [];
  }catch(e){
    return [];
  }
}

function isWishlisted(productId){
  return getStoredArray('wishlist').includes(productId);
}

function toggleWishlist(productId){
  let wishlist = getStoredArray('wishlist');
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
    btn.classList.toggle('active', isWishlisted(productId));
    btn.textContent = isWishlisted(productId) ? 'Saved' : 'Save';
  });
}

function buildProductCard(p){
  const card = document.createElement('article');
  card.className = 'product-card';
  const imageMarkup = p.image
    ? `<img src="${p.image}" alt="${p.name}">`
    : `<span>${p.category || 'Couples'}</span>`;

  card.innerHTML = `
    <a class="product-image" href="${makeLinkToProduct(p)}">${imageMarkup}</a>
    <div class="product-body">
      <span class="product-category">${p.category || 'Featured'}</span>
      <h4><a href="${makeLinkToProduct(p)}">${p.name}</a></h4>
      <p class="product-desc">${p.short_description || p.description || 'Premium couples item for evening plans.'}</p>
      <div class="product-foot">
        <div>
          <div class="product-price">${formatPrice(p.price)}</div>
          <div class="product-meta">${p.age_restricted ? '18+ product' : 'Ready to order'}</div>
        </div>
      </div>
      <button class="add-cart" data-id="${p.id}" data-name="${encodeURIComponent(p.name)}" data-price="${p.price || 0}">Add to cart</button>
    </div>
    <button class="product-wish ${isWishlisted(p.id) ? 'active' : ''}" data-wishlist-btn="${p.id}">${isWishlisted(p.id) ? 'Saved' : 'Save'}</button>
  `;

  card.querySelector('[data-wishlist-btn]').addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
  });

  return card;
}

function filterProducts(){
  const filtered = allProducts.filter(p => {
    const priceOk = (Number(p.price) || 0) >= priceMin && (Number(p.price) || 0) <= priceMax;
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

async function addProductToCart(product){
  const cart = getStoredArray('cart');
  const found = cart.find(item => item.id == product.id);

  if(found){
    found.qty = Number(found.qty || 0) + 1;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price: Number(product.price) || 0,
      qty: 1
    });
  }

  localStorage.setItem('cart', JSON.stringify(cart));

  try{
    await fetch(`${API_BASE}/cart.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id: product.id, name: product.name, price: Number(product.price) || 0, qty: 1})
    });
  }catch(e){
    console.warn('Cart sync failed; saved locally.', e);
  }
}

function bindAddToCartButtons(){
  document.querySelectorAll('.add-cart').forEach(button => {
    button.addEventListener('click', async () => {
      const product = {
        id: button.getAttribute('data-id'),
        name: decodeURIComponent(button.getAttribute('data-name')),
        price: parseFloat(button.getAttribute('data-price') || 0)
      };

      await addProductToCart(product);
      button.textContent = 'Added';
      button.disabled = true;

      setTimeout(() => {
        button.textContent = 'Add to cart';
        button.disabled = false;
      }, 1000);
    });
  });
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

  const imageMarkup = p.image
    ? `<img src="${p.image}" alt="${p.name}">`
    : `<span>${p.category || 'Couples'}</span>`;

  out.innerHTML = `
    <article class="product-card product-detail-card">
      <div class="product-image">${imageMarkup}</div>
      <div class="product-body">
        <span class="product-category">${p.category || 'Featured'}</span>
        <h2>${p.name}</h2>
        <p class="product-desc">${p.description || p.short_description || 'Detailed product description unavailable.'}</p>
        <div class="product-foot">
          <div>
            <div class="product-price">${formatPrice(p.price)}</div>
            <div class="product-meta">${p.age_restricted ? '18+ product' : 'Ready to order'}</div>
          </div>
        </div>
        <button id="add-to-cart-detail">Add to cart</button>
        <a href="shop.html">Back to shop</a>
      </div>
      <button class="product-wish ${isWishlisted(p.id) ? 'active' : ''}" data-wishlist-btn="${p.id}">${isWishlisted(p.id) ? 'Saved' : 'Save'}</button>
    </article>
  `;

  out.querySelector('[data-wishlist-btn]').addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
  });

  if(p.age_restricted){
    showAgeGate();
  }
}

function showAgeGate(){
  const modal = document.getElementById('age-gate');
  if(modal) modal.style.display = 'flex';
}

function getQueryParam(name){
  return new URLSearchParams(window.location.search).get(name);
}

function applySearchFilter(value){
  const text = (value || '').toLowerCase();
  const filtered = allProducts.filter(p => {
    return [p.name, p.category, p.short_description, p.description].some(field => {
      return field && field.toLowerCase().includes(text);
    });
  });
  renderProducts(filtered);
}

document.addEventListener('DOMContentLoaded', async () => {
  if(document.getElementById('products')){
    const list = await fetchProducts();
    allProducts = Array.isArray(list) ? list : FALLBACK_PRODUCTS;
    setupCategoryFilters();
    setupPriceFilter();
    renderProducts(allProducts);

    const searchInput = document.getElementById('search-input');
    if(searchInput){
      searchInput.addEventListener('input', () => applySearchFilter(searchInput.value));
      const q = getQueryParam('q');
      if(q){
        searchInput.value = q;
        applySearchFilter(q);
      }
    }
  }

  if(document.getElementById('product-details')){
    const id = getQueryParam('id');
    if(!id){
      document.getElementById('product-details').innerText = 'Product ID missing.';
      return;
    }

    const product = await fetchProducts(id);
    if(!product){
      document.getElementById('product-details').innerText = 'Failed to load product.';
      return;
    }

    renderProductDetail(product);
    const addBtn = document.getElementById('add-to-cart-detail');
    if(addBtn){
      addBtn.addEventListener('click', async () => {
        await addProductToCart(product);
        addBtn.textContent = 'Added';
        addBtn.disabled = true;
        setTimeout(() => {
          addBtn.textContent = 'Add to cart';
          addBtn.disabled = false;
        }, 1000);
      });
    }
  }
});
