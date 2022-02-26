<?php

namespace Rest\Exponea\Api\Interfaces;

interface InterfaceAnswer
{
    /**
     * Получение ошибок в виде массива
     */
    public function getErrorByArray();

    /**
     * Получение ошибок в виде текста
     */
    public function getErrorText();

    /**
     * Получение ошибки в виде JSON формата
     */
    public function getErrorJson();

    /**
     * Запись ошибки в виде строки
     */
    public function setError(string $msgError);

    /**
     * Запись ошибки в виде массива
     */
    public function setErrorByArray(array $arError);

    /**
     * Проверка на отсутствие ошибок
     */
    public function emptyError();

    /**
     * Очистить ошибки
     */
    public function clearError();

    /**
     * Добавить данные, для формирования ответа
     */
    public function setAnswerFromArrKeyByInt(string $key, int $msgAnswer);
    /**
     * Добавить данные, для формирования ответа
     */
    public function setAnswerFromArrKeyByString(string $key, string $msgAnswer);

    /**
     * Записать информационное сообщение в виде массива
     */
    public function setAnswerByArray(array $data);

    /**
     * Записать информационное сообщение в виде строки
     */
    public function setAnswerByString(string $msgAnswer);

    /**
     * Получить сформированный ответ в виде массива
     */
    public function getAnswerByArray();

    /**
     * Получить сформированный ответ в виде строки
     */
    public function getAnswer();

    /**
     * Очистка ответа сервера
     */
    public function clearAnswer();

    /**
     * Очистка данных для формирования ответа
     */
    public function clearDataAnswer();

}

?>