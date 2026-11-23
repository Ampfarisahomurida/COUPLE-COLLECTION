(() => {
  const form = document.getElementById('customer-auth-form');
  const message = document.getElementById('auth-message');
  const passwordInput = form.elements.password;
  const confirmationField = document.getElementById('password-confirmation-field');
  const submitButton = form.querySelector('button[type="submit"]');
  let mode = 'register';
  let csrfToken = '';
  let sessionReady = false;

  if(window.location.protocol === 'file:'){
    message.textContent = 'This page was opened directly from a file and cannot connect to the account service. Open the website through its PHP server: ';
    const siteLink = document.createElement('a');
    siteLink.href = 'http://127.0.0.1:8000/auth.html';
    siteLink.textContent = 'Open Couple Collection on localhost';
    message.appendChild(siteLink);
    submitButton.disabled = true;
    return;
  }

  function safeReturnPath(){
    const value = new URLSearchParams(window.location.search).get('return') || 'index.html';
    const target = new URL(value, window.location.origin);
    if(target.origin !== window.location.origin || !/^https?:$/.test(target.protocol) || target.pathname === '/auth.html'){
      return 'index.html';
    }
    return `${target.pathname}${target.search}${target.hash}`;
  }

  function setMode(nextMode){
    mode = nextMode;
    const registering = mode === 'register';
    document.querySelectorAll('[data-auth-mode]').forEach(button => {
      const active = button.dataset.authMode === mode;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', String(active));
    });
    document.getElementById('auth-heading').textContent = registering ? 'Create your account' : 'Welcome back';
    document.getElementById('auth-intro').textContent = registering
      ? 'Register to save your basket and shop the collection.'
      : 'Sign in to return to your account and basket.';
    confirmationField.hidden = !registering;
    passwordInput.autocomplete = registering ? 'new-password' : 'current-password';
    passwordInput.minLength = registering ? 10 : 1;
    passwordInput.required = true;
    document.getElementById('password-confirmation').required = registering;
    submitButton.textContent = registering ? 'Create account' : 'Sign in';
    message.textContent = '';
  }

  function setPasswordVisibility(button){
    const input = document.getElementById(button.dataset.passwordToggle);
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Hide' : 'Show';
    button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${button.dataset.passwordToggle === 'password' ? 'password' : 'confirmation password'}`);
    button.setAttribute('aria-pressed', String(visible));
  }

  async function loadSession(){
    try{
      const response = await fetch('/backend/php/api/auth/me.php', {credentials:'same-origin'});
      const user = await response.json();
      if(!response.ok){
        message.textContent = user.error || 'The account service is unavailable right now.';
        return false;
      }
      csrfToken = user.csrf_token || '';
      if(!csrfToken){
        message.textContent = 'The account service could not start securely. Please try again.';
        return false;
      }
      if(user.authenticated){
        window.location.replace(user.is_admin ? '/backend/php/admin/dashboard.php' : safeReturnPath());
        return false;
      }
      sessionReady = true;
      return true;
    }catch{
      message.textContent = 'Could not connect to the account service. Make sure the PHP server is running and open this page using its http:// or https:// address.';
      return false;
    }
  }

  document.querySelectorAll('[data-auth-mode]').forEach(button => {
    button.addEventListener('click', () => setMode(button.dataset.authMode));
  });
  document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => setPasswordVisibility(button));
  });

  if(new URLSearchParams(window.location.search).get('error') === 'session'){
    message.textContent = 'Please sign in to continue.';
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const email = String(new FormData(form).get('email') || '').trim();
    const password = String(passwordInput.value || '');
    if(!sessionReady && !await loadSession()) return;
    if(mode === 'register' && password !== document.getElementById('password-confirmation').value){
      message.textContent = 'Your passwords do not match.';
      return;
    }
    if(!csrfToken){
      message.textContent = 'Please refresh this page and try again.';
      return;
    }

    submitButton.disabled = true;
    message.textContent = '';
    try{
      const response = await fetch(`/backend/php/api/auth/${mode === 'register' ? 'register' : 'login'}.php`, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},
        body:JSON.stringify({email,password})
      });
      const result = await response.json();
      if(!response.ok){
        message.textContent = result.error || 'We could not sign you in.';
        return;
      }
      if(!result.ok){
        message.textContent = 'The account service could not complete your request. Please try again.';
        return;
      }
      window.location.replace(result.is_admin
        ? '/backend/php/admin/dashboard.php'
        : safeReturnPath());
    }catch{
      message.textContent = 'We could not reach the account service. Check your connection and try again.';
    }finally{
      submitButton.disabled = false;
    }
  });

  setMode('register');
  loadSession();
})();
