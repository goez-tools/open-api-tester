<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$file = $_SERVER['DOCUMENT_ROOT'] . $uri;

// The built-in server will directly access existing files
if (file_exists($file) && !is_dir($file)) {
    return false;
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/index.html")) {
    $body = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/index.html");
    echo $body;

    return true;
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/index.php")) {
    include $_SERVER['DOCUMENT_ROOT'] . "/index.php";

    return true;
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/public/index.php")) {
    include $_SERVER['DOCUMENT_ROOT'] . "/public/index.php";

    return true;
}
