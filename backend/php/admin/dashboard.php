<?php
require_once __DIR__ . '/../session.php';
if(empty($_SESSION['is_admin'])){
	// Simple client-side login form
	?>
	<!doctype html>
	<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Login</title>
	<style>body{font-family:system-ui,sans-serif;background:#fbf8f6;color:#2b2022;display:grid;place-items:center;min-height:100vh;margin:0}.admin-login{background:#fff;padding:2rem;border-radius:20px;box-shadow:0 16px 42px #30192014;width:min(90vw,360px)}label{display:grid;gap:.4rem;margin:1rem 0}input,button{font:inherit;padding:.75rem;border:1px solid #eadfdd;border-radius:10px}button{background:#25151a;color:#fff;cursor:pointer;width:100%}#msg{color:#a95060;margin-top:1rem}</style>
	</head><body><main class="admin-login">
	<h2>Admin Login</h2>
	<form id="login">
		<label>Email <input type="email" name="email" autocomplete="username" required></label>
		<label>Password <input type="password" name="password" autocomplete="current-password" required></label>
		<button>Login</button>
	</form>
	<div id="msg"></div>
	<script>
	document.getElementById('login').addEventListener('submit', async function(e){
		e.preventDefault();
		const fd = new FormData(e.target);
		const payload = {email: fd.get('email'), password: fd.get('password')};
		const res = await fetch('../api/admin/login.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
		const j = await res.json();
		if(res.ok) location.reload(); else document.getElementById('msg').innerText = j.error || 'Login failed';
	});
	</script>
	</main></body></html>
	<?php
	exit;
}

// Admin UI
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Dashboard</title>
<style>
*{box-sizing:border-box}body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;color:#2b2022;background:#fbf8f6;padding:clamp(1rem,4vw,3rem);margin:0}
body>h2,body>p,body>section{max-width:1200px;margin-left:auto;margin-right:auto}
body>h2{font-family:Georgia,serif;font-size:2rem;font-weight:500}body>section{background:#fff;border:1px solid #eadfdd;border-radius:18px;padding:1.25rem;margin-top:1.25rem;box-shadow:0 12px 32px #3019200d}
input,textarea,select,button{font:inherit;border:1px solid #eadfdd;border-radius:9px;padding:.65rem .8rem}input,textarea,select{max-width:100%}
button{background:#25151a;color:#fff;cursor:pointer}button:hover{background:#482a33}table{border-collapse:collapse;width:100%;min-width:720px}td,th{text-align:left;vertical-align:top;border-bottom:1px solid #eadfdd;padding:.7rem}th{color:#74676a;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em}
#products,#orders{overflow-x:auto}#modal>div{max-height:90vh;overflow:auto}#modal form>*{margin:.25rem}
.metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem;margin-top:1rem}.metric-card{padding:1rem;border:1px solid #eadfdd;border-radius:13px;background:#fff}.metric-card span{display:block;color:#74676a;font-size:.8rem}.metric-card strong{display:block;margin-top:.4rem;font-size:1.7rem;color:#25151a}.business-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:1rem;margin-top:1.25rem}.business-grid section{min-width:0}.sales-row{display:grid;grid-template-columns:90px 1fr 110px;gap:.7rem;align-items:center;margin:.75rem 0;font-size:.82rem}.sales-track{height:12px;overflow:hidden;border-radius:999px;background:#f2eaee}.sales-track span{display:block;height:100%;border-radius:inherit;background:#c86a78}.overview-note{color:#74676a;font-size:.85rem}.portal-link{display:inline-block;margin-left:.75rem;color:#a95060}@media(max-width:760px){.business-grid{grid-template-columns:1fr}.sales-row{grid-template-columns:72px 1fr 88px;font-size:.75rem}}
@media(max-width:700px){body>section{padding:1rem}body>section form{display:grid;gap:.5rem}body>section input,body>section textarea{width:100%}}
</style>
</head><body>
<h2>Owner Portal</h2>
<p>Signed in as <?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?> — <a href="../api/admin/logout.php" id="logout">Logout</a><a href="/" class="portal-link">View storefront</a></p>

<section>
  <h3>Business overview</h3>
  <p class="overview-note">Revenue includes paid and fulfilled orders. Awaiting EFT and cancelled orders are excluded.</p>
  <div id="business-metrics" class="metric-grid" aria-live="polite"></div>
  <div class="business-grid">
    <section>
      <h3>Confirmed revenue · last 6 months</h3>
      <div id="monthly-sales"></div>
    </section>
    <section>
      <h3>Recent registrations</h3>
      <div id="customers"></div>
    </section>
  </div>
</section>

<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;z-index:1000">
	<div style="background:#fff;padding:1rem;border-radius:6px;max-width:640px;width:95%">
		<h3>Edit product</h3>
		<form id="edit-form">
			<input type="hidden" id="modal-id" name="id">
			<input id="modal-sku" name="sku" placeholder="SKU"><br>
			<input id="modal-name" name="name" placeholder="Name" required><br>
			<input id="modal-category" name="category" placeholder="Category"><br>
			<input id="modal-short" name="short_description" placeholder="Short description"><br>
			<textarea id="modal-desc" name="description" placeholder="Description"></textarea><br>
			<input id="modal-price" name="price" type="number" step="0.01"><br>
			<input id="modal-stock" name="stock" type="number"><br>
			<label>Age restricted <input id="modal-age" name="age_restricted" type="checkbox"></label><br>
			<input id="modal-image-path" name="image_path" type="hidden">
			<label>Image: <input id="modal-image" name="image" type="file" accept="image/*"></label><br>
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
			<button>Save</button> <button type="button" id="modal-close">Cancel</button>
			<div id="modal-msg"></div>
		</form>
	</div>
</div>

<section>
	<h3>Create product</h3>
	<form id="create">
		<input name="sku" placeholder="SKU">
		<input name="name" placeholder="Name" required>
		<input name="category" placeholder="Category">
		<input name="short_description" placeholder="Short description">
		<input name="description" placeholder="Description">
		<input name="image_path" placeholder="Image path or URL">
		<input name="price" placeholder="Price" type="number" step="0.01">
		<input name="stock" placeholder="Stock" type="number">
		<label>Age restricted <input type="checkbox" name="age_restricted"></label>
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
		<button>Create</button>
	</form>
	<div id="create-msg"></div>
</section>

<section>
	<h3>Products</h3>
	<div id="products"></div>
</section>

<section>
	<h3>Recent orders</h3>
	<div id="orders"></div>
</section>

<script>
const adminCsrfToken = '<?php echo htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';

async function fetchOverview(){
  const metricsOut = document.getElementById('business-metrics');
  try{
    const response = await fetch('../api/admin/overview.php');
    const data = await response.json();
    if(!response.ok) throw new Error(data.error || 'Business overview could not be loaded.');

    const money = new Intl.NumberFormat('en-ZA', {style:'currency',currency:'ZAR'}).format(data.metrics.gross_revenue);
    const values = [
      ['Confirmed revenue', money],
      ['Paid orders', data.metrics.paid_orders],
      ['Awaiting EFT payment', data.metrics.awaiting_payment_orders],
      ['Registered customers', data.metrics.registered_customers],
      ['New customers · 30 days', data.metrics.new_customers_30_days],
      ['All orders', data.metrics.total_orders]
    ];
    metricsOut.replaceChildren();
    values.forEach(([label, value]) => {
      const card = document.createElement('article');
      card.className = 'metric-card';
      const title = document.createElement('span');
      title.textContent = label;
      const amount = document.createElement('strong');
      amount.textContent = String(value);
      card.append(title, amount);
      metricsOut.appendChild(card);
    });

    const sales = document.getElementById('monthly-sales');
    sales.replaceChildren();
    const monthly = Array.isArray(data.monthly_sales) ? data.monthly_sales : [];
    if(!monthly.length){
      sales.textContent = 'No confirmed sales in this period yet.';
    }else{
      const maxRevenue = Math.max(...monthly.map(row => Number(row.revenue) || 0), 1);
      monthly.forEach(row => {
        const line = document.createElement('div');
        line.className = 'sales-row';
        const date = document.createElement('span');
        const [year, month] = row.month.split('-').map(Number);
        date.textContent = new Date(year, month - 1, 1).toLocaleDateString('en-ZA', {month:'short',year:'2-digit'});
        const track = document.createElement('div');
        track.className = 'sales-track';
        const bar = document.createElement('span');
        bar.style.width = `${Math.max(2, (Number(row.revenue) / maxRevenue) * 100)}%`;
        track.appendChild(bar);
        const total = document.createElement('strong');
        total.textContent = new Intl.NumberFormat('en-ZA', {style:'currency',currency:'ZAR',maximumFractionDigits:0}).format(Number(row.revenue) || 0);
        line.append(date, track, total);
        sales.appendChild(line);
      });
    }

    const customersOut = document.getElementById('customers');
    customersOut.replaceChildren();
    if(!Array.isArray(data.recent_customers) || !data.recent_customers.length){
      customersOut.textContent = 'No customer accounts registered yet.';
    }else{
      const table = document.createElement('table');
      const head = document.createElement('thead');
      head.innerHTML = '<tr><th>Email</th><th>Registered</th></tr>';
      table.appendChild(head);
      const body = document.createElement('tbody');
      data.recent_customers.forEach(customer => {
        const row = document.createElement('tr');
        const email = document.createElement('td');
        email.textContent = customer.email;
        const date = document.createElement('td');
        date.textContent = new Date(customer.created_at).toLocaleDateString('en-ZA');
        row.append(email, date);
        body.appendChild(row);
      });
      table.appendChild(body);
      customersOut.appendChild(table);
    }
  }catch(error){
    metricsOut.textContent = error.message || 'Business overview could not be loaded.';
  }
}

async function fetchProducts(){
	const res = await fetch('../api/admin/products.php');
	const list = await res.json();
	const out = document.getElementById('products');
	out.innerHTML = '';
	const table = document.createElement('table');
	const thead = document.createElement('thead');
	thead.innerHTML = '<tr><th>ID</th><th>SKU</th><th>Name</th><th>Price</th><th>Stock</th><th>Age</th><th>Actions</th></tr>';
	table.appendChild(thead);
	const tb = document.createElement('tbody');
	list.forEach(p=>{
		const tr = document.createElement('tr');
		tr.innerHTML = `<td>${p.id}</td><td>${p.sku||''}</td><td>${p.name}</td><td>${p.price}</td><td>${p.stock}</td><td>${p.age_restricted? 'Yes':''}</td><td><button data-id="${p.id}" class="edit">Edit</button> <button data-id="${p.id}" class="del">Delete</button></td>`;
		tb.appendChild(tr);
	});
	table.appendChild(tb);
	out.appendChild(table);
	document.querySelectorAll('.del').forEach(b=>b.addEventListener('click', async e =>{
		const id = e.target.getAttribute('data-id');
		if(!confirm('Delete product '+id+'?')) return;
		const res = await fetch('../api/admin/products.php', {method:'DELETE', headers:{'Content-Type':'application/json','X-CSRF-Token': '<?php echo htmlspecialchars(get_csrf_token()); ?>'}, body:JSON.stringify({id:id,csrf_token:'<?php echo htmlspecialchars(get_csrf_token()); ?>'})});
		if(res.ok) fetchProducts();
		else alert('Delete failed');
	}));

	// Edit handlers - open modal
	document.querySelectorAll('.edit').forEach(b=>b.addEventListener('click', async e =>{
		const id = e.target.getAttribute('data-id');
		const res = await fetch('../api/admin/products.php?id='+encodeURIComponent(id));
		const p = await res.json();
		// Populate modal
		document.getElementById('modal-id').value = p.id;
		document.getElementById('modal-sku').value = p.sku || '';
		document.getElementById('modal-name').value = p.name || '';
		document.getElementById('modal-category').value = p.category || '';
		document.getElementById('modal-short').value = p.short_description || '';
		document.getElementById('modal-desc').value = p.description || '';
		document.getElementById('modal-price').value = p.price || 0;
		document.getElementById('modal-stock').value = p.stock || 0;
		document.getElementById('modal-age').checked = Number(p.age_restricted) === 1;
		document.getElementById('modal-image-path').value = p.image || '';
		document.getElementById('modal').style.display = 'flex';
	}));
}

document.getElementById('create').addEventListener('submit', async function(e){
	e.preventDefault();
	const fd = new FormData(e.target);
	const payload = {
		sku: fd.get('sku'),
		name: fd.get('name'),
		category: fd.get('category'),
		short_description: fd.get('short_description'),
		description: fd.get('description'),
		image_path: fd.get('image_path'),
		price: parseFloat(fd.get('price')||0),
		stock: parseInt(fd.get('stock')||0),
		age_restricted: fd.get('age_restricted')?1:0,
		csrf_token: fd.get('csrf_token')
	};
	const res = await fetch('../api/admin/products.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
	const j = await res.json();
	if(res.ok) { document.getElementById('create-msg').innerText = 'Created ID '+j.id; fetchProducts(); }
	else document.getElementById('create-msg').innerText = j.error || 'Create failed';
});

document.getElementById('logout').addEventListener('click', async function(e){
	e.preventDefault();
	await fetch('../api/admin/logout.php');
	location.reload();
});

fetchProducts();
fetchOverview();

async function fetchOrders(){
	const response = await fetch('../api/admin/orders.php');
	const list = await response.json();
	const out = document.getElementById('orders');
	out.replaceChildren();
	if(!response.ok){
		out.textContent = list.error || 'Orders could not be loaded.';
		return;
	}
	if(!list.length){
		out.textContent = 'No orders yet.';
		return;
	}
	const table = document.createElement('table');
	const head = document.createElement('thead');
	head.innerHTML = '<tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Delivery address</th><th>Action</th></tr>';
	table.appendChild(head);
	const body = document.createElement('tbody');
	const transitions = {
		awaiting_payment_eft: ['paid', 'cancelled'],
		paid: ['processing'],
		processing: ['shipped'],
		shipped: ['delivered'],
		delivered: [],
		cancelled: []
	};
	list.forEach(order => {
		const row = document.createElement('tr');
		const values = [
			`#${order.id}`,
			`${order.customer_name} · ${order.customer_email}`,
			order.items || '',
			`R${Number(order.total).toFixed(2)}`,
			order.payment_method
		];
		values.forEach(value => {
			const cell = document.createElement('td');
			cell.textContent = value;
			row.appendChild(cell);
		});
		const statusCell = document.createElement('td');
		const select = document.createElement('select');
		[order.status, ...(transitions[order.status] || [])].forEach(status => {
			const option = document.createElement('option');
			option.value = status;
			option.textContent = status.replaceAll('_', ' ');
			select.appendChild(option);
		});
		statusCell.appendChild(select);
		row.appendChild(statusCell);
		const address = document.createElement('td');
		address.textContent = order.shipping_address;
		row.appendChild(address);
		const actionCell = document.createElement('td');
		const save = document.createElement('button');
		save.type = 'button';
		save.textContent = 'Update';
		save.addEventListener('click', async () => {
			if(select.value === 'cancelled' && !confirm('Cancel this order and return its items to stock?')) return;
			const response = await fetch('../api/admin/orders.php', {
				method: 'PATCH',
				headers: {'Content-Type':'application/json','X-CSRF-Token':adminCsrfToken},
				body: JSON.stringify({id:order.id,status:select.value})
			});
			const result = await response.json();
			if(response.ok) fetchOrders();
			else alert(result.error || 'Order update failed');
		});
		actionCell.appendChild(save);
		row.appendChild(actionCell);
		body.appendChild(row);
	});
	table.appendChild(body);
	out.appendChild(table);
}
fetchOrders();
</script>

<script>
document.getElementById('modal-close').addEventListener('click', ()=>{ document.getElementById('modal').style.display='none'; });
document.getElementById('edit-form').addEventListener('submit', async function(e){
	e.preventDefault();
	const form = e.target;
	const fd = new FormData(form);
	const id = fd.get('id');
	// If an image file was selected, upload first
	const fileInput = document.getElementById('modal-image');
	if(fileInput && fileInput.files && fileInput.files[0]){
		const fdata = new FormData(); fdata.append('image', fileInput.files[0]);
		const up = await fetch('../api/admin/upload_image.php', {method:'POST', headers:{'X-CSRF-Token':adminCsrfToken}, body: fdata});
		const uj = await up.json();
		if(up.ok && uj.path) fd.set('image_path', uj.path);
		else { document.getElementById('modal-msg').innerText = 'Image upload failed'; return; }
	}
	// Build payload
	const payload = Object.fromEntries(fd.entries());
	payload.id = id;
	payload.age_restricted = payload.age_restricted ? 1 : 0;
	const res = await fetch('../api/admin/products.php', {method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?php echo htmlspecialchars(get_csrf_token()); ?>'}, body:JSON.stringify(payload)});
	const j = await res.json();
	if(res.ok){ document.getElementById('modal-msg').innerText = 'Saved'; document.getElementById('modal').style.display='none'; fetchProducts(); }
	else document.getElementById('modal-msg').innerText = j.error || 'Save failed';
});
</script>

</body></html>
