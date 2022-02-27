<?php

namespace Rest\Exponea;

use Bitrix\Main\EventManager;

class Events
{
    /**
     *  Пространство имен, для хранения событий модуля IBLOCK
     */
    private const NAMESPACE_IBLOCK = "\\". __NAMESPACE__ . "\\Iblock\\Events";
    private const NAMESPACE_REST_EXPONEA = "\\". __NAMESPACE__ . "\\Actions\\Exponea\\Events";
    /**
     * @var EventManager
     */
    private static $eventManager = null;
    private static $eventsCollection = array();

    /**
     * Автозагрузка всех событий
     * @return void
     */
    public static function autoload(): void
    {
        if(is_null(static::$eventManager)) {
            static::$eventManager = EventManager::getInstance();
            if (is_null(static::$eventManager) === false) {
                static::$eventsCollection = array_merge(static::$eventsCollection, static::iblock());
                static::$eventsCollection = array_merge(static::$eventsCollection, static::rest());

                static::addEvents();
            }

            static::$eventsCollection = array();
        }
        #static::$eventManager = null;
    }

    private static function getNamespace(string $module): string
    {
        switch (strtolower($module)) {
            case "iblock":
                return self::NAMESPACE_IBLOCK;
                break;
            case Application::MODULE_ID:
                return self::NAMESPACE_REST_EXPONEA;
                break;
            default:
                return "";
                break;
        }
    }

    /**
     * @param string $module
     * @param array $data
     * @return void
     */
    private static function addEvents(): void
    {
        foreach (static::$eventsCollection as $module => $eventCollection) {
            foreach ($eventCollection as $eventKey => $eventData) {
                if (strlen($eventData["event"]) && strlen($eventData["namespace"]) && strlen($eventData["function"])) {
                    static::$eventManager->AddEventHandler(
                        $module,
                        $eventData["event"],
                        [
                            static::getNamespace((string)$module) . "\\{$eventData['namespace']}",
                            $eventData["function"],
                        ]
                    );
                }
            }
        }
    }

    /**
     * События с модулем iblock
     * @return array
     */
    private static function iblock(): array
    {
        return [
            "iblock" => [
                [
                    "event" => "OnBeforeIBlockElementDelete",
                    "namespace" => "EditShortLinks",
                    "function" => "deleteLink"
                ],
                [
                    "event" => "OnAfterIBlockElementAdd",
                    "namespace" => "EditShortLinks",
                    "function" => "addShortLink"
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    private static function rest(): array
    {
        return [
            Application::MODULE_ID => [
                [
                    "event" => "OnBeforeAddNewProductShortLink",
                    "namespace" => "ShortLinks",
                    "function" => "OnBeforeAddNewProductShortLink"
                ],
            ]
        ];
    }
}