<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\SiteTable;
use Bitrix\Main\EventManager;
use Bitrix\Tasks\Internals\DataBase\Tree\Exception\LinkExistsException;

class rest_exponea extends CModule
{
    public $MODULE_ID = "rest.exponea";
    public $MODULE_NAME;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;
    public $MODULE_CSS;
    public $MODULE_GROUP_RIGHTS = "Y";
    public $PARTNER_NAME = "Sholokhov";
    public $PARTNER_URI = "https://github.com/Sholokhov99/bitrix-rest-exponea";
    /**
     * Id типа инфоблока, который необходимо установить
     */
    private const IBLOCK_TYPE = "rest_exponea";
    /**
     * Символьные кода инфоблоков, которые необходимо установить
     */
    private const IBLOCK_CODE = ["short_link", "counts_short_links"];
    /**
     * Файлы, которые необходимо установить
     */
    private const FILES_INSTALL = [
        [
            "form" => '/admin',
            "to" => '/bitrix/admin',
        ],
        [
            'from' => '/js',
            'to' => '/bitrix/js',
        ]
    ];
    /**
     * Список групп пользователей, которые использует модуль
     */
    private const GROUP_LIST = [
        "shortlink" => "Редактор пользовательских ссылок",
        "admin_shortlinks" => "Администратор коротких ссылок",
        "rest_api" => "rest_api"
    ];
    /**
     * ID созданных групп при установке модуля
     * @var array
     */
    private $idGroups = [];
    /**
     * Ошибки при установке модуля
     * @var array
     */
    private $errors;

    /**
     * @return void
     */
    public function rest_exponea()
    {
        $arModuleVersion = array();

        include(__DIR__ . "/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        } else {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("REST_EXPONEA_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("REST_EXPONEA_MODULE_DESCRIPTION");
    }

    /**
     * @return void
     */
    public function DoInstall(): void
    {
        global $APPLICATION;

        if ($this->InstallIblock()) {
            $this->InstallDB();
            $this->InstallFiles();
            $this->InstallUserGroups();
        }

        if ($this->errorEmpty() === false) {
            $APPLICATION->ThrowException($this->getErrorStr());
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("REST_EXPONEA_MODULE_INSTALL") . $this->MODULE_ID,
            __DIR__ . "/step1.php"
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);

        $this->unInstallDB();
        $this->unInstallFiles();
        $this->unInstallUserGroups();
        $this->unInstallUblock();
        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("REST_EXPONEA_MODELE_DELETE") . $this->MODULE_ID,
            __DIR__ . "/unstep1.php"
        );
    }

    /**
     * @return array[]
     */
    private function getEvents(): array
    {
        return [
            [
                'fromModule' => $this->MODULE_ID,
                'event' => 'OnBeforeAddNewProductShortLink',
                'toModule' => $this->MODULE_ID,
                'toClass' => 'ShortLinks',
                'toMethod' => 'OnBeforeAddNewProductShortLink'
            ],
            [
                'fromModule' => $this->MODULE_ID,
                'event' => 'OnBeforeAddNewCustomShortLink',
                'toModule' => $this->MODULE_ID,
                'toClass' => 'ShortLinks',
                'toMethod' => 'OnBeforeAddNewCustomShortLink'
            ],
        ];
    }

    /**
     * @return void
     */
    public function registerEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach ($this->getEvents() as $collectionEvent) {
            if (isset($collectionEvent['fromModule']) && isset($collectionEvent['event'])
                && isset($collectionEvent['toModule']) && isset($collectionEvent['toClass']) && isset($collectionEvent['toMethod'])) {
                $eventManager->registerEventHandlerCompatible(
                    $collectionEvent['fromModule'],
                    $collectionEvent['event'],
                    $collectionEvent['toModule'],
                    $collectionEvent['toClass'],
                    $collectionEvent['toMethod'],
                );
            }
        }
        unset($eventManager);
    }

    /**
     * @return void
     */
    public function unRegisterEvents(): void
    {
        $eventManager = EventManager::getInstance();
        foreach ($this->getEvents() as $collectionEvent) {
            if (isset($collectionEvent['fromModule']) && isset($collectionEvent['event'])
                && isset($collectionEvent['toModule']) && isset($collectionEvent['toClass']) && isset($collectionEvent['toMethod'])) {
                $eventManager->unRegisterEventHandler(
                    $collectionEvent['fromModule'],
                    $collectionEvent['event'],
                    $collectionEvent['toModule'],
                    $collectionEvent['toClass'],
                    $collectionEvent['toMethod'],
                );
            }
        }
        unset($eventManager);
    }

    /**
     * @return bool
     */
    public function installDB(): bool
    {
        $this->registerEvents();
        ModuleManager::registerModule($this->MODULE_ID);
        return true;
    }

    /**
     * @return void
     */
    public function unInstallDB(): void
    {
        $this->unRegisterEvents();
        \COption::RemoveOption($this->MODULE_ID);
    }

    /**
     * @return bool
     */
    public function installFiles(): bool
    {
        if ($_ENV["COMPUTERNAME"] != 'BX') {
            foreach (self::FILES_INSTALL as $collection) {
                if (isset($collection['from']) && isset($collection['to'])) {
                    CopyDirFiles(
                        $this->getPathModule() . $collection['from'],
                        $_SERVER["DOCUMENT_ROOT"] . $collection['to']
                    );
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function uninstallFiles(): bool
    {
        if ($_ENV["COMPUTERNAME"] != 'BX') {
            foreach (self::FILES_INSTALL as $collection) {
                DeleteDirFiles(
                    $this->getPathModule() . $collection['from'],
                    $_SERVER["DOCUMENT_ROOT"] . $collection['to']
                );
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function installIblock(): bool
    {
        global $APPLICATION;

        if (Loader::includeModule("iblock")) {
            $createIblockType = true;

            $filter = ['=CODE' => self::IBLOCK_CODE];
            $dbBlock = \CIBlock::GetList([], $filter);

            while ($iblock = $dbBlock->Fetch()) {
                $this->addError(
                    str_replace("#CODE#", $iblock['CODE'], Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_IBLOCK"))
                );
            }

            $dbIblockType = CIBlockType::GetList([], ["=ID" => self::IBLOCK_TYPE]);
            if (!$dbIblockType->Fetch()) {
                $this->createIblockType();
            }

            if ($this->errorEmpty()) {
                foreach (self::IBLOCK_CODE as $iblockCode) {
                    $iblockSettings = $this->getDataIblockCreate($iblockCode);
                    if (count($iblockSettings) && is_array($iblockSettings["IBLOCK"]) && is_array(
                            $iblockSettings["FIELDS"]
                        )) {
                        if ($this->createIblock($iblockSettings["IBLOCK"], $iblockSettings["FIELDS"]) === false) {
                            $this->addError(Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_IBLOCK_ERROR"));
                        }
                    } else {
                        $this->addError(Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_IBLOCK_ERROR") . "2");
                    }

                    if ($this->errorEmpty() === false) {
                        break;
                    }
                }
            }
        } else {
            $this->addError(Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_IBLOCK_ERROR_MODULE"));
        }

        return $this->errorEmpty();
    }

    /**
     * @return void
     */
    public function unInstallUblock(): void
    {
        $dbIblockType = \CIBlockType::Delete(self::IBLOCK_TYPE);
    }

    /**
     * Создание групп пользователей
     * @return bool
     */
    public function installUserGroups(): bool
    {
        $dbGroup = new \CGroup();
        $groupList = [];
        $fields = ["ACTIVE" => "Y", "C_SORT" => 100];

        $dBGroupList = \CGroup::GetList($by = "c_sort", $order = "asc", ["STRING_ID" => array_keys(self::GROUP_LIST)]);
        while ($group = $dBGroupList->Fetch()) {
            $groupList[] = $group["STRING_ID"];
        }

        foreach (self::GROUP_LIST as $stringId => $name) {
            if (in_array($stringId, $groupList) === false) {
                $fields["STRING_ID"] = $stringId;
                $fields["NAME"] = $name;
                $idGroup = $dbGroup->Add($fields);

                if (strlen($dbGroup->LAST_ERROR) > 0) {
                    foreach ($this->idGroups as $key => $id) {
                        \CGroup::Delete($id);
                        unset($this->idGroups[$key]);
                    }
                    $this->addError(Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_GROUP_ERROR"));
                    return false;
                } else {
                    $this->idGroups[] = $idGroup;
                }
            }
        }

        return $this->errorEmpty();
    }

    /**
     * Удаление созданных групп
     * @return bool
     */
    public function unInstallUserGroups(): bool
    {
        $dBGroupList = \CGroup::GetList($by = "c_sort", $order = "asc", ["STRING_ID" => array_keys(self::GROUP_LIST)]);
        while ($group = $dBGroupList->Fetch()) {
            \CGroup::Delete($group["ID"]);
        }
        return true;
    }

    /**
     * Получение пути до install модуля в ядре
     * @return string
     */
    public function getPathModule(): string
    {
        #return $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/{$this->MODULE_ID}/install";
        return __DIR__ . "/install";
    }

    /**
     * Добавление ошибки
     * @param string $msg
     * @return bool
     */
    private function addError(string $msg): bool
    {
        if (strlen($msg)) {
            $this->errors[] = $msg;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверка на наличие ошибок
     * @return bool
     */
    private function errorEmpty(): bool
    {
        return count($this->errors) === 0 || is_null($this->errors);
    }

    /**
     * Получение ошибок в виде строки
     * @return string
     */
    private function getErrorStr(): string
    {
        $msgError = "";
        if (is_array($this->errors)) {
            $msgError = implode("<br>", $this->errors);
        }

        return $msgError;
    }

    /**
     * Создание типа ИБ
     * @return bool
     */
    private function createIblockType(): bool
    {
        $dbIBlockType = new CIBlockType;
        $fields = [
            "ID" => self::IBLOCK_TYPE,
            "SECTIONS" => "Y",
            "LANG" => [
                "ru" => [
                    "NAME" => "REST_EXPONEA"
                ]
            ]
        ];

        $result = $dbIBlockType->Add($fields);

        if ($result === false) {
            $this->addError(Loc::getMessage("REST_EXPONEA_MODELE_INSTALL_IBLOCK_TYPE_ERROR"));
        }

        return $result;
    }

    /**
     * Создание инфоблока
     * @param string $name
     * @param string $code
     * @return bool
     */
    private function createIblock(array $collectionIblock, array $collectionPropertys): bool
    {
        $iblock = new CIBlock;
        $idIblock = $iblock->Add($collectionIblock);

        # Создание свойств
        if ($idIblock) {
            $dbProperties = CIBlockProperty::GetList([], ["IBLOCK_ID" => $idIblock]);
            if ($dbProperties->SelectedRowsCount() <= 0) {
                $dbIblockProperty = new CIBlockProperty;

                foreach ($collectionPropertys as $data) {
                    $data["IBLOCK_ID"] = $idIblock;
                    if ($dbIblockProperty->Add($data) <= 0) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        \COption::SetOptionInt($this->MODULE_ID, "IBLOCK_ID_{$collectionIblock['CODE']}", $idIblock);
        return true;
    }

    /**
     * Получение данных для создания ИБ
     * @param string $code
     * @return array|array[]
     */
    private function getDataIblockCreate(string $code): array
    {
        $access = ["2" => "R"];
        # Прописать права на Iblock id
        $data = [
            "IBLOCK" => [
                "ACTIVE" => "Y",
                "NAME" => "",
                "CODE" => "",
                "IBLOCK_TYPE_ID" => self::IBLOCK_TYPE,
                "SITE_ID" => $this->getSiteId(), #Написать код, чтобы можно было получить id сайта
                "SORT" => "5",
                "GROUP_ID" => $access,
                "FIELDS" => [
                    "CODE" => [
                        "IS_REQUIRED" => "Y",
                        "DEFAULT_VALUE" => [
                            "UNIQUE" => "Y",
                            "TRANS_LEN" => "255",
                            "TRANS_CASE" => "L",
                        ],
                    ],
                ],
                "LIST_PAGE_URL" => "",
                "SECTION_PAGE_URL" => "",
                "DETAIL_PAGE_URL" => "",
                "INDEX_SECTION" => "N",
                "INDEX_ELEMENT" => "N",
                "VERSION" => 1,
                "SECTION_PROPERTY" => "N",
            ]
        ];
        if ($code === "short_link") {
            $data["IBLOCK"]["NAME"] = "Короткие ссылки";
            $data["IBLOCK"]["CODE"] = $code;
            $data["FIELDS"] = [
                [
                    "NAME" => "Количество просмотров",
                    "ACTIVE" => "Y",
                    "SORT" => 0,
                    "CODE" => "COUNT_VIEWS",
                    "PROPERTY_TYPE" => "S",
                    "ROW_COUNT" => 1,
                    "COL_COUNT" => 60,
                    #"IBLOCK_ID" => $ID,
                    "HINT" => "",
                ],
                [
                    "NAME" => "Get параметры",
                    "ACTIVE" => "Y",
                    "SORT" => 0,
                    "CODE" => "GET_PARAMS",
                    "PROPERTY_TYPE" => "S",
                    "ROW_COUNT" => 1,
                    "COL_COUNT" => 60,
                    #"IBLOCK_ID" => $ID,
                    "HINT" => "",
                ]
            ];
        } elseif ($code === "counts_short_links") {
            $data["IBLOCK"]["NAME"] = "Количесто коротких ссылок";
            $data["IBLOCK"]["CODE"] = $code;
            $data["FIELDS"] = [
                [
                    "NAME" => "Количество",
                    "CODE" => "COUNT_SHORT_LINK",
                    "ACTIVE" => "Y",
                    "SORT" => 0,
                    "PROPERTY_TYPE" => "S",
                    "ROW_COUNT" => 1,
                    "COL_COUNT" => 60,
                    #"IBLOCK_ID" => $ID,
                    "HINT" => "",
                ],
            ];
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * Получение текущего ID сайта
     * @return array
     */
    private function getSiteId(): array
    {
        $siteId = [];
        $dbSites = CSite::GetList($by = "sort", $order = "desc", array("DOMAIN" => $_SERVER['SERVER_NAME']));
        while ($site = $dbSites->Fetch()) {
            $siteId[] = $site["LID"];
        }

        return count($siteId) ? $siteId : array("s1");
    }
}