<?php

namespace Ga\Rest\Actions\Exponea;

use Bitrix\Main\Localization\Loc;
use Ga\Rest\Actions\Rest;
use Ga\Rest\Api\Interfaces\InterfaceAction;
use Ga\Rest\Iblock\Models;
use Ga\Rest\Iblock\ShortLinks;
use Ga\Rest\ShortLinks\Errors;
use Ga\Rest\ShortLinks\Source as ShortLinkSource;
use Ga\Rest\ShortLinks\Ui;
use Bitrix\Main\Web\Json;
use Ga\Rest\Tools\Http\Tools;

class CustomLink_AddHL extends Rest implements InterfaceAction
{
    /**
     * Масив с данными о продукте
     */
    protected const PROPERTY_PRODUCT_CODE = "PRODUCT";
    /**
     * Массив с данными о кастомной ссылке
     */
    protected const PROPERTY_CUSTOM_URL_CODE = "CUSTOM_URL";
    /**
     * Массив с полями полученного POST запроса
     */
    protected const PROPERTY_FIELDS_CODE = "arFields";
    /**
     * Поле с URL, который необходимо сократить
     */
    protected const PROPERTY_URL_CODE = "URL";
    /**
     * Поле с UTM метками, которые необходимо подставить к короткой ссылке
     */
    protected const PROPERTY_UTM_CODE = "UTM";
    /**
     * Поле с данными о длине жизни короткой ссылке
     */
    protected const PROPERTY_DAYS_CODE = "DAYS";
    /**
     * Поле с данными о формате данных в ответе
     */
    protected const PROPERTY_FORMAT_CODE = "FORMAT";
    /**
     * Массив с данными о жизни короткой ссылки
     */
    protected const PROPERTY_TIME_CODE = "TIME";
    /**
     * Массив с всеми данными запроса
     */
    protected const PROPERTY_ARG_CODE = "arg";
    /**
     * Ссылка на каталог сайта
     */
    protected const CATALOG_DIR = "cars";
    /**
     * Токен webhook
     * @var string
     */
    protected $token = "token";
    /**
     * ID созданной короткой ссылки
     * @var int
     */
    protected $idShortLink = 0;
    /**
     * @var string
     */
    protected $utm = "";
    /**
     * @var object
     */
    protected $time = null;
    /**
     * @var object
     */
    protected $arg = null;
    /**
     * Тип формирования ссылки
     * @var string
     */
    protected $typeLink = "";
    /**
     * Массив входных данных
     * @var array|null
     */
    protected $collectionData;
    /**
     * @var array
     */
    protected $collectionLongUrl = array();
    /**
     * @var ShortLinks
     */
    protected $iblockShortLink;

    public function __construct()
    {
        parent::__construct();
        $this->iblockShortLink = new ShortLinks();
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public function validateWebhook(): bool
    {
        $this->arg = $this->request->getPost(self::PROPERTY_ARG_CODE);

        if ($this->request->getPost(self::PROPERTY_ARG_CODE) === null) {
            static::$answer->setError(Loc::getMessage("ERROR_TYPE_FORMAT"));
        } else {
            $this->arg = $this->request->getPost(self::PROPERTY_ARG_CODE);
        }

        # Валидация продукта
        if (isset($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_PRODUCT_CODE])) {
            $this->validateProductWebhook();
        } elseif ($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_CUSTOM_URL_CODE]) {
            $this->validateCustomUrlWebhook();
        } else {
            static::$answer->setError(Loc::getMessage("ERROR_TYPE_FORMAT"));
        }

        # Валидаци времени
        $this->validateTimeWebhook();

        if (static::$answer->emptyError()) {
            return true;
        } else {
            $this->collectionData = null;

            return false;
        }
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public function start(): bool
    {
        if ($this->checkToken() && $this->validateWebhook()) {
            if ($this->typeLink === self::PROPERTY_PRODUCT_CODE) {
                $this->createProductLink();
            } else {
                $this->createCustomLink();
            }

            if (is_array($this->collectionLongUrl) === false || $this->validationLongUrl() === false) {
                return false;
            }

            return $this->createNewUrl();
        }
        return false;
    }

    /**
     * Валидация запроса на создание кастомного url
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function validateCustomUrlWebhook(): bool
    {
        $success = true;

        $this->typeLink = self::PROPERTY_CUSTOM_URL_CODE;
        $this->collectionData = Json::decode($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_CUSTOM_URL_CODE]);

        # Проверка на соответствие типа
        if (is_array($this->collectionData) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_EMPTY_PRODUCT_ID"));
            $success = false;
        }

        # Валидация кастомной ссылки
        if (isset($this->collectionData[self::PROPERTY_URL_CODE]) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_CUSTOM_LINK_URL_NOT_FOUND"));
            $success = false;
        } elseif (is_string($this->collectionData[self::PROPERTY_URL_CODE]) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_CUSTOM_LINK_URL_ERROR_DATATYPE"));
            $success = false;
        }

        # Валидация UTM
        if (isset($this->collectionData[self::PROPERTY_UTM_CODE]) && is_string(
                $this->collectionData[self::PROPERTY_UTM_CODE]
            ) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_CUSTOM_LINK_UTM_ERROR_DATATYPE"));
            $success = false;
        } else {
            $this->utm = $this->collectionData[self::PROPERTY_UTM_CODE];
        }

        return $success;
    }

    /**
     * Валидация времени жизни ссылки
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function validateTimeWebhook(): void
    {
        if (isset($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_TIME_CODE])) {
            $this->time = Json::decode($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_TIME_CODE]);

            if (is_array($this->time)) {
                # Валидация дней жизни
                if (isset($this->time[self::PROPERTY_DAYS_CODE]) && is_numeric(
                        $this->time[self::PROPERTY_DAYS_CODE]
                    ) === false) {
                    $this->time[self::PROPERTY_DAYS_CODE] = 0;
                } else {
                    $this->time[self::PROPERTY_DAYS_CODE] = (int)$this->time[self::PROPERTY_DAYS_CODE];
                }

                # Валидация формата возврата даты
                if (isset($this->time[self::PROPERTY_FORMAT_CODE]) && (is_string(
                            $this->time[self::PROPERTY_FORMAT_CODE]
                        ) === false || strlen(
                            $this->time[self::PROPERTY_FORMAT_CODE]
                        ) === false)) {
                    $this->time[self::PROPERTY_FORMAT_CODE] = $this->defaultDateFormat;
                }
            } else {
                $this->time = $this->getDefaultDataTime();
            }
        } else {
            $this->time = $this->getDefaultDataTime();
        }
    }

    /**
     * Валидация запроса на создание короткой ссылки на основании продукта
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function validateProductWebhook(): bool
    {
        $success = true;

        $this->typeLink = self::PROPERTY_PRODUCT_CODE;
        $this->collectionData = Json::decode($this->arg[self::PROPERTY_FIELDS_CODE][self::PROPERTY_PRODUCT_CODE]);

        # Проверка на соответствие типа данных
        if (is_array($this->collectionData) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_FORMAT_PRODUCT"));
            $success = false;
        }

        # Проверка на наличие ID продукта
        if (array_key_exists("ID", $this->collectionData) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_EMPTY_PRODUCT_ID"));
            $success = false;
        }

        # Проверка на наличие UTM, если есть, то проверить тип данных
        if (isset($this->collectionData[self::PROPERTY_UTM_CODE]) && is_string(
                $this->collectionData[self::PROPERTY_UTM_CODE]
            ) === false) {
            static::$answer->setError(Loc::getMessage("ERROR_CUSTOM_LINK_UTM_ERROR_DATATYPE"));
            $success = false;
        } else {
            $this->utm = $this->collectionData[self::PROPERTY_UTM_CODE];
        }

        return $success;
    }

    /**
     * @param array $resultAddShortLInk
     * @return bool
     */
    protected function validateResultAddNewUrl(array $resultAddShortLInk): bool
    {
        $dataAddShortLink = $resultAddShortLInk["data"];
        $this->idShortLink = $dataAddShortLink["id"];

        if ($resultAddShortLInk["success"] !== true || is_numeric($this->idShortLink) === false) {
            # Проверяем, что у нас только ошибка с дублем (просьба EXPONEA), для выдачи успешного результата
            if (is_array($dataAddShortLink) && $dataAddShortLink[0]["code"] == Errors::CODE_ERROR_NOT_URL) {
                $this->idShortLink = $this->iblockShortLink->getIdShortLinkUserFromUrl(
                    $this->collectionLongUrl[self::PROPERTY_URL_CODE],
                    $this->getUtm()
                );

                if (is_numeric($this->idShortLink) && is_numeric($this->time[self::PROPERTY_DAYS_CODE])) {
                    $this->iblockShortLink->updateDieTime(
                        intval($this->idShortLink),
                        ShortLinkSource::generateDateDieLink(intVal($this->time[self::PROPERTY_DAYS_CODE]))
                    );
                }
            } else {
                $errText = Loc::getMessage("ERROR_CREATE_SHORT_LINK");
                if (is_string($dataAddShortLink) && strlen($dataAddShortLink)) {
                    $errText = $dataAddShortLink;
                } elseif (is_array($dataAddShortLink)) {
                    foreach ($dataAddShortLink as $key => $arParams) {
                        if (is_string($arParams["text"])) {
                            static::$answer->setError($arParams["text"]);
                        }
                    }
                } elseif (is_null($dataAddShortLink)) {
                    static::$answer->setError($errText);
                }

                static::$answer->setError($errText);

                return false;
            }
        }

        return true;
    }

    /**
     * Валидация полученного массива с длинной ссылкой
     * @return bool
     */
    protected function validationLongUrl(): bool
    {
        if (count($this->collectionLongUrl) === 0) {
            static::$answer->setError(Loc::getMessage("ERROR_NOT_FOUND_PRODUCT"));
        } elseif (isset($this->collectionLongUrl[self::PROPERTY_URL_CODE]) === false || is_string(
                $this->collectionLongUrl[self::PROPERTY_URL_CODE]
            ) === false || strlen(
                $this->collectionLongUrl[self::PROPERTY_URL_CODE]
            ) === 0) {
            static::$answer->setError(Loc::getMessage("ERROR_LONG_URL_NOT_FOUND"));
        }

        return static::$answer->emptyError();
    }

    /**
     * Получение конечной ссылки на продукт
     * @return void
     */
    protected function createProductLink(): void
    {
        $models = new Models();
        $arrLongUrl = $models->getDetailUrl(intval($this->collectionData["ID"]));

        $arrLongUrl[self::PROPERTY_URL_CODE] = Tools::getUrlWeb(
                self::CATALOG_DIR
            ) . $arrLongUrl[self::PROPERTY_URL_CODE];

        $this->collectionLongUrl = $arrLongUrl;
    }

    /**
     * Получение конечной кастомной ссылки
     * @return void
     */
    protected function createCustomLink(): void
    {
        $this->collectionLongUrl = array(
            self::PROPERTY_URL_CODE => $this->collectionData[self::PROPERTY_URL_CODE],
            "ID" => 0
        );
    }

    /**
     * Добавление нового URL
     * @return array
     */
    protected function addNewUrl(): array
    {
        # Создание элемента через ajax
        $shortLinkUi = new Ui();

        # Модификация request
        $arrAjax = array(
            "action" => "add",
            "url" => $this->collectionLongUrl[self::PROPERTY_URL_CODE],
            "days" => $this->time[self::PROPERTY_DAYS_CODE],
            "params" => $this->getUtm()
        );

        $resultAjax = $shortLinkUi->isAjax($arrAjax, true);

        # Добавить валидацию и исключение ошибок
        if (strlen($resultAjax) !== 0) {
            $resultAddShortLInk = Json::decode($resultAjax);
            if (is_array($resultAddShortLInk)) {
                return $resultAddShortLInk;
            }
        }

        return array();
    }

    /**
     * Создание короткой ссылки
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function createNewUrl(): bool
    {
        $resultAddShortLInk = $this->addNewUrl();
        if (is_array($resultAddShortLInk) && $this->validateResultAddNewUrl($resultAddShortLInk)) {
            $collectionShortLink = $this->iblockShortLink->getUrlFromId(intval($this->idShortLink));

            $shortLinkCode = ShortLinkSource::getCurDirToShortLink((string)$collectionShortLink["CODE"]);


            static::$answer->setAnswerFromArrKeyByString(
                "short_link",
                $this->iblockShortLink->getShortLinkFromCode((string)$collectionShortLink["CODE"])
            );

            static::$answer->setAnswerFromArrKeyByString(
                "long_link",
                (string)$this->collectionLongUrl[self::PROPERTY_URL_CODE]
            );

            static::$answer->setAnswerFromArrKeyByString(
                "shortlink_code",
                $shortLinkCode
            );

            static::$answer->setAnswerFromArrKeyByInt(
                "id_link",
                intval($this->idShortLink)
            );

            if (is_numeric($this->collectionLongUrl["ID"]) && intval($this->collectionLongUrl["ID"]) > 0) {
                static::$answer->setAnswerFromArrKeyByInt(
                    "id",
                    intval($this->collectionLongUrl["ID"])
                );
            }

            static::$answer->setAnswerFromArrKeyByString(
                "date_active_to",
                $this->getNewDateFormat(
                    (string)$collectionShortLink["DATE_ACTIVE_TO"],
                    $this->time[self::PROPERTY_FORMAT_CODE]
                )
            );

            static::$answer->setAnswerFromArrKeyByString(
                "date_active_from",
                $this->getNewDateFormat(
                    (string)$collectionShortLink["DATE_CREATE"],
                    $this->time[self::PROPERTY_FORMAT_CODE]
                )
            );

            return true;
        }

        return false;
    }

    /**
     * Получение UTM и преобразование в необходимый вид
     * @param array $data
     * @return string
     */
    protected function getUtm(array $data = array()): string
    {
        $utm = str_replace(array("#iitt#"), $data, urldecode($this->utm));
        return is_string($utm) ? $utm : implode("&", $utm);
    }

    /**
     * Получение стандартного формата времени
     * @return array
     */
    protected function getDefaultDataTime(): array
    {
        return array(
            self::PROPERTY_DAYS_CODE => 0,
            self::PROPERTY_FORMAT_CODE => $this->defaultDateFormat
        );
    }

}

?>