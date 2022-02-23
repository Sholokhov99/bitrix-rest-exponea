<?php

namespace Ga\Rest\Api;

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
    public function getIblockId(): int
    {
        return intval($this->iblockId);
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
        return $this->getIblockId() === 0;
    }

    /**
     * Получение ID инфоблока
     * @return void
     */
    protected function setIblockId(): void
    {
        $sort = [
            "sort" => "asc"
        ];
        $filter = [
            "ACTIVE" => "Y",
            "CODE" => $this->getIblockCode()
        ];

        if(strlen($filter["CODE"])) {
            $dbIblock = \CIBlock::GetList($sort,$filter);
            $iblock = $dbIblock->Fetch();

            $this->iblockId = intval($iblock["ID"]);
        } else {
            $this->iblockId = 0;
        }
    }
}