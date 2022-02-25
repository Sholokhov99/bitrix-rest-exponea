<?php

namespace Ga\Rest\ShortLinks;

use Bitrix\Main\Localization\Loc;
use Ga\Rest\Tools\Http\Tools;

class Ui extends Source
{
    /**
     * @param array $request
     * @param bool $dontCheckSession
     * @return string
     */
    public function isAjax(array $request, bool $dontCheckSession = false): string
    {
        $GLOBALS['APPLICATION']->RestartBuffer();

        if (check_bitrix_sessid() || $dontCheckSession) {
            if ($request["action"] == "delete" && is_numeric($request['id'])) {
                $this->deleteElement(intval($request['id']));
            } elseif ($request["action"] == "update" && is_string($request['url']) && is_numeric($request['id'])) {
                $params = (isset($request["params"]) && is_string($request["params"])) ? $request["params"] : "";
                $this->updateElement(intval($request['id']), $request['url'], $params);
            } elseif ($request["action"] == "add" && is_string($request['url'])) {
                $daysLive = (isset($request["days"]) && is_int($request["days"])) ? $request["days"] : 0;
                $params = (isset($request["params"]) && is_string($request["params"])) ? $request["params"] : "";
                $success = $this->addElement($request['url'], $params, $daysLive);
                $this->setAnswerFromArrKeyByInt("id", $success);
            } elseif ($request["action"] == "get_list_links") {
                return $this->getListLinks();
            } else {
                $this->errorNotFoundMethod();
            }
        } else {
            $this->errorAccess();
        }

        $this->generateAnswer();
        $json = $this->getAnswer();
        $this->clearAnswer();

        return $json;
    }

    /**
     * @return string|null
     */
    public function getContent(): string
    {
        global $USER;
        if ($this->checkAccess()) {
            $content = $this->getListLinks();
        } elseif (intval($USER->GetID()) === 0) {
            $content = $this->getAuthForm();
        } else {
            $content = Loc::getMessage("ERROR_ACCESS");
        }

        return is_string($content) ? $content : "";
    }

    /**
     * Вернуть HTML страницы с списком ссылок
     * @return string
     */
    protected function getListLinks(): string
    {
        global $arFilterList, $USER, $APPLICATION;
        ob_start();
        if ($this->isAdmin() === false) {
            $arFilterList = array(
                "CREATED_BY" => $USER->GetID()
            );
        }

        $APPLICATION->IncludeComponent(
            "bitrix:news.list",
            "shortlinks",
            array(
                "ACTIVE_DATE_FORMAT" => "d.m.Y",
                "ADD_SECTIONS_CHAIN" => "Y",
                "AJAX_MODE" => "N",
                "AJAX_OPTION_ADDITIONAL" => "",
                "AJAX_OPTION_HISTORY" => "N",
                "AJAX_OPTION_JUMP" => "N",
                "AJAX_OPTION_STYLE" => "Y",
                "CACHE_FILTER" => "N",
                "CACHE_GROUPS" => "N",
                "CACHE_TIME" => "36000000",
                "CACHE_TYPE" => "N",
                "CHECK_DATES" => "Y",
                "DETAIL_URL" => "",
                "DISPLAY_BOTTOM_PAGER" => "Y",
                "DISPLAY_DATE" => "Y",
                "DISPLAY_NAME" => "Y",
                "DISPLAY_PICTURE" => "Y",
                "DISPLAY_PREVIEW_TEXT" => "Y",
                "DISPLAY_TOP_PAGER" => "N",
                "FIELD_CODE" => array("DATE_CREATE", "CREATED_BY", "CREATED_USER_NAME", "TIMESTAMP_X", ""),
                "FILTER_NAME" => "arFilterList",
                "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                "IBLOCK_ID" => $this->iblockShortLink->getIblockId(),
                "INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
                "INCLUDE_SUBSECTIONS" => "Y",
                "NEWS_COUNT" => "20",
                "PAGER_BASE_LINK_ENABLE" => "N",
                "PAGER_DESC_NUMBERING" => "N",
                "PAGER_DESC_NUMBERING_CACHE_TIME" => "0",
                "PAGER_SHOW_ALL" => "N",
                "PAGER_SHOW_ALWAYS" => "N",
                "PAGER_TEMPLATE" => ".default",
                "PAGER_TITLE" => "",
                "PARENT_SECTION" => "",
                "PARENT_SECTION_CODE" => "",
                "PREVIEW_TRUNCATE_LEN" => "",
                "PROPERTY_CODE" => array("SHORT_LINK", "GET_PARAMS"),
                "SET_BROWSER_TITLE" => "Y",
                "SET_LAST_MODIFIED" => "N",
                "SET_META_DESCRIPTION" => "Y",
                "SET_META_KEYWORDS" => "Y",
                "SET_STATUS_404" => "N",
                "MESSAGE_404" => "",
                "SHOW_404" => "N",
                "SET_TITLE" => "Y",
                "SORT_BY1" => "ACTIVE_FROM",
                "SORT_BY2" => "SORT",
                "SORT_ORDER1" => "DESC",
                "SORT_ORDER2" => "ASC",
                "STRICT_SECTION_CHECK" => "N",
                "ROOT_SHORT_LINK" => Tools::getUrlWeb(self::DIR_SHORT_LINK),
            )
        );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Вернуть форму авторизации
     * @return string
     */
    protected function getAuthForm(): string
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            "bitrix:system.auth.form",
            "popup",
            array(
                "COMPONENT_TEMPLATE" => ".default",
                "REGISTER_URL" => SITE_DIR . "auth/",
                "PROFILE_URL" => SITE_DIR . "profile/",
                "SHOW_ERRORS" => "Y",
                "AJAX_MODE" => "Y",
                "~SHOW_TITLES" => "N",
                'AJAX_OPTION_JUMP' => 'N'
            ),
            false
        );
        $content = ob_get_contents();
        ob_end_clean();

        return is_string($content) ? $content : "";
    }
}