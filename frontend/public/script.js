function makeLinkToProduct(p){
  return `product.html?id=${encodeURIComponent(p.id)}`;
}

async function fetchProducts(id){
  try{
    const url = id ? `../../backend/php/api/products.php?id=${encodeURIComponent(id)}` : '../../backend/php/api/products.php';
    const res = await fetch(url);
    const data = await res.json();
    return data;
  }catch(e){
    console.error(e);
    return null;
  }
}

function renderProducts(list){
  const out = document.getElementById('products');
  if(!out) return;
  out.innerHTML = '';
  list.forEach(p=>{
    const el = document.createElement('div'); el.className='card';
    el.innerHTML = `<h4><a href="${makeLinkToProduct(p)}">${p.name}</a></h4><p>${p.short_description}</p><p class="age-note">Category: ${p.category} ${p.age_restricted? '· 18+' : ''}</p><button class="add-cart" data-id="${p.id}" data-name="${encodeURIComponent(p.name)}" data-price="${p.price||0}">Add to cart</button>`;
    out.appendChild(el);
  })
  document.querySelectorAll('.add-cart').forEach(b=> b.addEventListener('click', async e =>{
    const id = e.target.getAttribute('data-id');
    const name = decodeURIComponent(e.target.getAttribute('data-name'));
    const price = parseFloat(e.target.getAttribute('data-price')||0);
    // update client-side cart
    const cart = JSON.parse(localStorage.getItem('cart')||'[]');
    const found = cart.find(i=>i.id==id);
    if(found) found.qty = found.qty + 1; else cart.push({id:id,name:name,price:price,qty:1});
    localStorage.setItem('cart', JSON.stringify(cart));
    // sync to server
    try{ await fetch('../../backend/php/api/cart.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id,name:name,price:price,qty:1})}); }catch(e){ console.warn('Cart sync failed', e); }
    alert('Added to cart');
  }));
}

function renderProductDetail(p){
  const out = document.getElementById('product-details');
  if(!out) return;
  out.innerHTML = '';
  const el = document.createElement('div'); el.className='card';
  el.innerHTML = `
    <h2>${p.name}</h2>
    <p>${p.description || p.short_description}</p>
    <p><strong>Price:</strong> R${p.price? p.price.toFixed(2) : '0.00'}</p>
    <p class="age-note">Category: ${p.category} ${p.age_restricted? '· 18+' : ''}</p>
    <button id="add-to-cart-detail">Add to cart</button>
    <a href="shop.html">&larr; Back to shop</a>
  `;
  out.appendChild(el);

  if(p.age_restricted){
    showAgeGate();
  }
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

document.addEventListener('DOMContentLoaded', async ()=>{
  // If on a page with #products container (index/shop)
  if(document.getElementById('products')){
    const list = await fetchProducts();
    if(list) renderProducts(list);
    else document.getElementById('products').innerText = 'Failed to load products.';
  }

  // If on a product detail page
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
    // detail add to cart
    const addBtn = document.getElementById('add-to-cart-detail');
    if(addBtn){
      addBtn.addEventListener('click', async ()=>{
        const id = p.id; const name = p.name; const price = p.price || 0;
        const cart = JSON.parse(localStorage.getItem('cart')||'[]');
        const found = cart.find(i=>i.id==id);
        if(found) found.qty = found.qty + 1; else cart.push({id:id,name:name,price:price,qty:1});
        localStorage.setItem('cart', JSON.stringify(cart));
        try{ await fetch('../../backend/php/api/cart.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id,name:name,price:price,qty:1})}); }catch(e){ console.warn('Cart sync failed', e); }
        alert('Added to cart');
      });
    }
  }
});
