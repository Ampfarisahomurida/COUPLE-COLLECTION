(() => {
  const authPage = window.location.pathname.endsWith('/auth.html');
  if(authPage) return;

  document.documentElement.classList.add('auth-pending');

  function authUrl(){
    const returnTo = `${window.location.pathname}${window.location.search}${window.location.hash}`;
    return `auth.html?return=${encodeURIComponent(returnTo)}`;
  }

  function updateNavigation(user){
    document.querySelectorAll('.site-nav').forEach(nav => {
      if(nav.querySelector('[data-account-controls]')) return;
      const controls = document.createElement('span');
      controls.className = 'account-controls';
      controls.dataset.accountControls = 'true';
      controls.innerHTML = user.is_admin
        ? `<a class="owner-portal-link" href="/backend/php/admin/dashboard.php">Owner portal</a><button type="button" class="account-signout">Sign out</button>`
        : `<span class="account-email">${escapeText(user.email)}</span><button type="button" class="account-signout">Sign out</button>`;
      nav.appendChild(controls);
      controls.querySelector('.account-signout').addEventListener('click', async () => {
        const button = controls.querySelector('.account-signout');
        button.disabled = true;
        try{
          const response = await fetch('/backend/php/api/auth/logout.php', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-Token':user.csrf_token},
            body:JSON.stringify({csrf_token:user.csrf_token})
          });
          if(!response.ok){
            button.disabled = false;
            button.textContent = 'Try sign out again';
            return;
          }
          window.location.href = 'auth.html';
        }catch(error){
          button.disabled = false;
          button.textContent = 'Try sign out again';
        }
      });
    });
  }

  async function loadBasketCount(){
    try{
      const response = await fetch('/backend/php/api/cart.php', {credentials:'same-origin'});
      if(!response.ok) return;
      const cart = await response.json();
      if(!Array.isArray(cart)) return;
      const normalized = cart.map(item => ({
        id:item.product_id || item.id,
        name:item.name,
        price:Number(item.price || 0),
        qty:Number(item.qty || 1)
      }));
      localStorage.setItem('cart', JSON.stringify(normalized));
      const count = normalized.reduce((total, item) => total + Math.max(0, item.qty), 0);
      document.querySelectorAll('[data-cart-count]').forEach(element => {
        element.textContent = String(count);
        element.setAttribute('aria-label', `${count} items in basket`);
      });
    }catch(error){
      console.warn('Basket count could not be refreshed.', error);
    }
  }

  function escapeText(value){
    const span = document.createElement('span');
    span.textContent = String(value || '');
    return span.innerHTML;
  }

  window.customerAuthReady = fetch('/backend/php/api/auth/me.php', {credentials:'same-origin'})
    .then(async response => {
      if(!response.ok) throw new Error('Account status could not be checked');
      const user = await response.json();
      if(!user.authenticated){
        window.location.replace(authUrl());
        return false;
      }
      updateNavigation(user);
      await loadBasketCount();
      document.documentElement.classList.remove('auth-pending');
      return true;
    })
    .catch(() => {
      window.location.replace(`auth.html?return=${encodeURIComponent(`${window.location.pathname}${window.location.search}${window.location.hash}`)}&error=session`);
      return false;
    });
})();
