<?php

namespace Rest\Exponea\Tools\Http;

use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;

class Tools
{
    public static function getUrlWeb(string $curDir = ""): string
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $uri = new Uri($request->getRequestUri());
        return $uri->getScheme() . "://" . $_SERVER['SERVER_NAME'] . "/" . $curDir;
    }
}