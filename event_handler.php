<?php
//регистрируем обработчик
AddEventHandler("iblock", "OnAfterIBlockElementAdd", array("Only\Site\Handlers\Iblock", "addLog"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", array("Only\Site\Handlers\Iblock", "addLog"));

//подключаем класс с обработчиком
require($_SERVER['DOCUMENT_ROOT'].'/local/modules/dev.site/lib/Handlers/Iblock.php');
//подключаем класс с агентом
require($_SERVER['DOCUMENT_ROOT'].'/local/modules/dev.site/lib/Agents/Iblock.php');



