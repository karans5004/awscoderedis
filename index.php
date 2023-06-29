<!DOCTYPE html>
<html>
<head>
	<title>Ahmad</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css">
</head>
<body style="background-color: #EB984E">
	<div class="ui container">
		<div class="ui segment" style="max-width: 800px; margin: 0 auto;">
			
			<h2 class="ui center aligned header""><font color="blue">AWS DB and Redis</h2>
			<h5 class="ui center aligned header"><font color="brown">Server IP Address <?= $_SERVER['SERVER_ADDR'] ?></h5>
			<form class="ui form" style="width: 400px; margin: 0 auto" action="javascript:void(0)">
				<h3 class="ui header"></h3>
				<div class="field">
					<label>Product name</label>
					<input type="text" placeholder="Product name" id="new_product_name" required>
				</div>
				<div class="field">
					<label>Product quantity</label>
					<input type="number" placeholder="Product Qty." id="new_product_quantity" required>
				</div>
				<div class="field">
					<label>Product price</label>
					<input type="number" placeholder="Product price" id="new_product_price" required>
				</div>
				<div class="field">
					<div class="ui center aligned grid">
						<div class="eight wide column">
							<button class="ui teal left labeled icon button" id="add_from_file">
								<i class="file icon"></i>
								Load
							</button>
						</div>
						<div class="eight wide column">
							<button class="ui teal right labeled icon button" id="product_add">
								<i class="plus icon"></i>
								Add
							</button>
						</div>
					</div>
				</div>
			</form>
			<br><br>
			<div class="ui center aligned grid">
				<div class="eight wide column">
					<div class="ui action left icon input">
						<i class="search icon"></i>
						<input type="text" placeholder="Product ID" id="product_id_search">
						<button class="ui grey icon button" id="search_product">Search</button>
					</div>
				</div>
				<div class="eight wide column">
					<div class="ui buttons">
						<button class="ui grey button" id="get_all_db">DB</button>
						<div class="or"></div>
						<button class="ui teal button" id="get_all_cache">Cache</button>
					</div>
				</div>
			</div>
			<table class="ui celled table">
				<thead>
					<tr>
						<th class="center aligned">ID</th>
						<th class="center aligned">Name</th>
						<th class="center aligned">Qty.</th>
						<th class="center aligned">Price</th>
						<th class="center aligned">Source</th>
						<th></th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"></script>
<script type="text/javascript">
	let db_conn_str = '';

	$('.setting').click(() => {
		db_conn_str = '';

		while (db_conn_str.length === 0) 
			db_conn_str = prompt('Enter the file path');

		console.log(`db_conn_str ${db_conn_str}`);
	});

	$('#get_all_db').click(() => {
		$.ajax({
			url: `data.php?operation=get_all_db&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
				if (!res.hasOwnProperty('products')) {
					alert('product not found');
					return;
				}
				$('tbody').empty();

				for (const product of res.products) {
					$('tbody').append(`
						<tr data-id="${product.id}" data-name="${product.name}" data-quantity="${product.quantity}" data-price="${product.price}"> 
							<td class="center">${product.id}</td>
							<td class="center">${product.name}</td>
							<td class="center">${product.quantity}</td>
							<td class="center">${product.price}</td>
							<td class="center">${res.source}</td>
							<td class="center"><i class="edit green icon"></i></td>
							<td class="center"><i class="trash alternate red icon"></i></td>
						</tr>
					`)
				}
			},
			error: error_handler
		});
	});

	$('#get_all_cache').click(() => {
		$.ajax({
			url: `data.php?operation=get_all_cache&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
				if (!res.hasOwnProperty('products')) {
					alert('product not found');
					return;
				}
				$('tbody').empty();

				for (const product of res.products) {
					$('tbody').append(`
						<tr data-id="${product.id}" data-name="${product.name}" data-quantity="${product.quantity}" data-price="${product.price}"> 
							<td class="center">${product.id}</td>
							<td class="center">${product.name}</td>
							<td class="center">${product.quantity}</td>
							<td class="center">${product.price}</td>
							<td class="center">${res.source}</td>
							<td class="center"><i class="edit green icon"></i></td>
							<td class="center"><i class="trash alternate red icon"></i></td>
						</tr>
					`)
				}
			},
			error: error_handler
		});
	});


	$('#add_from_file').click(() => {
		file_path = prompt('Enter file path');

		$.ajax({
			url: `data.php?operation=add_from_file&file_path=${file_path}&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
			},
			error: error_handler
		});
	});

	// add
	$('#product_add').click(() => {
		$.ajax({
			url: `data.php?operation=add&product_name=${$('#new_product_name').val()}&product_quantity=${$('#new_product_quantity').val()}&product_price=${$('#new_product_price').val()}&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
			},
			error: error_handler
		});
	});


	// search by ID
	$('#search_product').click(() => {
		$.ajax({
			url: `data.php?operation=search&product_id=${$('#product_id_search').val()}&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
				if (!res.hasOwnProperty('product')) {
					alert('product not found');
					return;
				}
				$('tbody').empty();

				$('tbody').append(`
					<tr data-id="${res.product.id}" data-name="${res.product.name}" data-quantity="${res.product.quantity}" data-price="${res.product.price}"> 
						<td class="center">${res.product.id}</td>
						<td class="center">${res.product.name}</td>
						<td class="center">${res.product.quantity}</td>
						<td class="center">${res.product.price}</td>
						<td class="center">${res.source}</td>
						<td class="center"><i class="edit green icon"></i></td>
						<td class="center"><i class="trash alternate red icon"></i></td>
					</tr>
				`)
			},
			error: error_handler
		});
	});


	//update
	$(document).on('click', '.edit.icon', function() {
		let tr = $(this).closest('tr')[0];
		let product_id = $(tr).data('id'),
			product_name = undefined,
			product_quantity = undefined,
			product_price = undefined;

		while (!product_name || product_name.length === 0)
			product_name = prompt('Product name');

		while (!product_quantity || isNaN(product_quantity))
			product_quantity = prompt('Product quantity');

		while (!product_price || isNaN(product_price))
			product_price = prompt('Product price');

		$.ajax({
			url: `data.php?operation=update&product_id=${product_id}&product_name=${product_name}&product_quantity=${product_quantity}&product_price=${product_price}&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}
			},
			error: error_handler
		});
	});

	// delete
	$(document).on('click', '.trash.icon', function() {
		let tr = $(this).closest('tr')[0]
		let product_id = $(tr).data('id')

		$.ajax({
			url: `data.php?operation=delete&product_id=${product_id}&db_conn_str=${db_conn_str}`,
			success: res => {
				if (res.error) {
					alert(res.message);
					return;
				}	
				$(tr).remove();
			},
			error: error_handler
		});
	});


	error_handler = (err) => {
		console.log(err);
		alert(err.message);
	}
</script>
</body>
</html>
