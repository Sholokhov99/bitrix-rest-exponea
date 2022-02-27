<?php

namespace Rest\Exponea\Actions\Exponea\Events;

class ShortLinks
{
    public static function OnBeforeAddNewProductShortLink($fields)
    {
        $result = [
            "ID" => "HAHA",
            "URL" => "SDGFG",
        ];

        return $result;
    }
}