<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Rest\Exponea;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/rest.exponea/include.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/rest.exponea/prolog.php");
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/interface/admin_lib.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

CJSCore::Init(["jquery"]);
if (Loader::includeModule('rest.exponea') && Loader::includeModule('iblock')) {
    $request = Application::getInstance()->getContext()->getRequest();

    if ($request->isPost()) {
        $fields = array_keys($request->getPost("fields"));
        \Bitrix\Main\Diag\Debug::dump($fields);
        COption::SetOptionString(Exponea\Application::MODULE_ID, "IBLOCK_ID_".Exponea\Options::EXPONEA_WEBHOOK_SHORTLINK_PRODUCT, serialize($fields));
    }

    $idIblockList = unserialize(
        COption::GetOptionString(Exponea\Application::MODULE_ID, "IBLOCK_ID_".Exponea\Options::EXPONEA_WEBHOOK_SHORTLINK_PRODUCT)
    );

    ?>
    <form method="post">
        <?= bitrix_sessid_post(); ?>
        <?php
        $dbIblock = CIBlock::GetList([], ["ACTIVE" => "Y"]);

        while ($iblock = $dbIblock->Fetch()) {?>
            <input type="checkbox" name="fields[<?=$iblock["ID"]?>]"
                <?= in_array($iblock["ID"], $idIblockList) ? "checked" : "" ?>
                    value="<?=$iblock["ID"]?>"
            >
            <label>
                <?= "[{$iblock['ID']}] {$iblock["NAME"]}"; ?>
            </label>
            <br>
        <?php
        }
        ?>
        <br>
        <button type="submit" name="sub">
            <?= Loc::getMessage("BUTTON_SAVE") ?>
        </button>
    </form>
    <?php
} ?>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
