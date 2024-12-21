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
                $quantity->notEmpty()->withMessage("Quantity can't be empty")->isInt()->withMessage('Quantity must be int')->toInt();

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
            }
        } catch (PDOException $error) {
            send_response(['message' => "Something went wrong in the DB" . $error->getMessage()], 500);
        }
    }
}
