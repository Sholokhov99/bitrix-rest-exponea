<?php

namespace Rest\Exponea\Actions;

use Bitrix\Main\Web\Json;
use Rest\Exponea\Api\Answer as ApiAnswer;

class Answer extends ApiAnswer
{
    /**
     * Создать ответ сервера
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     */
    public function generateAnswer(): string
    {
        $this->endTsScript = microtime();

        $success = $this->emptyError();

        $arAnswer = array(
            "success" => $success,
            "timestamp_start" => $this->startTsScript,
            "timestamp_end" => $this->endTsScript,
            "data" => ($success) ? $this->getAnswerByArray() : $this->getErrorByArray(),
        );

        $this->answer = Json::encode($arAnswer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($this->answer) ? $this->answer : "";
    }
}