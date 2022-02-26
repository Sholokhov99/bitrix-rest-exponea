<?php

namespace Rest\Exponea\ShortLinks;

use Bitrix\Main\Localization\Loc;
use Rest\Exponea\Actions\Answer;

class Errors extends Answer
{
    public const CODE_ERROR_ACCESS = "-1";
    public const CODE_ERROR_NOT_URL = "56";
    public const CODE_DELETE_SHORT_LINK = "245";
    public const CODE_NOT_URL = "3";
    public const CODE_UPDATE_SHORT_LINK = "0";
    public const CODE_ADD_SHORT_LINK = "2";
    public const CODE_ARR_NOT_FOUND_METHOD = "1";

    private const SPLIT_ERROR = " | ";

    private function setErrorByCode(string $key, string $error): void
    {
        $arrError = array(
            "code" => $key,
            "text" => $error
        );

        $this->setErrorByArray($arrError);
    }

    public function errorAccess(): void
    {
        $this->setErrorByCode(self::CODE_ERROR_ACCESS, Loc::getMessage("ERROR_ACCESS"));
    }

    public function errorDublicateUrl(): void
    {
        $this->setErrorByCode(self::CODE_ERROR_NOT_URL, Loc::getMessage("ERROR_DUPLICATE_SHORT_LINK"));
    }

    public function errorDelete(): void
    {
        $this->setErrorByCode(self::CODE_DELETE_SHORT_LINK, Loc::getMessage("ERROR_DELETE_SHORT_LINK"));
    }

    public function errorNotUrl(): void
    {
        $this->setErrorByCode(self::CODE_NOT_URL, Loc::getMessage('ERROR_NOT_URL'));
    }

    public function errorUpdate(): void
    {
        $this->setErrorByCode(self::CODE_UPDATE_SHORT_LINK, Loc::getMessage("ERROR_UPDATE_SHORT_LINK"));
    }

    public function errorAdd(): void
    {
        $this->setErrorByCode(self::CODE_ADD_SHORT_LINK, Loc::getMessage("ERROR_ADD_SHORT_LINK"));
    }

    public function errorNotFoundMethod(): void
    {
        $this->setErrorByCode(self::CODE_ARR_NOT_FOUND_METHOD, Loc::getMessage("ERROR_AJAX_NOT_FOUND_METHOD"));
    }

}

?>