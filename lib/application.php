<?php

/*
 * Create: sholokhov
 * Date: 20.02.2022
 * Email: sholokhov.daniil@gmail.com
 */

namespace Rest\Exponea;

class Application
{
    public const MODULE_ID = "rest.exponea";

    /**
     * Загрузка необходимых классов
     * @return void
     */
    public static function autoload(): void
    {
        Events::autoload();
    }
}