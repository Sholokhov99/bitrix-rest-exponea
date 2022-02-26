<?php

namespace Rest\Exponea\Api;

use Rest\Exponea\Actions\Answer;
use Rest\Exponea\Logger\Log;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

abstract class Security
{
    /**
     * Уникальный ID обращения пользователя
     */
    private const PROPERTY_LOGGER_ID_OPERATION = "ID_OPERATION";
    /**
     * Дата обращения пользователя
     */
    private const PROPERTY_LOGGER_DATE = "DATE";
    /**
     * Тип обращения пользователя
     */
    private const PROPERTY_LOGGER_TYPE = "TYPE";
    /**
     * Событие обработки rest запроса
     */
    private const PROPERTY_LOGGER_ACTION = "ACTION";
    /**
     * IP посетителя
     */
    private const PROPERTY_LOGGER_IP_CLIENT = "IP CLIENT";
    /**
     * Тело запроса POST
     */
    private const PROPERTY_LOGGER_METHOD_POST = "POST";
    /**
     * Тело запроса GET
     */
    private const PROPERTY_LOGGER_METHOD_GET = "GET";
    /**
     * Ответ от сервера
     */
    private const PROPERTY_LOGGER_ANSWER = "ANSWER_SERVER";
    /**
     * ID посетителя (id пользователя в системе bitrix)
     */
    private const PROPERTY_LOGGER_USER_ID = "USER_ID";
    /**
     * Группа посетителя
     */
    private const PROPERTY_LOGGER_GROUP_LIST = "GROUP_LIST";
    /**
     * POST параметр, который сигнализирует, что идет тест
     */
    private const POST_PROPERTY_TEST_MODE = "test";
    /**
     * Значение тест-параметра
     */
    private const POST_VALUE_PROPERTY_TEST_MODE = "1";
    /**
     * Код группы, которая имеет доступ к API
     */
    private const REST_ACCESS_GROUP = "rest_api";
    /**
     * Наименования лога
     */
    private const FILE_NAME_LOGGER = "GaRestActions";
    /**
     * Префикс в уникальном ID посетителя
     */
    private const PREFIX_OPERATION = "rest_api_";
    /**
     * ID операции посетителя
     * @var string
     */
    protected $idOperation = "";
    /**
     * Режим технической работы (включать строго функцией setTechnicalWork() )
     * @var bool
     */
    protected $technicalWork = false;
    /**
     * Объект контекста
     * @var \Bitrix\Main\HttpRequest|\Bitrix\Main\Request
     */
    protected $request = null;
    /**
     * Объект логера
     * @var Log|null
     */
    protected $logger = null;
    /**
     * @var Answer
     */
    protected static $answer = null;

    public function __construct(string $typeRest = "", string $actionRest = "")
    {
        static::$answer = new Answer();

        $this->idOperation = uniqid(self::PREFIX_OPERATION);
        $this->request = Application::getInstance()->getContext()->getRequest();

        $this->initLogger($typeRest, $actionRest);
    }

    /**
     * Добавление записи в лог
     * @param $answer
     * @return void
     */
    public function logAnswer($answer): void
    {
        if (is_null($this->logger) === false) {
            $dt = new \DateTime();
            $data = array(
                self::PROPERTY_LOGGER_ID_OPERATION => $this->idOperation,
                self::PROPERTY_LOGGER_DATE => $dt->getTimestamp(),
                self::PROPERTY_LOGGER_ANSWER => $answer
            );
            $this->logger->setDataLog($data);
            $this->logger->write();
        }
    }

    /**
     * Проверка доступа к REST API
     * @return bool
     */
    public function checkAccess(): bool
    {
        global $USER;

        $groupsUser = $USER->GetUserGroupArray();

        $dBGroupList = \CGroup::GetList(
            $by = "c_sort",
            $order = "asc",
            array("ID" => $groupsUser, "STRING_ID" => self::REST_ACCESS_GROUP)
        );
        $access = !!$dBGroupList->Fetch();

        if ($access === false) {
            $dataLog = array(
                self::PROPERTY_LOGGER_USER_ID => $USER->GetID(),
                self::PROPERTY_LOGGER_GROUP_LIST => $groupsUser,
                self::PROPERTY_LOGGER_ANSWER => Loc::getMessage("ERROR_ACCESS"),
            );
            $this->logAnswer($dataLog);

            static::$answer->setError(Loc::getMessage("ERROR_ACCESS"));
        }

        return $access;
    }

    /**
     * Проверка на технические работы
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     */
    public function isTechnicalWork(): bool
    {
        if ($this->technicalWork && $this->request->getPost(
                self::POST_PROPERTY_TEST_MODE
            ) !== self::POST_VALUE_PROPERTY_TEST_MODE) {
            static::$answer->setError(Loc::getMessage("INFO_TECHNICAL_WORK"));
            static::$answer->generateAnswer();

            return true;
        }

        return false;
    }

    /**
     * @param string $typeRest
     * @param string $actionRest
     * @return void
     */
    private function initLogger(string $typeRest, string $actionRest): void
    {
        $typeApi = strlen($typeRest) ? $this->request->getPost($typeRest) : "";
        $actionApi = strlen($actionRest) ? $this->request->getPost($actionRest) : "";

        $this->logger = new Log();
        $this->logger->setFileName(self::FILE_NAME_LOGGER . "_" . $typeApi);
        $dt = new \DateTime();
        $data = array(
            self::PROPERTY_LOGGER_ID_OPERATION => $this->idOperation,
            self::PROPERTY_LOGGER_DATE => $dt->getTimestamp(),
            self::PROPERTY_LOGGER_TYPE => $typeApi,
            self::PROPERTY_LOGGER_ACTION => $actionApi,
            self::PROPERTY_LOGGER_IP_CLIENT => $_SERVER["REMOTE_ADDR"],
            self::PROPERTY_LOGGER_METHOD_POST => $this->request->getPostList()->toArray(),
            self::PROPERTY_LOGGER_METHOD_GET => $this->request->getQueryList()->toArray(),
        );
        $this->logger->setDataLog($data);
        $this->logger->write();
    }
}

?>