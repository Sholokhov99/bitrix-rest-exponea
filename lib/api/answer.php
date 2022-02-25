<?php

namespace Ga\Rest\Api;

use Bitrix\Main\Web\Json;
use Ga\Rest\Tools\Convert;
use Ga\Rest\Api\Interfaces\InterfaceAnswer;

abstract class Answer implements InterfaceAnswer
{
    /**
     * Массив с ошибками
     * @var array
     */
    private $error = array();
    /**
     * Массив с данными, для ответа сервера
     * @var array
     */
    private $dataAnswer = array();
    /**
     * Строка ответа
     * @var string
     */
    protected $answer = "";
    /**
     * Дата время начала обработки запроса в системе UNIX
     * @var int
     */
    protected $startTsScript = 0;
    /**
     * Время конца обработки запроса в системе UNIX
     * @var int
     */
    protected $endTsScript = 0;

    public function __construct()
    {
        $this->startTsScript = microtime();
    }

    /**
     * Создать ответ сервера
     * @return string
     */
    abstract protected function generateAnswer(): string;

    /**
     * @return array
     */
    public function getErrorByArray(): array
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getErrorText(): string
    {
        return Convert::arrayToString($this->error);
    }

    /**
     * @return string
     */
    public function getErrorJson(): string
    {
        return Json::encode($this->error);
    }

    /**
     * @return void
     */
    public function setError(string $msgError): void
    {
        if (strlen(trim($msgError))) {
            $this->error[] = $msgError;
        }
    }

    /**
     * @return void
     */
    public function setErrorByArray(array $collError): void
    {
        if (count($collError)) {
            $this->error[] = $collError;
        }
    }

    /**
     * @return bool
     */
    public function emptyError(): bool
    {
        return (count($this->error) === 0);
    }

    /**
     * @return void
     */
    public function clearError(): void
    {
        $this->error = array();
    }

    /**
     * @return void
     */
    public function setAnswerFromArrKeyByInt(string $key, int $msgAnswer): void
    {
        $this->dataAnswer[$key] = $msgAnswer;
    }

    /**
     * @return void
     */
    public function setAnswerFromArrKeyByString(string $key, string $msgAnswer): void
    {
        $this->dataAnswer[$key] = $msgAnswer;
    }

    /**
     * @return void
     */
    public function setAnswerByArray(array $data): void
    {
        if (count($data)) {
            $this->dataAnswer[] = $data;
        }
    }

    /**
     * @return void
     */
    public function setAnswerByString(string $msgAnswer): void
    {
        if (strlen(trim($msgAnswer))) {
            $this->dataAnswer[] = $msgAnswer;
        }
    }

    /**
     * @return array
     */
    public function getAnswerByArray(): array
    {
        return $this->dataAnswer;
    }

    /**
     * @return string
     */
    public function getAnswer(): string
    {
        return $this->answer;
    }

    /**
     * @return void
     */
    public function clearAnswer(): void
    {
        $this->answer = "";
    }

    /**
     * @return void
     */
    public function clearDataAnswer(): void
    {
        $this->dataAnswer = array();
    }

}