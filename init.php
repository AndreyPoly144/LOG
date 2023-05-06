<?php
define("MY_DEFAULT_PATH", '/local/templates/.default');
function myPrint($var){
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}


//подклюение обработчика событий и агента
if (file_exists($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/event_handler.php')) {
    require($_SERVER['DOCUMENT_ROOT'].'/local/php_interface/event_handler.php');
}
//подключение компосера
if (file_exists($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php')) {
    require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');
}
