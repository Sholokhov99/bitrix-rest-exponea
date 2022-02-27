<?php

namespace Rest\Exponea\Api\Interfaces;

interface InterfaceAction
{
    /**
     * Валидация webhook
     * @return bool
     */
    public function validateWebhook(): bool;

    /**
     * Точка запуска обработчикавебхука
     * @return array
     */
    public function start(): bool;
}
?>