<?php

namespace Rest\Exponea;

use Bitrix\Main\HttpRequest;
use Rest\Exponea\Actions\Answer;
use Bitrix\Main\Localization\Loc;

class Options
{
    /**
     * Токен от Exponea
     */
    public const EXPONEA_TOKEN = "EXPONEA_TOKEN";
    /**
     * Токен для вебхука коротких ссылок
     */
    public const EXPONEA_WEBHOOK = "EXPONEA_WEBHOOK";
    /**
     *
     */
    public const EXPONEA_WEBHOOK_SHORTLINK_PRODUCT = "EXPONEA_WEBHOOK_SHORTLINK_PRODUCT";

    /**
     * Сохранение данных в БД из админки
     * @param HttpRequest $request
     * @return bool
     */
    public static function saveAjax(HttpRequest $request): string
    {
        $error = [];
        if (check_bitrix_sessid()) {
            $fields = (array)$request->getPost("fileds");

            foreach ($fields as $key => $value) {
                if (defined(__CLASS__ . "::" . $key)) {
                    \COption::SetOptionString(Application::MODULE_ID, $key, $value);
                } else {
                    $error[] = str_replace(
                        "#NAME#",
                        $key,
                        Loc::getMessage("OPTION_AJAX_NAME_NOTFOUND")
                    );
                }
            }
            return implode("<br>", $error);
        }

        return Loc::getMessage("OPTION_AJAX_SESSION_ERROR");
    }
}