<?php

namespace Ga\Rest\Actions;

use Bitrix\Main\Localization\Loc;
use Ga\Rest\Api\Security;
use Ga\Rest\Tools\Http\ResponseCode;
use Ga\Rest\Api\Interfaces\InterfaceRest;

class Rest extends Security implements InterfaceRest
{
    /**
     * @var string
     */
    protected $keyNameClassAction = "action";
    /**
     * @var string
     */
    protected $keyNameNamespaceAction = "typeAction";
    /**
     * @var string
     */
    protected $nameFunctionStartAction = "start";
    /**
     * Формат ответа сервера
     * @var string
     */
    protected $out = "json";
    /**
     * Стандартный формат даты в ответе
     * @var string
     */
    protected $defaultDateFormat = "d.m.Y H:i:s";
    /**
     * Токен для доступа к rest
     * @var string
     */
    protected $token = "";
    /**
     * Класс обработки rest запроса
     * @var \stdClass
     */
    protected $action = null;

    public function __construct()
    {
        parent::__construct($this->keyNameNamespaceAction, $this->keyNameClassAction);
        $this->checkSecurity();
    }

    /**
     * @return void
     */
    public function route(): void
    {
        $this->setOut((string)$this->request->getPost("out"));

        $namespace = __NAMESPACE__ . '\\' . ucfirst(
                strtolower($this->request->getPost($this->keyNameNamespaceAction))
            ) . '\\' . ucfirst(strtolower($this->request->getPost($this->keyNameClassAction)));

        if (class_exists($namespace) && method_exists($namespace, $this->nameFunctionStartAction)) {
            $this->action = new $namespace;
            $this->action->start();
        } else {
            static::$answer->setError(Loc::getMessage("ERROR_NOT_FOUND_ACTION"));
        }

        $answer = static::$answer->generateAnswer();
        static::$answer->clearAnswer();

        $this->logAnswer($answer);

        echo $answer;
    }

    /**
     * Проверка доступа к api
     * @return bool
     */
    protected function checkToken(): bool
    {
        if($this->request !== null && $this->request->getPost("token") === $this->token) {
            return true;
        } else {
            static::$answer->SetError(Loc::getMessage("ERROR_INVALID_TOKEN"));
            static::$answer->generateAnswer();
            return false;
        }
    }

    /**
     * Приведение даты в необходимый формат
     * @param string $date
     * @param string $format
     * @return string
     */
    protected function getNewDateFormat(string $date, string $format): string
    {
        if (strlen($format) === 0) {
            $format = $this->defaultDateFormat;
        }

        return date_format(date_create($date), $format);
    }

    /**
     * @param string $out
     * @return void
     */
    protected function setOut(string $out): bool
    {
        if (strlen($out)) {
            $this->out = $out;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     */
    private function checkSecurity(): void
    {
        if($this->checkAccess() === false) {
            ResponseCode::setForbiden(static::$answer->generateAnswer());
        }

        if($this->isTechnicalWork()) {
            die(static::$answer->getAnswer());
        }
    }
}