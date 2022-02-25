<?php

namespace Ga\Rest\ShortLinks;

use Ga\Rest\Iblock\CountersShortLinks;
use Ga\Rest\Iblock\ShortLinks;
use Bitrix\Main\Localization\Loc;

class Source extends Errors
{
    protected $iblockShortLink;
    /**
     * Текст ответа для ui
     * @var string
     */
    protected static $textAnswer = "";

    public const DIR_SHORT_LINK = "sl";
    /**
     * Символы используемые для формирования коротких ссылок
     */
    protected const CHARS_CODE_FROM_LINK = "abcdefghijklmnopqrstuvwxyz0123456789";
    /**
     * Номер группы доступная, для просмотра страницы
     */
    protected const OPEN_ACCESS_GROUP_CODE = "shortlink";
    /**
     * Номер группы администраторов
     */
    protected const ADMIN_ACCESS_GROUP_CODE = "admin_shortlinks";
    /**
     * Максимальное количество попыток генерации пароля
     */
    protected const MAX_COUNT_GENERATE_CODE = 1000;
    /**
     * Процент занятых ссылок из всего количества
     */
    protected const PERENT_BUSY_LINKS = 0.8;
    /**
     * Время жизниссылки в днях
     */
    protected const LIFE_LENGTH_IN_DAY_LINK = 0;
    /**
     * Минимальная длина короткой ссылки
     */
    protected const MIN_LENGTH_SHORT_LINK = 1;
    /**
     * Максимальная длина короткой ссылки, если 0, то нет ограничений
     */
    protected const MAX_LENGTH_SHORT_LINK = 0;

    public function __construct()
    {
        $this->iblockShortLink = new ShortLinks();
        $this->countersShortLinks = new CountersShortLinks();
    }
    
    /**
     * @param string $code
     * @return string
     */
    public static function getCurDirToShortLink(string $code): string
    {
        return self::DIR_SHORT_LINK . "/{$code}/";
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->checkAccess(self::ADMIN_ACCESS_GROUP_CODE);
    }

    /**
     * @param int $length
     * @return int
     */
    public function getCombinations(int $length): int
    {
        $n = strlen(self::CHARS_CODE_FROM_LINK);
        $m = $length;

        if ($n > $m) {
            return intval((gmp_fact($n) / ($m * gmp_fact(($n - $m)))));
        }

        return 0;
    }

    /**
     * @param int $days
     * @return string
     */
    public static function generateDateDieLink(int $days = 0): string
    {
        $date = new \DateTime();

        if ($days <= 0) {
            $days = self::LIFE_LENGTH_IN_DAY_LINK;
        }

        $date->add(new \DateInterval("P{$days}D"));
        return \ConvertDateTime($date->format("d.m.Y H:i:s"));
    }

    /**
     * Перенаправление на внешнюю ссылку
     * @return string
     */
    public function redirect()
    {
        $code = $this->getCodeShortLinkFromUrl();
        $url = $this->iblockShortLink->getUrl($code);

        if (strlen($url) === 0 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            $url = "/";
        }

        return $url;
    }

    /**
     * Проверка доступа к странице
     * @param string $groupCode
     * @return bool
     */
    public function checkAccess(string $groupCode = ""): bool
    {
        global $USER;
        $groupCode = (strlen($groupCode) === 0) ? self::OPEN_ACCESS_GROUP_CODE : $groupCode;
        $groupsUser = $USER->GetUserGroupArray();

        $filter = array(
            "ID" => implode("|", $groupsUser),
            "STRING_ID" => $groupCode
        );

        $dBGroupList = \CGroup::GetList($by = "c_sort", $order = "asc", $filter);

        return !!($dBGroupList->Fetch());
    }

    /**
     * Генерация простой короткой ссылки
     * @return string
     */
    protected function generateShortLink(): string
    {
        $length = self::MIN_LENGTH_SHORT_LINK;
        $index = 0;

        if (self::MAX_LENGTH_SHORT_LINK === 0) {
            $newLength = true;
            $counterLinks = $this->countersShortLinks->getCountLinksList();

            foreach ($counterLinks as $key => $arParams) {
                if (intval($arParams["LENGTH"] < $length)) {
                    break;
                } else {
                    $length = intval($arParams["LENGTH"]);
                }

                if ($this->isCounterLinkFree(intval($arParams["COUNT"]), $length)) {
                    $newLength = false;
                    break;
                }
            }

            if ($newLength) {
                $length++;
            }
        }

        do {
            $code = randString($length, array(self::CHARS_CODE_FROM_LINK,));
            $index++;
        } while ($this->iblockShortLink->isDuplicationShortLink($code) || $index == self::MAX_COUNT_GENERATE_CODE);

        return $code;
    }

    /**
     * Удалить ссылку
     * @param int $elementId
     * @return bool
     */
    protected function deleteElement(int $elementId): bool
    {
        if ($this->checkAccess()) {
            $result = $this->iblockShortLink->delete($elementId);

            if ($result === false) {
                $this->errorDelete();
            } else {
                $this->setAnswerByString(Loc::getMessage("SUCCESS_DELETE_SHORT_LINK"));
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Обновить ссылку
     * @param int $elementId
     * @param string $url
     * @param string $params
     * @return bool
     */
    protected function updateElement(int $elementId, string $url, string $params): bool
    {
        if ($this->checkAccess()) {
            $result = $this->iblockShortLink->update($elementId, $url, $params);

            if ($result === false) {
                $this->errorUpdate();
            } else {
                $this->setAnswerByString(Loc::getMessage("SUCCESS_UPDATE_SHORT_LINK"));
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Добавить новую ссылку
     * @param string $url
     * @param string $params
     * @param int $daysLive
     * @return int
     */
    protected function addElement(string $url, string $params, int $daysLive = 0): int
    {
        $result = 0;
        if ($this->checkAccess()) {
            if ($this->iblockShortLink->isDuplicationUrl($url, $params) === false) {
                $shortLinks = $this->generateShortLink();
                $result = $this->iblockShortLink->add($url, $shortLinks, $daysLive, $params);

                if ($result <= 0) {
                    $this->errorAdd();
                } else {
                    $this->setAnswerByString(Loc::getMessage("SUCCESS_ADD_SHORT_LINK"));
                }
            } else {
                $this->errorDublicateUrl();
            }
        } else {
            $this->errorAccess();
        }

        return $result;
    }

    /**
     * Проверка доступа к определенной ссылке
     * @param int $createdId
     * @return bool
     */
    protected static function checkAccessElementLink(int $createdId): bool
    {
        global $USER;
        return ($USER->GetID() === $createdId || self::isAdmin());
    }

    /**
     * Получение кода короткой ссылки из URL
     * @return string
     */
    private function getCodeShortLinkFromUrl(): string
    {
        global $APPLICATION;
        $url = $APPLICATION->GetCurPage();
        $code = str_replace(array(self::DIR_SHORT_LINK . "/", "/"), "", $url);

        return (is_string($code)) ? $code : "";
    }

    /**
     * Получение доступности, для создания короткой ссылки с определенной длиной
     * @param int $count
     * @param int $length
     * @return bool
     */
    private function isCounterLinkFree(int $count, int $length): bool
    {
        $combinations = $this->getCombinations($length);

        return $combinations * self::PERENT_BUSY_LINKS > $count;
    }
}

?>