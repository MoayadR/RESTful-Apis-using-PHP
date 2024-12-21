<?php

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
/**
 * Get the API method
 * @return String The API method
 */
function get_method()
{
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get data object from API data
 * @return Object The data object
 */
function get_request_data()
{
    return array_merge(empty($_POST) ? array() : $_POST, (array) json_decode(file_get_contents('php://input'), true), $_GET);
}

/**
 * Send an API response
 * @param  *       $response The API response
 * @param  integer $code     The response code
 */
function send_response($response, $code = 200)
{
    http_response_code($code);
    die(json_encode($response));
}

/**
 * Get path from the request url
 * @return String the path
 */
function get_path_from_request()
{
    return $_SERVER['REQUEST_URI'];
}

/**
 * Get the path elements
 */
function get_path_elements()
{

    $path = get_path_from_request();

    $exploded_path = (explode('/', $path));

    $filtered_array =  (array_filter($exploded_path, function ($arg) {
        return (strlen($arg) > 0) && $arg[0] !== '?';
    }));

    $reindexed_array = array_values($filtered_array);

    return $reindexed_array;
}

/**
 * Get the query params of a string or else empty array
 */
function get_query_params()
{
    return $_GET;
}

function get_formdata_PUT()
{
    // echo $_SERVER['CONTENT_TYPE'];

    if (preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'] ?? "", $matches)) {
        $boundary = $matches[1];
    } else {
        return [];
    }

    $parsedData = [];

    $raw_data = file_get_contents('php://input');

    $raw_segments = explode($boundary, $raw_data);


    // preprocessing
    array_pop($raw_segments);
    array_shift($raw_segments);
    foreach ($raw_segments as $part) {
        $newString = trim($part, '\n\r-');

        list($headers, $body) = explode("\r\n\r\n", $newString, 2);
        $headers = ltrim($headers);
        $body = rtrim($body);

        if (preg_match('/name="([^"]+)"/', $headers, $matches)) {
            // print_r($matches);
            $name = $matches[1];
        }

        if (preg_match('/filename="(.*)"/', $headers, $matches)) {
            // print_r($matches);
            $filename = $matches[1] ?? null;
        }

        if (!is_null($filename)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $tempFile = 'uploads/' . generateUUID() . '.' . $extension;
            file_put_contents($tempFile, $body);
            $parsedData[$name] = [
                'filename' => $filename,
                'size' => strlen($body),
                'path' => $tempFile
            ];
        } else {
            $parsedData[$name] = $body;
        }
    }
    var_dump($parsedData);
    return $parsedData;
}

function PUT(string $name): string
{

    $lines = file('php://input');
    $keyLinePrefix = 'Content-Disposition: form-data; name="';

    $PUT = [];
    $findLineNum = null;

    foreach ($lines as $num => $line) {
        if (strpos($line, $keyLinePrefix) !== false) {
            if ($findLineNum) {
                break;
            }
            if ($name !== substr($line, 38, -3)) {
                continue;
            }
            $findLineNum = $num;
        } else if ($findLineNum) {
            $PUT[] = $line;
        }
    }

    array_shift($PUT);
    array_pop($PUT);

    return mb_substr(implode('', $PUT), 0, -2, 'UTF-8');
}
