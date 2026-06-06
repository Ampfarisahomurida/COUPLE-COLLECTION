<?php
require_once __DIR__ . '/../session.php';
if(empty($_SESSION['is_admin'])){
	// Simple client-side login form
	?>
	<!doctype html>
	<html><head><meta charset="utf-8"><title>Admin Login</title></head><body>
	<h2>Admin Login</h2>
	<form id="login">
		<label>Email: <input type="email" name="email" required></label><br>
		<label>Password: <input type="password" name="password" required></label><br>
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
	</body></html>
	<?php
	exit;
}

// Admin UI
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Dashboard</title>
<style>body{font-family:Arial,Helvetica,sans-serif;padding:1rem}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>
</head><body>
<h2>Admin Dashboard</h2>
<p>Signed in as <?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?> — <a href="../api/admin/logout.php" id="logout">Logout</a></p>

<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;display:flex;z-index:1000">
	<div style="background:#fff;padding:1rem;border-radius:6px;max-width:640px;width:95%">
		<h3>Edit product</h3>
		<form id="edit-form">
			<input type="hidden" id="modal-id" name="id">
			<input id="modal-sku" name="sku" placeholder="SKU"><br>
			<input id="modal-name" name="name" placeholder="Name" required><br>
			<input id="modal-short" name="short_description" placeholder="Short description"><br>
			<textarea id="modal-desc" name="description" placeholder="Description"></textarea><br>
			<input id="modal-price" name="price" type="number" step="0.01"><br>
			<input id="modal-stock" name="stock" type="number"><br>
			<label>Age restricted <input id="modal-age" name="age_restricted" type="checkbox"></label><br>
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

<script>
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
		document.getElementById('modal-age').checked = !!p.age_restricted;
		document.getElementById('modal').style.display = 'block';
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
		const up = await fetch('../api/admin/upload_image.php', {method:'POST', body: fdata});
		const uj = await up.json();
		if(up.ok && uj.path) fd.append('image_path', uj.path);
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
