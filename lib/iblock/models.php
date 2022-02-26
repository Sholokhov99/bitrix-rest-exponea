<?php

namespace Rest\Exponea\Iblock;

use Rest\Exponea\Api\Iblock;

class Models extends Iblock
{
    protected $iblockCode = "";

    /**
     * @param int $idElement
     * @return array
     */
    public function getDetailUrl(int $idElement): array
    {
        $result = array();
        if ($idElement && $this->isModuleReady() && $this->iblockIdEmpty() === false) {
            $dbElementList = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "ID" => $idElement
                ),
                false,
                false,
                array("ID", "DETAIL_PAGE_URL")
            );

            if ($element = $dbElementList->GetNextElement()) {
                $arFields = $element->GetFields();

                $result = array(
                    "ID" => $arFields["ID"],
                    "URL" => $arFields["DETAIL_PAGE_URL"]
                );
            }
        }

        return $result;
    }
}

?>