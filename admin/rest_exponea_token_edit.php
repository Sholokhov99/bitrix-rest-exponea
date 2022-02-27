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

if (Loader::includeModule('rest.exponea')) {
    $request = Application::getInstance()->getContext()->getRequest();

    if ($request->isPost()) {
        $msg = Exponea\Options::saveAjax($request);
        if (strlen($msg)) {
            # Тут вывод ошибки
            CAdminMessage::ShowMessage($msg);
        } else {
            # Успешные изменения
            CAdminMessage::ShowNote(Loc::getMessage("TOKEN_SAVE_OK"));
        }
    } ?>
    <form method="post">
        <?php
        echo bitrix_sessid_post() ?>
        <label>
            <?= Loc::getMessage("TOKEN_LABEL_EXPONEA_TOKEN") ?>
        </label>
        <br>
        <input type="text"
               name="fileds[<?= Exponea\Options::EXPONEA_TOKEN ?>]"
               value="<?= COption::GetOptionString(Exponea\Application::MODULE_ID, Exponea\Options::EXPONEA_TOKEN) ?>"
        />
        <br>
        <br>
        <label>
            <?= Loc::getMessage("TOKEN_LABEL_REST_TOKEN") ?>
        </label>
        <br>
        <input type="text"
               name="fileds[<?= Exponea\Options::EXPONEA_WEBHOOK ?>]"
               value="<?= COption::GetOptionString(Exponea\Application::MODULE_ID, Exponea\Options::EXPONEA_WEBHOOK) ?>"
        />
        <br>
        <br>
        <button type="submit" name="sub">
            <?= Loc::getMessage("TOKEN_BUTTON_SAVE") ?>
        </button>
    </form>
    <?php
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
