<?php

// if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
// 	$uri = 'https://';
// } else {
// 	$uri = 'http://';
// }
// $uri .= $_SERVER['HTTP_HOST'];
// header('Location: ' . $uri . '/dashboard/');
// exit;
// 

///////////////////////////////////////////////////////////////////////////

/**
 * @routes
 * 
 * @Products
 * GET /products/
 * POST /products/ 
 * PUT /products/:id
 * DELETE /products/:id
 * 
 * @Cart
 * POST /cart/:id
 * PATCH /cart/:id?quantity=2
 * DELETE /cart/:id
 * GET /pricing
 * GET /products
 * 
 */

header('Content-Type: application/json');
require_once('utils/helpers.php');
require_once('utils/db-config.php');
require_once('controllers/product.php');
require_once('controllers/cart.php');
require_once('controllers/static-serving.php');

$path_elements = get_path_elements();

if (!count($path_elements))
	send_response(['message' => 'This is the index'], 200);

$method = get_method();
$data = get_request_data();

$resource_controller = array_shift($path_elements); // remove first element

switch ($resource_controller) {
	case 'products':
		new ProductController($method, $data, $path_elements, $connection);
		break;
	case 'cart':
		new CartController($method, $data, $path_elements, $connection);
		break;
	case 'uploads':
		new StaticServer($method, $path_elements);
		break;
	default:
		send_response(['message' => 'Requested Resource Not Found!'], 404);
		break;
}
