<?php

namespace Only\Site\Handlers;

class Iblock
{
    public static function addLog($field): void         //срабатывает при изменении или добавлении элемента любого инфоблока
    {
        $IBLOCK_LOG_ID = 12;          //id инфоблока LOG
        $IBLOCK_SECTION_ROOT_ID = false;  //id того раздела в который добавляем элемент или раздел (false - изначально добавляем в корень)

        //если изменения/добавления были не в LOG и они прошли успешно и модуль 'iblock' установлен
        if ($field['IBLOCK_ID'] != $IBLOCK_LOG_ID && $field['RESULT'] && \CModule::IncludeModule('iblock')) {

            //проверяем есть ли в LOG корневой раздел с именем равным коду инфоблока (если нет - добавляем и берем его ид, а если есть - просто берем его id)
            $IBLOCK_SECTION_ROOT_ID = self::addSection($IBLOCK_LOG_ID, $IBLOCK_SECTION_ROOT_ID, \CIBlock::GetArrayByID($field['IBLOCK_ID'], "CODE"));

            //если элемент находился в разделе
            if ($field['IBLOCK_SECTION']){
                //метод возвращает массив из разделов-родителей того раздела в который добавили элемент (включая сам раздел)
                $sectionsPathArr = self::getSectionsPath($field['IBLOCK_SECTION'][0], $field['IBLOCK_ID']);  //в метод передаем ид раздела в который добавили(или изменили) элемент, и ид инфоблока
                //добавляем разделы в корневой раздел в log (если их нет) и получаем ид раздела в который нужно добавить сам элемент
                $IBLOCK_SECTION_ROOT_ID = self::bypassSections($IBLOCK_LOG_ID, $IBLOCK_SECTION_ROOT_ID, $sectionsPathArr);
            }

            //собираем текст анонса элемента
            $PREVIEW_TEXT = self::createPreviewText(\CIBlock::GetArrayByID($field['IBLOCK_ID'], "NAME"), $sectionsPathArr ?? [], $field['NAME']);

            //в финале добавляем элемент
            self::addElement($IBLOCK_SECTION_ROOT_ID, $IBLOCK_LOG_ID, $field['ID'], $PREVIEW_TEXT);
        }
    }

    public static function getSectionsPath(int $SECT_ID, int $IBLOCK_ID): array
    {
        return \CIBlockSection::GetNavChain(
            $IBLOCK_ID,
            $SECT_ID,
            array('ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE'),
            true
        );
    }

    public static function bypassSections(int $IBLOCK_LOG_ID, int $IBLOCK_SECTION_ROOT_ID, array $sectionsPathArr): int
    {
        foreach ($sectionsPathArr as $section) {
            $IBLOCK_SECTION_ROOT_ID = self::addSection($IBLOCK_LOG_ID, $IBLOCK_SECTION_ROOT_ID, $section['NAME']);
        }
        return $IBLOCK_SECTION_ROOT_ID;      //вернем ид того раздела в который должен добавляться элемент
    }


    public static function addSection(int $IBLOCK_LOG_ID, int|bool $IBLOCK_SECTION_ROOT_ID, string $NAME): int
    {
        $arFilter = array("IBLOCK_ID" => $IBLOCK_LOG_ID, "NAME" => $NAME);
        $db_list = \CIBlockSection::GetList(array(), $arFilter);
        $ar_result = $db_list->GetNext();
        if (!$ar_result) {                                           //если раздела нет, добавляем его
            $bs = new \CIBlockSection;
            $arFields = array(
                "ACTIVE" => 'Y',
                "IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ROOT_ID,
                "IBLOCK_ID" => $IBLOCK_LOG_ID,
                "NAME" => $NAME,
            );
            return $bs->Add($arFields);
        } else {                                                          //если раздел уже есть вернем его id
            return $ar_result['ID'];
        }
    }

    public static function addElement(int $IBLOCK_SECTION_ROOT_ID, int $IBLOCK_LOG_ID, string $name, string $PREVIEW_TEXT): void
    {
        $el = new \CIBlockElement();
        $arLoadProductArray = [
            "IBLOCK_SECTION_ID" => $IBLOCK_SECTION_ROOT_ID,   //раздел в который кладется элемент
            "IBLOCK_ID" => $IBLOCK_LOG_ID,           //id инфоблока LOG
            "NAME" => $name,         //в имя элемента кладем id логируемого элемента
            "ACTIVE" => 'Y',
            'ACTIVE_FROM' => ConvertTimeStamp(false, 'FULL'),
            'PREVIEW_TEXT' => $PREVIEW_TEXT
        ];
        $el->Add($arLoadProductArray);
    }

    public static function createPreviewText(string $iblockName, array $sectionsPathArr, string $elemName): string
    {
        $PREVIEW_TEXT = "$iblockName ->";
        if ($sectionsPathArr != []) {
            foreach ($sectionsPathArr as $section) {
                $PREVIEW_TEXT .= " {$section['NAME']} ->";
            }
        }
        $PREVIEW_TEXT .= " $elemName";
        return $PREVIEW_TEXT;
    }




//НИЖЕ НЕ МОЙ КОД
    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

}

