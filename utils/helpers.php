
<?php
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

?>