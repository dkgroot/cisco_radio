<?php
function get_server() {
    return Array(
        'protocol' => $_SERVER['REQUEST_SCHEME'],
        'address' => $_SERVER['SERVER_ADDR'],
        'port' => $_SERVER['SERVER_PORT'],
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
}

function get_baseUrl() {
    if(isset($_SERVER['HTTP_HOST'])) {
        $server= get_server();
        return $server['protocol'] . "://" . $server['address'] . ":" . $server['port'] . $server['path'];
    } else{
        return dirname(__DIR__) . "/index.php";
    }
}

function get_appUrl() {
    if(isset($_SERVER['HTTP_HOST'])) {
        $server= get_server();
        return $server['protocol'] . ":" . $server['address'] . ":" . $server['port'] . $server['path'];
    } else{
        return dirname(__DIR__) . "/index.php";
    }
}
?>
