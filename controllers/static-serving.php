<?php

class StaticServer
{
    function __construct($method, $path_elements)
    {
        if ($method !== 'GET')
            send_response(['message' => 'This method is not supported!'], 404);

        if (!count($path_elements))
            send_response(['message' => 'File name required'], 400);

        $fileName = array_shift($path_elements);
        $fileName = "uploads/" . $fileName;

        $mime =  mime_content_type($fileName);

        $fileSize =  filesize($fileName);

        // Clear output buffers to avoid conflicts To prevent the chunked transfer of file
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header("Accept-Ranges: bytes");
        header('Content-Length: ' . $fileSize);

        readfile($fileName);
        exit;
    }
}
