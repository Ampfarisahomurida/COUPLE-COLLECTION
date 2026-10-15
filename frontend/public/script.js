const API_BASE = '/backend/php/api';

const FALLBACK_PRODUCTS = [
  {
    id: 'date-night-kit',
    name: 'Date Night Kit',
    category: 'Date Night',
    short_description: 'Everything needed for a memorable evening together.',
    description: 'Everything needed for a memorable evening together.',
    price: 499.99,
    image: 'images/1348f53552fdf085e6f5a9f8c6bf1669.jpg',
    collections: ['Date Night Kits', 'New Arrivals', 'Most Loved']
  },
  {
    id: 'massage-oil-set',
    name: 'Massage Oil Set',
    category: 'Wellness',
    short_description: 'Relaxing aromatherapy oils for couples.',
    description: 'Relaxing aromatherapy oils for couples.',
    price: 249.99,
    image: 'images/491e5580dbe04ce2e66f7f54a55563fa.jpg',
    collections: ['Self-Care Sets', 'Most Loved']
  },
  {
    id: 'luxury-candle-set',
    name: 'Luxury Candle Set',
    category: 'Home',
    short_description: 'Premium scented candles for romantic moments.',
    description: 'Premium scented candles for romantic moments.',
    price: 199.99,
    image: 'images/candle.jpg',
    collections: ['Date Night Kits', 'New Arrivals']
  },
  {
    id: 'chocolate-gift-box',
    name: 'Chocolate Gift Box',
    category: 'Gifts',
    short_description: 'Delicious gourmet chocolates for sharing.',
    description: 'Delicious gourmet chocolates for sharing.',
    price: 299.99,
    image: 'images/chocolate gift box.jpg',
    collections: ['Date Night Kits', 'Most Loved']
  },
  {
    id: 'couples-wellness-box',
    name: 'Couples Wellness Box',
    category: 'Wellness',
    short_description: 'Self-care essentials for relaxation and comfort.',
    description: 'Self-care essentials for relaxation and comfort.',
    price: 599.99,
    image: 'images/Couples Wellness Box.jpg',
    collections: ['Self-Care Sets', 'New Arrivals', 'Most Loved']
  },
  {
    id: 'anniversary-gift-collection',
    name: 'Anniversary Gift Collection',
    category: 'Gifts',
    short_description: 'Elegant gifts designed for special celebrations.',
    description: 'Elegant gifts designed for special celebrations.',
    price: 799.99,
    image: 'images/Anniversary Gift Collection.jpg',
    collections: ['Most Loved', 'New Arrivals']
  },
  {
    id: 'matching-couple-mugs',
    name: 'Matching Couple Mugs',
    category: 'Home',
    short_description: 'Stylish matching mugs for everyday moments.',
    description: 'Stylish matching mugs for everyday moments.',
    price: 179.99,
    image: 'images/Matching Couple Mugs.jpg',
    collections: ['New Arrivals']
  },
  {
    id: 'personalized-photo-frame',
    name: 'Personalized Photo Frame',
    category: 'Gifts',
    short_description: 'Display your favorite memories together.',
    description: 'Display your favorite memories together.',
    price: 349.99,
    image: 'images/Personalized Photo Frame.jpg',
    collections: ['Most Loved']
  }
];

let allProducts = [];
let selectedCategories = [];
let priceMin = 0;
let priceMax = Number.POSITIVE_INFINITY;
let searchText = '';
let productSort = 'featured';

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

function escapeHtml(value){
  return String(value || '').replace(/[&<>"']/g, character => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[character]);
}

function getStoredArray(key){
  try{
    const value = JSON.parse(localStorage.getItem(key) || '[]');
    return Array.isArray(value) ? value : [];
  }catch(e){
    return [];
  }
}

function updateCartCount(){
  const count = getStoredArray('cart').reduce((total, item) => total + Math.max(0, Number(item.qty) || 0), 0);
  document.querySelectorAll('[data-cart-count]').forEach(element => {
    element.textContent = String(count);
    element.setAttribute('aria-label', `${count} items in basket`);
  });
}

window.updateCartCount = updateCartCount;

function isWishlisted(productId){
  return getStoredArray('wishlist').some(id => String(id) === String(productId));
}

function toggleWishlist(productId){
  let wishlist = getStoredArray('wishlist').map(String);
  const normalizedId = String(productId);
  if(wishlist.includes(normalizedId)){
    wishlist = wishlist.filter(id => id !== normalizedId);
  } else {
    wishlist.push(normalizedId);
  }
  localStorage.setItem('wishlist', JSON.stringify(wishlist));
  updateWishlistUI();
}

function updateWishlistUI(){
  document.querySelectorAll('[data-wishlist-btn]').forEach(btn => {
    const productId = btn.getAttribute('data-wishlist-btn');
    const saved = isWishlisted(productId);
    btn.classList.toggle('active', saved);
    btn.textContent = saved ? '♥' : '♡';
    btn.setAttribute('aria-label', saved ? 'Remove from saved items' : 'Save item');
    btn.title = saved ? 'Remove from saved items' : 'Save item';
  });
}

function getReviews(productId){
  return getStoredArray(`reviews:${productId}`);
}

function saveReview(productId, review){
  const reviews = getReviews(productId);
  reviews.unshift(review);
  localStorage.setItem(`reviews:${productId}`, JSON.stringify(reviews.slice(0, 20)));
}

function buildReviewSummary(productId){
  const reviews = getReviews(productId);
  if(!reviews.length){
    return '<p class="review-empty">No reviews yet.</p>';
  }
  const average = reviews.reduce((sum, review) => sum + Number(review.rating || 0), 0) / reviews.length;
  const latest = reviews[0];
  return `
    <p class="review-summary">${average.toFixed(1)} / 5 from ${reviews.length} review${reviews.length === 1 ? '' : 's'}</p>
    <p class="review-latest">Latest: "${escapeHtml(latest.text)}" - ${escapeHtml(latest.name)}</p>
  `;
}

function bindReviewForm(form, productId){
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    const text = String(formData.get('review') || '').trim();
    const name = String(formData.get('reviewer') || '').trim() || 'Customer';
    const rating = Number(formData.get('rating') || 5);

    if(!text){
      form.querySelector('[data-review-message]').textContent = 'Please write a short review first.';
      return;
    }

    saveReview(productId, {
      name,
      rating,
      text,
      created_at: new Date().toISOString()
    });

    form.reset();
    form.querySelector('[data-review-message]').textContent = 'Review added. Thank you.';
    const reviewBox = form.closest('.product-review');
    const summary = reviewBox.querySelector('[data-review-summary]');
    if(summary){
      summary.innerHTML = buildReviewSummary(productId);
    }
  });
}

function getProductCollections(product){
  if(Array.isArray(product.collections) && product.collections.length){
    return product.collections;
  }

  const text = `${product.name || ''} ${product.category || ''}`.toLowerCase();
  const collections = [];
  if(text.includes('date') || text.includes('candle') || text.includes('chocolate')){
    collections.push('Date Night Kits');
  }
  if(text.includes('wellness') || text.includes('massage') || text.includes('oil')){
    collections.push('Self-Care Sets');
  }
  if(text.includes('anniversary') || text.includes('mugs') || text.includes('candle')){
    collections.push('New Arrivals');
  }
  if(text.includes('gift') || text.includes('wellness') || text.includes('date') || text.includes('chocolate')){
    collections.push('Most Loved');
  }
  return collections.length ? collections : ['New Arrivals'];
}

function buildProductCard(p){
  const card = document.createElement('article');
  card.className = 'product-card';
  const stockCount = Number(p.stock);
  const soldOut = Number.isFinite(stockCount) && stockCount <= 0;
  const imageMarkup = p.image
    ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy">`
    : `<span>${escapeHtml(p.category || 'Couples')}</span>`;

  card.innerHTML = `
    <a class="product-image" href="${makeLinkToProduct(p)}" aria-label="View ${escapeHtml(p.name)}">${imageMarkup}<span class="image-quick-view">View details</span></a>
    <div class="product-body">
      <span class="product-category">${escapeHtml(p.category || 'Featured')}</span>
      <h4><a href="${makeLinkToProduct(p)}">${escapeHtml(p.name)}</a></h4>
      <p class="product-desc">${escapeHtml(p.short_description || p.description || 'Premium couples item for evening plans.')}</p>
      <div class="product-foot">
        <div>
          <div class="product-price">${formatPrice(p.price)}</div>
          <div class="product-meta">${soldOut ? 'Currently unavailable' : (Number(p.age_restricted) === 1 ? '18+ product' : 'A thoughtful find')}</div>
        </div>
      </div>
      <div class="product-actions">
        <button class="product-wish ${isWishlisted(p.id) ? 'active' : ''}" data-wishlist-btn="${escapeHtml(p.id)}" aria-label="${isWishlisted(p.id) ? 'Remove from saved items' : 'Save item'}" title="${isWishlisted(p.id) ? 'Remove from saved items' : 'Save item'}">${isWishlisted(p.id) ? '♥' : '♡'}</button>
        <button class="add-cart" data-id="${escapeHtml(p.id)}" data-name="${encodeURIComponent(p.name)}" data-price="${Number(p.price) || 0}" data-age-restricted="${Number(p.age_restricted) === 1 ? '1' : '0'}" ${soldOut ? 'disabled' : ''}>${soldOut ? 'Unavailable' : 'Add to basket'}</button>
      </div>
      <a class="product-details-link" href="${makeLinkToProduct(p)}">View product details <span aria-hidden="true">→</span></a>
    </div>
  `;

  card.querySelector('[data-wishlist-btn]').addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
  });
  const image = card.querySelector('.product-image img');
  if(image){
    image.addEventListener('error', () => {
      image.remove();
      const placeholder = document.createElement('span');
      placeholder.textContent = p.category || 'Couples';
      card.querySelector('.product-image').prepend(placeholder);
    }, {once:true});
  }

  return card;
}

function filterProducts(){
  const filtered = allProducts.filter(p => {
    const priceOk = (Number(p.price) || 0) >= priceMin && (Number(p.price) || 0) <= priceMax;
    const categoryOk = selectedCategories.length === 0 || selectedCategories.includes(p.category);
    const searchOk = !searchText || [p.name, p.category, p.short_description, p.description]
      .some(field => field && String(field).toLowerCase().includes(searchText));
    return priceOk && categoryOk && searchOk;
  });
  const sorted = [...filtered];
  if(productSort === 'price-low') sorted.sort((a, b) => Number(a.price) - Number(b.price));
  if(productSort === 'price-high') sorted.sort((a, b) => Number(b.price) - Number(a.price));
  if(productSort === 'name') sorted.sort((a, b) => String(a.name).localeCompare(String(b.name)));
  renderProducts(sorted);
  const count = document.getElementById('results-count');
  if(count) count.textContent = `${sorted.length} ${sorted.length === 1 ? 'find' : 'finds'} in the collection`;
}

function renderProducts(list){
  const out = document.getElementById('products');
  if(!out) return;
  out.innerHTML = '';
  if(!list || !list.length){
    out.innerHTML = '<p class="message">No products found. Try adjusting your filters.</p>';
    return;
  }

  if(out.dataset.groupedProducts === 'true'){
    const groups = ['Date Night Kits', 'Self-Care Sets', 'New Arrivals', 'Most Loved'];
    groups.forEach(group => {
      const groupProducts = list.filter(product => getProductCollections(product).includes(group));
      if(!groupProducts.length) return;

      const section = document.createElement('section');
      section.className = 'collection-section';
      section.innerHTML = `
        <div class="collection-head">
          <h4>${group}</h4>
        </div>
        <div class="grid"></div>
      `;
      const grid = section.querySelector('.grid');
      groupProducts.forEach(product => grid.appendChild(buildProductCard(product)));
      out.appendChild(section);
    });
  } else {
    list.forEach(product => out.appendChild(buildProductCard(product)));
  }
  bindAddToCartButtons();
  updateWishlistUI();
}

async function addProductToCart(product){
  if(Number(product.age_restricted) === 1){
    let verified = false;
    try{
      const current = await fetch(`${API_BASE}/age_verification.php`);
      if(current.ok){
        const status = await current.json();
        verified = status.verified === true;
      }
    }catch(error){
      verified = false;
    }
    if(!verified){
      if(!window.confirm('This item is for adults 18 and older. Do you confirm that you are at least 18?')) return false;
      try{
        const response = await fetch(`${API_BASE}/age_verification.php`, {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({confirm:true})
        });
        if(!response.ok){
          window.alert('Age confirmation could not be saved. Please try again.');
          return false;
        }
      }catch(error){
        window.alert('Age confirmation could not be saved. Please try again.');
        return false;
      }
    }
  }

  const cart = getStoredArray('cart');
  const found = cart.find(item => item.id == product.id);

  if(found){
    if(Number(found.qty || 0) >= 10){
      window.alert('A maximum of 10 of each product can be added to your basket.');
      return false;
    }
    found.qty = Number(found.qty || 0) + 1;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price: Number(product.price) || 0,
      qty: 1
    });
  }

  try{
    const response = await fetch(`${API_BASE}/cart.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id: product.id, name: product.name, price: Number(product.price) || 0, qty: 1})
    });
    if(!response.ok){
      const result = await response.json();
      if(response.status === 401){
        window.location.href = `auth.html?return=${encodeURIComponent(`${window.location.pathname}${window.location.search}${window.location.hash}`)}`;
      }else{
        window.alert(result.error || 'This item could not be added to your basket.');
      }
      return false;
    }
    const savedCart = await response.json();
    if(!Array.isArray(savedCart)){
      window.alert('Your basket could not be updated. Please try again.');
      return false;
    }
    const normalized = savedCart.map(item => ({
      id: item.product_id || item.id,
      name: item.name,
      price: Number(item.price || 0),
      qty: Number(item.qty || 1)
    }));
    localStorage.setItem('cart', JSON.stringify(normalized));
    updateCartCount();
    return true;
  }catch(error){
    window.alert('Your basket could not be updated. Check your connection and try again.');
    return false;
  }
}

function bindAddToCartButtons(){
  document.querySelectorAll('.add-cart').forEach(button => {
    button.addEventListener('click', async () => {
      button.disabled = true;
      const product = {
        id: button.getAttribute('data-id'),
        name: decodeURIComponent(button.getAttribute('data-name')),
        price: parseFloat(button.getAttribute('data-price') || 0),
        age_restricted: Number(button.getAttribute('data-age-restricted') || 0)
      };

      if(!await addProductToCart(product)){
        button.disabled = false;
        return;
      }
      button.textContent = 'Added';

      setTimeout(() => {
        button.textContent = 'Add to basket';
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
      const minimum = document.getElementById('price-min').value;
      const maximum = document.getElementById('price-max').value;
      const nextMin = minimum === '' ? 0 : Number(minimum);
      const nextMax = maximum === '' ? Number.POSITIVE_INFINITY : Number(maximum);
      if(!Number.isFinite(nextMin) || nextMin < 0 || (maximum !== '' && (!Number.isFinite(nextMax) || nextMax < 0)) || nextMin > nextMax){
        const count = document.getElementById('results-count');
        if(count) count.textContent = 'Enter a valid minimum and maximum price.';
        return;
      }
      priceMin = nextMin;
      priceMax = nextMax;
      filterProducts();
    });
  }
}

function renderProductDetail(p){
  const out = document.getElementById('product-details');
  if(!out) return;

  const soldOut = Number.isFinite(Number(p.stock)) && Number(p.stock) <= 0;
  const imageMarkup = p.image
    ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}">`
    : `<span>${escapeHtml(p.category || 'Couples')}</span>`;

  out.innerHTML = `
    <article class="product-card product-detail-card">
      <div class="product-image">${imageMarkup}</div>
      <div class="product-body">
        <span class="product-category">${escapeHtml(p.category || 'Featured')}</span>
        <h2>${escapeHtml(p.name)}</h2>
        <p class="product-desc">${escapeHtml(p.description || p.short_description || 'Detailed product description unavailable.')}</p>
        <div class="product-foot">
          <div>
            <div class="product-price">${formatPrice(p.price)}</div>
            <div class="product-meta">${soldOut ? 'Currently unavailable' : (Number(p.age_restricted) === 1 ? '18+ product' : 'A thoughtful find')}</div>
          </div>
        </div>
        <div class="product-actions">
          <button class="product-wish ${isWishlisted(p.id) ? 'active' : ''}" data-wishlist-btn="${escapeHtml(p.id)}" aria-label="${isWishlisted(p.id) ? 'Remove from saved items' : 'Save item'}">${isWishlisted(p.id) ? '♥' : '♡'}</button>
          <button id="add-to-cart-detail" ${soldOut ? 'disabled' : ''}>${soldOut ? 'Unavailable' : 'Add to basket'}</button>
        </div>
        <form class="product-review" data-review-form="${p.id}">
          <div data-review-summary>${buildReviewSummary(p.id)}</div>
          <div class="review-fields">
            <input name="reviewer" placeholder="Your name">
            <select name="rating" aria-label="Rating">
              <option value="5">5 stars</option>
              <option value="4">4 stars</option>
              <option value="3">3 stars</option>
              <option value="2">2 stars</option>
              <option value="1">1 star</option>
            </select>
          </div>
          <textarea name="review" rows="3" placeholder="Write a review" required></textarea>
          <button type="submit" class="review-submit">Submit review</button>
          <p class="review-message" data-review-message></p>
        </form>
        <a href="shop.html">Back to shop</a>
      </div>
    </article>
  `;

  out.querySelector('[data-wishlist-btn]').addEventListener('click', (e) => {
    e.preventDefault();
    toggleWishlist(p.id);
  });
  bindReviewForm(out.querySelector('[data-review-form]'), p.id);

  if(p.age_restricted){
    showAgeGate();
  }
}

async function showAgeGate(){
  const modal = document.getElementById('age-gate');
  if(!modal) return;
  try{
    const response = await fetch(`${API_BASE}/age_verification.php`);
    if(response.ok){
      const status = await response.json();
      if(status.verified === true) return;
    }
  }catch(error){
    // Keep the age prompt visible if the verification service is unavailable.
  }
  if(modal) modal.style.display = 'flex';
}

function getQueryParam(name){
  return new URLSearchParams(window.location.search).get(name);
}

function applySearchFilter(value){
  searchText = (value || '').trim().toLowerCase();
  filterProducts();
}

document.addEventListener('DOMContentLoaded', async () => {
  if(window.customerAuthReady && !await window.customerAuthReady) return;
  updateCartCount();
  if(document.getElementById('products')){
    const list = await fetchProducts();
    allProducts = Array.isArray(list) ? list : FALLBACK_PRODUCTS;
    setupCategoryFilters();
    setupPriceFilter();
    filterProducts();
    const sortSelect = document.getElementById('sort-products');
    if(sortSelect){
      sortSelect.addEventListener('change', () => {
        productSort = sortSelect.value;
        filterProducts();
      });
    }

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
        if(!await addProductToCart(product)) return;
        addBtn.textContent = 'Added';
        addBtn.disabled = true;
        setTimeout(() => {
          addBtn.textContent = 'Add to basket';
          addBtn.disabled = false;
        }, 1000);
      });
    }
  }
});
