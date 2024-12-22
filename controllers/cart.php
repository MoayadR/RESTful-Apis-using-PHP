<?php

require_once('validators/validator.php');

class CartController
{

    private $ALLOWED_METHODS = ['POST', 'GET', 'PATCH', 'DELETE'];
    function find_cart_or_create($connection)
    {
        try {
            $stmt = $connection->prepare("SELECT * FROM nebulax_task.cart");

            $stmt->execute();

            if (!$stmt->rowCount()) {
                // create a cart
                $stmt = $connection->prepare("INSERT INTO nebulax_task.cart VALUES ()");
                $stmt->execute();

                return $connection->lastInsertId();
            } else {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return  $row['id'];
            }
        } catch (PDOException $error) {
            send_response(['message' => "Something went wrong in the DB" . $error->getMessage()], 500);
        }
    }

    function __construct($method, $data, $path, $connection)
    {
        if (!in_array($method, $this->ALLOWED_METHODS))
            send_response(['message' => 'Requested Resource Not Found!'], 404);

        $cart_id = $this->find_cart_or_create($connection); // checks if the cart is present or create a new one

        try {
            if ($method === 'POST') {
                if (!count($path))
                    send_response(['message' => 'You must specify the product id'], 400);

                $product_id = array_shift($path);

                $quantity = new Validator($data['quantity'] ?? null, 'quantity');
                $quantity->notEmpty()->withMessage("Quantity can't be empty")->isInt()->withMessage('Quantity must be int')->toInt(0);

                $errors = getAllErrorsFromValidator($quantity);
                if (count($errors))
                    send_response($errors, 422);

                $quantity_value = $quantity->value;

                // check if the product exists in the db or not
                $stmt = $connection->prepare('SELECT * FROM nebulax_task.product WHERE id = :id');
                $stmt->bindParam(':id', $product_id, PDO::PARAM_STR);
                $stmt->execute();

                if (!$stmt->rowCount())
                    send_response(['message' => 'Product Not Found'], 404);

                // check if the product is already in the cart if it exists add to the quantity and don't create a new instance
                $stmt = $connection->prepare('SELECT * FROM nebulax_task.cart_product WHERE product_id = :product_id');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->rowCount()) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $row_quantity = (int) $row['quantity'];
                    $new_quantity = $row_quantity + $quantity_value;

                    $stmt = $connection->prepare('UPDATE nebulax_task.cart_product SET quantity = :quantity WHERE id = :row_id');
                    $stmt->bindParam(':quantity', $new_quantity);
                    $stmt->bindParam(':row_id', $row['id'], PDO::PARAM_STR);

                    if ($stmt->execute())
                        send_response(['message' => 'Product Added to the cart'], 201);
                    else
                        send_response(['message' => 'Something went wrong'], 500);
                }

                // insert a new instance of the cart_product item
                $stmt = $connection->prepare('INSERT INTO nebulax_task.cart_product (product_id , cart_id , quantity) VALUES (:product_id , :cart_id , :quantity)');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_STR);
                $stmt->bindParam(':quantity', $quantity_value);

                if ($stmt->execute())
                    send_response(['message' => 'Product Added to the cart'], 201);
                else
                    send_response(['message' => 'Something went wrong'], 500);
            } elseif ($method === 'GET') {
                if (!count($path))
                    send_response(['message' => 'Requested Resource Not Found!'], 404);

                $resource = array_shift($path);
                $cart_id = $this->find_cart_or_create($connection); // checks if the cart is present or create a new one

                $stmt = $connection->prepare("SELECT * FROM nebulax_task.cart_product LEFT JOIN nebulax_task.product ON nebulax_task.cart_product.product_id = nebulax_task.product.id WHERE cart_id = :cart_id ");
                $stmt->bindParam(':cart_id', $cart_id, PDO::PARAM_STR);
                $stmt->execute();
                $rows = $stmt->fetchAll();

                if ($resource === 'products') {
                    $products = array_map(function ($element) {
                        return ['id' => $element['product_id'], 'quantity' => $element['quantity'], 'name' => $element['name'], 'sale_price' => $element['sale_price'], 'price' => $element['price'], 'image' => $element['image']];
                    }, $rows);
                    send_response($products, 200);
                } else if ($resource === 'pricing') {
                    $subTotal = 0;
                    $total = 0;
                    $taxes = 0.1; // 10%

                    foreach ($rows as $row) {
                        $subTotal += ($row['quantity'] * ($row['sale_price'] ?? $row['price']));
                    }

                    $total = $subTotal + ($subTotal * $taxes);

                    send_response(['Total' => $total, 'Subtotal' => $subTotal, 'Taxes' => $taxes], 200);
                } else {
                    send_response(['message' => 'Requested Resource Not Found!'], 404);
                }
            } elseif ($method === 'PATCH') {
                if (!count($path))
                    send_response(['message' => 'You must specify the product id'], 400);

                $product_id = array_shift($path);

                $stmt = $connection->prepare('SELECT * FROM nebulax_task.cart_product WHERE product_id = :product_id');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();
                if (!$stmt->rowCount())
                    send_response(['message' => 'Product is not in the cart'], 404);

                $quantity = new Validator($_GET['quantity'] ?? null, 'quantity');

                $quantity->notEmpty()->withMessage("Quantity can't be empty")->isInt(0)->withMessage('Quantity Must be int')->toInt();

                $quantity_value = $quantity->value;

                $stmt = $connection->prepare('UPDATE nebulax_task.cart_product SET quantity = :quantity_value WHERE product_id = :product_id');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->bindParam(':quantity_value', $quantity_value, PDO::PARAM_INT);
                $stmt->execute();

                send_response(['message' => 'Quantity changed successfully'], 200);
            } elseif ($method === 'DELETE') {
                if (!count($path))
                    send_response(['message' => 'You must specify the product id'], 400);

                $product_id = array_shift($path);

                $stmt = $connection->prepare('SELECT * FROM nebulax_task.cart_product WHERE product_id = :product_id');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();
                if (!$stmt->rowCount())
                    send_response(['message' => 'Product is not in the cart'], 404);

                $stmt = $connection->prepare('DELETE FROM nebulax_task.cart_product WHERE product_id = :product_id');
                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
                $stmt->execute();

                send_response(['message' => 'Deleted the product successfully'], 200);
            } else {
                send_response(['message' => 'Requested Resource Not Found!'], 404);
            }
        } catch (PDOException $error) {
            send_response(['message' => "Something went wrong in the DB" . $error->getMessage()], 500);
        }
    }
}
