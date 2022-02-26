<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\SiteTable;

class ga_rest extends CModule
{
    var $MODULE_ID = "ga.rest";
    var $MODULE_NAME;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";
    var $PARTNER_NAME = "Sholokhov";
    var $PARTNER_URI = "https://github.com/Sholokhov99/bitrix-rest-exponea";

    function ga_rest()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        else
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("GA_LOGGER_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("GA_LOGGER_MODULE_DESCRIPTION");
    }

    function DoInstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);
        $this->InstallFiles();
        $this->InstallDB();
        $this->InstallIblock();
        $GLOBALS["errors"] = $this->errors;
        RegisterModule($this->MODULE_ID);

        $this->registerEvents();

        $APPLICATION->IncludeAdminFile(Loc::getMessage("GA_LOGGER_MODULE_INSTALL") . $this->MODULE_ID, $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        UnRegisterModule($this->MODULE_ID);

        $this->unRegisterEvents();

        $APPLICATION->IncludeAdminFile(Loc::getMessage("GA_LOGGER_MODELE_DELETE") . $this->MODULE_ID, $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
    }

    function registerEvents(){

    }
    function unRegisterEvents(){

    }

    function InstallDB(){

    }

    function InstallFiles()
    {
        return true;
    }
    function UninstallFiles()
    {

    }

    function GetModuleRightList()
    {
        $arr = array(
            "reference_id" => array("D","R","W"),
            "reference" => array(
                "[D] ".GetMessage("REL_DENIED"),
                "[R] ".GetMessage("REL_VIEW"),
                "[W] ".GetMessage("REL_ADMIN"))
        );
        return $arr;
    }

    function InstallIblock()
    {

    }
    function UnInstallEvents()
    {

    }
}