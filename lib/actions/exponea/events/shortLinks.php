<?php

namespace Rest\Exponea\Actions\Exponea\Events;

class ShortLinks
{
    public static function OnBeforeAddNewProductShortLink($fields)
    {
        $result = [
            "ID" => "0",
            "URL" => "https://test.ru",
        ];

        return $result;
    }
}