<?php

namespace Rest\Exponea\Iblock\Events;

use Rest\Exponea\Iblock\ShortLinks;
use Rest\Exponea\Iblock\CountersShortLinks;

class EditShortLink
{
    private static $shortLinks;
    private static $countersShortLinks;

    private static function initShortLinks(): void
    {
        static::$shortLinks = new ShortLinks();
        static::$countersShortLinks = new CountersShortLinks();
    }

    /**
     * @param string $code
     * @param int $iblockId
     * @param string $active
     * @param bool $increase
     * @return void
     */
    private static function updateCounter(string $code, int $iblockId, string $active, bool $increase): void
    {
        if(is_null(static::$shortLinks)){
            static::initShortLinks();
        }

        $lenght = strlen($code);
        if ($lenght && in_array($iblockId, static::$shortLinks->getIblockId()) && $active == "Y") {
            static::$countersShortLinks->updateCounterLinks($lenght, $increase);
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public static function deleteLink(int $id): void
    {
        $arFields = \CIBlockElement::GetByID($id)->Fetch();
        static::updateCounter(
            (string)$arFields["CODE"],
            (int)$arFields["IBLOCK_ID"],
            (string)$arFields["ACTIVE"],
            false
        );
    }

    /**
     * @param array $arFields
     * @return void
     */
    public static function addShortLink(array $arFields): void
    {
        static::updateCounter(
            (string)$arFields["CODE"],
            (int)$arFields["IBLOCK_ID"],
            (string)$arFields["ACTIVE"],
            true
        );
    }
}

?>