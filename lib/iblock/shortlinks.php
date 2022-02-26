<?php

namespace Rest\Exponea\Iblock;

use Rest\Exponea\Api\Iblock;
use Rest\Exponea\Tools\Http\Tools;
use Rest\Exponea\ShortLinks\Source;
use Bitrix\Main\Type\DateTime;

class ShortLinks extends Iblock
{
    /**
     * @var string
     */
    protected $iblockCode = "short_link";
    /**
     *
     */
    private const PROPERTY_COUNT_VIEWS = "COUNT_VIEWS";
    /**
     *
     */
    private const PROPERTY_GET_PARAMS = "GET_PARAMS";

    /**
     * Проверка на дубли url у пользователя
     * @param string $url
     * @param string $params
     * @return bool
     */
    public function isDuplicationUrl(string $url, string $params): bool
    {
        if ($this->isModuleReady() === false || $this->iblockIdEmpty()) {
            return false;
        }
        global $USER;

        $dbElements = \CIBlockElement::GetList(
            array(),
            array(
                "IBLOCK_ID" => $this->getIblockId(),
                "NAME" => $this->getTransformUrl($url),
                "PROPERTY_" . self::PROPERTY_GET_PARAMS => $this->getTransformUrl($params),
                "CREATED_BY" => $USER->GetID(),
                "ACTIVE" => "Y",
            ),
            array()
        );

        return !!$dbElements;
    }

    /**
     * Проверка, что элемент принадлежит пользователю
     * @param int $id
     * @return bool
     */
    public function isElementByUser(int $id): bool
    {
        if ($this->isModuleReady()) {
            global $USER;

            $dbElement = \CIBlockElement::GetByID($id);
            if ($element = $dbElement->Fetch()) {
                return ($element["CREATED_BY"] == $USER->GetID());
            }
        }

        return false;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $source = new Source();

        if (($this->isElementByUser($id) || $source->isAdmin()) && $this->isModuleReady()) {
            #Удаление ссылки
            return \CIBlockElement::Delete($id);
        } else {
            return false;
        }
    }

    /**
     * @param int $id
     * @param string $url
     * @param string $params
     * @return bool
     */
    public function update(int $id, string $url, string $params): bool
    {
        $source = new Source();
        if ($this->isElementByUser($id) || $source->isAdmin() && $this->isModuleReady() && $this->iblockIdEmpty() === false) {
            $el = new \CIBlockElement();
            $updateStatus = $el->Update($id, array("NAME" => $this->getTransformUrl($url)));
            if ($updateStatus) {
                \CIBlockElement::SetPropertyValuesEx(
                    $id,
                    $this->getIblockId(),
                    array(
                        self::PROPERTY_GET_PARAMS => $this->getTransformUrl($params)
                    ),
                );
            }
            return $updateStatus;
        } else {
            return false;
        }
    }

    /**
     * @param string $url
     * @param string $shortLink
     * @param int $daysLive
     * @param string $params
     * @return int
     */
    public function add(string $url, string $shortLink, int $daysLive = 0, string $params = ""): int
    {
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false) {
            global $USER;
            $fields = array(
                "IBLOCK_ID" => $this->getIblockId(),
                "NAME" => $this->getTransformUrl($url),
                "ACTIVE" => "Y",
                "IBLOCK_SECTION_ID" => $USER->GetID(),
                "CODE" => $shortLink,
                "DATE_ACTIVE_TO" => Source::generateDateDieLink($daysLive),
                self::PROPERTY_COUNT_VIEWS => "0",
                "PROPERTY_VALUES" => array(
                    self::PROPERTY_GET_PARAMS => $this->getTransformUrl($params)
                ),
            );

            $el = new \CIBlockElement();
            return $el->Add($fields);
        }

        return false;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isDuplicationShortLink(string $code): bool
    {
        if ($this->isModuleReady() === false) {
            return false;
        }

        $dbElements = \CIBlockElement::GetList(
            array(),
            array(
                "IBLOCK_ID" => $this->getIblockId(),
                "CODE" => $code,
            ),
            array()
        );

        return !!$dbElements;
    }

    /**
     * @param string $code
     * @return string
     */
    public function getUrl(string $code): string
    {
        $url = "";
        if ($this->isModuleReady() && strlen($code) && $this->iblockIdEmpty() === false) {
            $dbElements = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "ACTIVE" => "Y",
                    "CODE" => $code
                ),
                false,
                false,
                array("NAME", "ID", "PROPERTY_" . self::PROPERTY_COUNT_VIEWS, "PROPERTY_" . self::PROPERTY_GET_PARAMS)
            );
            if ($element = $dbElements->Fetch()) {
                $this->updateCounter(
                    intval($element['ID']),
                    intval($element["PROPERTY_" . self::PROPERTY_COUNT_VIEWS . "_VALUE"])
                );
                $getParams = (strlen(
                    $element["PROPERTY_" . self::PROPERTY_GET_PARAMS . "_VALUE"]
                )) ? "?" . $element["PROPERTY_" . self::PROPERTY_GET_PARAMS . "_VALUE"] : "";
                $url = $element["NAME"] . "/" . $getParams;
            }
        }

        return $url;
    }

    /**
     * @return array
     */
    public function deleteDieLink(): array
    {
        $idlist = array();
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false) {
            $dbElement = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "!ACTIVE_DATE" => "Y",
                    false,
                    false,
                    array("ID")
                )
            );

            while ($element = $dbElement->Fetch()) {
                array_push($idlist, $element['ID']);
                \CIBlockElement::Delete($element["ID"]);
            }
        }
        return $idlist;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getUrlFromId(int $id): array
    {
        $arrUrl = array();
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false && $id) {
            $dbElement = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "ID" => $id,
                ),
                false,
                false,
                array("ID", "CODE", "DATE_ACTIVE_TO", "DATE_CREATE")
            );

            if ($element = $dbElement->Fetch()) {
                $arrUrl = $element;
            }
        }
        return is_array($arrUrl) ? $arrUrl : array();
    }

    /**
     * @param string $code
     * @return string
     */
    public function getShortLinkFromCode(string $code): string
    {
        $url = "";
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false && strlen($code)) {
            $dbElements = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "ACTIVE" => "Y",
                    "CODE" => $code
                ),
                false,
                false,
                array("CODE", "ID", "PROPERTY_" . self::PROPERTY_GET_PARAMS)
            );

            if ($element = $dbElements->Fetch()) {
                $params = ($element["PROPERTY_" . self::PROPERTY_GET_PARAMS . "_VALUE"]) ? "/?" . $element["PROPERTY_" . self::PROPERTY_GET_PARAMS . "_VALUE"] : "/";
                $url = Tools::getUrlWeb(Source::DIR_SHORT_LINK) . "/" . $element["CODE"] . $params;
            }
        }

        return $url;
    }

    /**
     * @param string $url
     * @param string $getParams
     * @return int
     */
    public function getIdShortLinkUserFromUrl(string $url, string $getParams): int
    {
        $idShortLink = 0;
        if ($this->isModuleReady() && $this->iblockIdEmpty() === false && strlen($url)) {
            $dbElements = \CIBlockElement::GetList(
                array(),
                array(
                    "IBLOCK_ID" => $this->getIblockId(),
                    "ACTIVE" => "Y",
                    "NAME" => $this->getTransformUrl($url),
                    "PROPERTY_" . self::PROPERTY_GET_PARAMS => $this->getTransformUrl($getParams),
                ),
                false,
                false,
                array("ID")
            );
            if ($element = $dbElements->Fetch()) {
                $idShortLink = $element["ID"];
            }
        }

        return $idShortLink;
    }

    /**
     * Обновление времени смерти короткой ссылки
     *
     * @param int $id
     * @param string $date
     * @return bool
     */
    public function updateDieTime(int $id, string $date): bool
    {
        if ($this->isModuleReady() && $id && DateTime::isCorrect($date, "d.m.Y H:i:s")) {
            $el = new \CIBlockElement();
            return $el->Update(
                $id,
                array("DATE_ACTIVE_TO" => $date)
            );
        }
        return false;
    }


    /**
     * @param string $url
     * @return string
     */
    private function getTransformUrl(string $url): string
    {
        return htmlspecialchars(str_replace(" ", "", $url));
    }

    /**
     * @param int $id
     * @param int|null $count
     * @return void
     */
    private function updateCounter(int $id, ?int $count): void
    {
        if($this->isModuleReady() && $this->iblockIdEmpty() === false) {
            if (is_numeric($count)) {
                $count = intval($count);
            } else {
                $count = 0;
            }
            \CIBlockElement::SetPropertyValuesEx(
                $id,
                $this->getIblockId(),
                array(
                    self::PROPERTY_COUNT_VIEWS => $count + 1
                )
            );
        }
    }
}

?>