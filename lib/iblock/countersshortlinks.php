<?php

namespace Rest\Exponea\Iblock;

use Rest\Exponea\Api\Iblock;

class CountersShortLinks extends Iblock
{
    /**
     * @var string
     */
    protected $iblockCode = "counts_short_links";
    private const PROPERTY_COUNT_SHORT_LINK = "COUNT_SHORT_LINK";

    /**
     * Получение количесто использованных ссылок (по длине)
     * @return array
     */
    public function getCountLinksList(): array
    {
        $counterLinks = array();
        $iblockId = $this->getIblockIdByArray();

        if ($this->iblockIdEmpty() === false && $this->isModuleReady() && is_null($iblockId) === false) {
            $dbElement = \CIBlockElement::GetList(
                array("NAME" => "ASC"),
                array('IBLOCK_ID' => $iblockId),
                false,
                false,
                array("ID", "NAME", "PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK)
            );

            while ($element = $dbElement->Fetch()) {
                if (is_numeric($element['NAME'])) {
                    if (is_numeric(
                            $element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"]
                        ) === false || $element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"] <= 0) {
                        $element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"] = 0;
                    }

                    $counterLinks[$element['ID']] = array(
                        "LENGTH" => $element['NAME'],
                        "COUNT" => $element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"]
                    );
                }
            }
        }
        return $counterLinks;
    }

    /**
     * @param int $length
     * @param bool $increase
     * @return void
     */
    public function updateCounterLinks(int $length, bool $increase = true): void
    {
        $iblockId = $this->getIblockIdByArray();
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false && is_null($iblockId) === false) {
            $dbElement = \CIBlockElement::GetList(
                array(),
                array(
                    'IBLOCK_ID' => $iblockId,
                    "NAME" => $length
                ),
                false,
                false,
                array("ID", "PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK)
            );

            if ($element = $dbElement->Fetch()) {
                if (is_numeric($element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"])) {
                    $count = (int)$element["PROPERTY_" . self::PROPERTY_COUNT_SHORT_LINK . "_VALUE"];
                } else {
                    $count = 0;
                }

                if ($increase) {
                    $count++;
                } elseif ($count <= 0) {
                    $count = 0;
                } else {
                    $count--;
                }

                \CIBlockElement::SetPropertyValuesEx(
                    $element["ID"],
                    $iblockId,
                    array(
                        self::PROPERTY_COUNT_SHORT_LINK => $count
                    )
                );
            } else {
                $el = new \CIBlockElement();
                $el->Add(
                    array(
                        "ACTIVE" => "Y",
                        "IBLOCK_ID" => $iblockId,
                        "NAME" => $length,
                        "PROPERTY_VALUES" => array(
                            self::PROPERTY_COUNT_SHORT_LINK => ($increase) ? 1 : 0
                        )
                    )
                );
            }
        }
    }
}

?>