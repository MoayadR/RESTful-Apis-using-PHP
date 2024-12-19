<?php

require_once('validators/validator.php');

function generateUUID()
{
    // Generate 16 random bytes
    $data = random_bytes(16);

    // Set version to 4 (random)
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

    // Set variant to 10xx
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    // Convert to UUID format
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

class ProductController
{
    private $ALLOWED_METHODS = ['POST', 'GET', 'PUT'];
    private $ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];

    function handleImageUpload()
    {
        if (!isset($_FILES['image']))
            return false;

        if (!in_array($_FILES['image']['type'], $this->ALLOWED_TYPES))
            return false;

        // Extract the file extension
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (!$file_extension) {
            return ["status" => false, "message" => "File extension is missing"];
        }

        $target_dir = "uploads/";
        if (!is_dir('uploads/')) {
            mkdir('uploads/', 0777, true); // Create the directory with write permissions
        }

        $target_file = $target_dir . basename(generateUUID()) . '.' . $file_extension;

        // Check file size
        if ($_FILES["image"]["size"] > 10000000)
            return false;

        // Move the uploaded file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Get the server host dynamically
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
            if ($method === 'POST') // create product
            {
                $path = $this->handleImageUpload();

                if (!$path)
                    send_response(['message' => "Image Type Not Supported for this resource"], 400);

                $validatePrice = new Validator($data['price']);
                $validatePrice->isString()->withMessage('This is not double')->toDouble();

                var_dump($data['price']);
                var_dump($validatePrice->getValue());

                send_response(['product' => $validatePrice->getValue()], 200);
            } elseif ($method === 'GET') {
                $stmt = $connection->prepare("SELECT * FROM nebulax_task.product");
                $stmt->execute();

                $rows = $stmt->fetchAll();

                send_response(['products' => $rows], 200);
            } elseif ($method === 'PUT') {
            } elseif ($method === 'DELETE') {
            }
        } catch (PDOException $error) {
            send_response(['message' => "Something went wrong in the DB" . $error->getMessage()], 500);
        }
    }
}
