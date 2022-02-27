<?php

namespace Rest\Exponea\Api;

use Rest\Exponea\Application;
use Rest\Exponea\Options;

abstract class Iblock
{
    /**
     * Символьный код инфоблока
     */
    protected $iblockCode;
    /**
     * ID модуля
     */
    protected const MODULE_ID = "iblock";
    /**
     * ID инфоблока
     * @var int
     */
    protected $iblockId;
    /**
     * Модуль загрузился
     * @var bool
     */
    private $moduleLoad = false;

    public function __construct()
    {
        $this->moduleLoad = \CModule::IncludeModule(self::MODULE_ID);
        $this->setIblockId();
    }

    /**
     * Получение кода инфоблока
     * @return string
     */
    public function getIblockCode(): string
    {
        return (string)$this->iblockCode;
    }

    /**
     * @return int
     */
    public function getIblockId(): array
    {
        return is_array($this->iblockId) ? $this->iblockId : array();
    }

    /**
     * @return bool
     */
    protected function isModuleReady(): bool
    {
        return $this->moduleLoad;
    }

    /**
     * @return bool
     */
    protected function iblockIdEmpty(): bool
    {
        return count($this->getIblockId()) === 0;
    }

    /**
     * Получение ID инфоблока
     * @return void
     */
    protected function setIblockId(): void
    {
        $idIblockFromOption = \COption::GetOptionString(Application::MODULE_ID, "IBLOCK_ID_".$this->iblockCode);

        if($this->iblockCode === Options::EXPONEA_WEBHOOK_SHORTLINK_PRODUCT){
            $this->iblockId = unserialize($idIblockFromOption);
        } else {
            $idIblockFromOption = intval($idIblockFromOption);
            if($idIblockFromOption) {
                $this->iblockId = array($idIblockFromOption);
            } else {
                $sort = [
                    "sort" => "asc"
                ];
                $filter = [
                    "ACTIVE" => "Y",
                    "CODE" => $this->getIblockCode()
                ];

                if (strlen($filter["CODE"])) {
                    $dbIblock = \CIBlock::GetList($sort, $filter);
                    if($iblock = $dbIblock->Fetch()) {
                        $this->iblockId = array(intval($iblock["ID"]));
                    } else {
                        $this->iblockId = array();
                    }
                } else {
                    $this->iblockId = array();
                }
            }
        }
    }
}