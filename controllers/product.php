<?php

require_once('validators/validator.php');

class ProductController
{
    private $ALLOWED_METHODS = ['POST', 'GET', 'PUT', 'DELETE'];
    private $ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];

    function handleImageUpload()
    {
        if (!isset($_FILES['image']))
            return false;

        if (!in_array($_FILES['image']['type'], $this->ALLOWED_TYPES))
            return false;

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (!$file_extension) {
            return ["status" => false, "message" => "File extension is missing"];
        }

        $target_dir = "uploads/";
        if (!is_dir('uploads/')) {
            mkdir('uploads/', 0777, true);
        }

        $target_file = $target_dir . basename(generateUUID()) . '.' . $file_extension;

        if ($_FILES["image"]["size"] > 10000000)
            return false;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $protocol . $_SERVER['HTTP_HOST'];

            return $host . '/' . $target_file;
        }

        return false;
    }

    function __construct($method, $data, $path, $connection)
    {
        if (!in_array($method, $this->ALLOWED_METHODS))
            send_response(['message' => 'Requested Resource Not Found!'], 404);

        try {
            if ($method === 'POST') {
                $image_path = $this->handleImageUpload();

                if (!$image_path)
                    send_response(['message' => "Image Type Not Supported for this resource"], 400);

                $price = new Validator($data['price'] ?? null, 'price');
                $sale_price = new Validator($data['sale_price'] ?? null, 'sale_price');
                $name = new Validator($data['name'] ?? null, 'name');

                $price->notEmpty()->withMessage("Price Can't be empty")->isDouble(0)->withMessage('Price Must Be Double')->toDouble();
                $sale_price->optional()->isDouble(0)->withMessage('Sale Price Must Be Double')->toDouble();
                $name->notEmpty()->withMessage("Name Can't be Empty")->isString()->withMessage('Name Must Be String')->toString();

                $errors = getAllErrorsFromValidator($price, $sale_price, $name);
                if (count($errors))
                    send_response($errors, 422);

                $price_value = $price->value;
                $sale_price_value = $sale_price->value;
                $name_value = $name->value;

                $stmt = $connection->prepare("INSERT INTO nebulax_task.product (name , price , sale_price , image) VALUES (:name , :price_value , :sale_price_value , :image)");
                $stmt->bindParam(':name', $name_value, PDO::PARAM_STR);
                $stmt->bindParam(':image', $image_path, PDO::PARAM_STR);
                $stmt->bindParam(':sale_price_value', $sale_price_value);
                $stmt->bindParam(':price_value', $price_value);

                if ($stmt->execute()) {
                    $lastId = $connection->lastInsertId();

                    $sql = "SELECT * FROM nebulax_task.product WHERE id = :id";
                    $stmt = $connection->prepare($sql);
                    $stmt->bindParam(':id', $lastId, PDO::PARAM_INT);
                    $stmt->execute();

                    $insertedRow = $stmt->fetch(PDO::FETCH_ASSOC);
                    send_response($insertedRow, 201);
                } else {
                    send_response(['message' => "Couldn't create the product"], 500);
                }
            } elseif ($method === 'GET') {
                $stmt = $connection->prepare("SELECT * FROM nebulax_task.product");
                $stmt->execute();

                $rows = $stmt->fetchAll();

                send_response(['products' => $rows], 200);
            } elseif ($method === 'PUT') {
                if (!count($path))
                    send_response(['message' => 'You must specify the product id'], 400);

                $id = array_shift($path);

                $stmt = $connection->prepare("SELECT * FROM nebulax_task.product WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt->execute();
                $product_row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (empty($product_row))
                    send_response(['message' => 'Product not found'], 404);


                $data = get_formdata_PUT();
                $image_path = $data['image']['path'] ?? $product_row['image'];
                print_r($data);

                $price = new Validator($data['price'] ?? null, 'price');
                $sale_price = new Validator($data['sale_price'] ?? null, 'sale_price');
                $name = new Validator($data['name'] ?? null, 'name');

                $price->notEmpty()->withMessage("Price Can't be empty")->isDouble(0)->withMessage('Price Must Be Double')->toDouble();
                $sale_price->notEmpty()->withMessage("Sale Price Can't be empty")->isDouble(0)->withMessage('Sale Price Must Be Double')->toDouble();
                $name->notEmpty()->withMessage("Name Can't be Empty")->isString()->withMessage('Name Must Be String')->toString();

                $errors = getAllErrorsFromValidator($price, $sale_price, $name);
                if (count($errors))
                    send_response($errors, 422);

                $price_value = $price->value;
                $sale_price_value = $sale_price->value;
                $name_value = $name->value;

                $stmt2 = $connection->prepare("UPDATE nebulax_task.product SET name = :name , price = :price , sale_price = :sale_price , image = :image WHERE id = :id");
                $stmt2->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt2->bindParam(':name', $name_value, PDO::PARAM_STR);
                $stmt2->bindParam(':price', $price_value);
                $stmt2->bindParam(':sale_price', $sale_price_value);
                $stmt2->bindParam(':image', $image_path, PDO::PARAM_STR);

                if ($stmt2->execute())
                    send_response(['message' => 'Updated the product'], 200);
                else
                    send_response(['message' => 'Something went wrong'], 500);
            } elseif ($method === 'DELETE') {
                if (!count($path))
                    send_response(['message' => 'You must specify the id of the product'], 400);

                $id = array_shift($path);

                $stmt = $connection->prepare('SELECT * FROM nebulax_task.product WHERE id = :id');
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);

                $stmt->execute();
                $count = $stmt->rowCount();

                if ($count !== 1)
                    send_response(['message' => 'Product not found'], 404);

                // delete any cart_product associated with the product
                $stmt = $connection->prepare('DELETE FROM nebulax_task.cart_product WHERE product_id =  :id');
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt->execute();

                // delete the product
                $stmt = $connection->prepare('DELETE FROM nebulax_task.product WHERE id = :id');
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    send_response(['message' => 'Product deleted successfully'], 200);
                } else {
                    send_response(['message' => "Couldn't delete the product"], 500);
                }
            }
        } catch (PDOException $error) {
            send_response(['message' => "Something went wrong in the DB" . $error->getMessage()], 500);
        }
    }
}
