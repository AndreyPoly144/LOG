<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs(int $IBLOCK_LOG_ID = 12):string
    {
        //перебираем элементы отсортированые по Дате изменения, и удаляем все, кроме первых десяти
        if(\CModule::IncludeModule("iblock")){
            $arFilter = Array("IBLOCK_ID"=>$IBLOCK_LOG_ID);
            $res = \CIBlockElement::GetList(['TIMESTAMP_X' => 'DESC'], $arFilter);
            $count=0;
            while($ob = $res->GetNextElement()){
                $arFields = $ob->GetFields();
                $count++;
                if($count>10){
                    \CIBlockElement::Delete($arFields['ID']);
                }
            }
        }
        return "Only\Site\Agents\Iblock::clearOldLogs();";
    }


    //НИЖЕ НЕ МОЙ КОД
    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
