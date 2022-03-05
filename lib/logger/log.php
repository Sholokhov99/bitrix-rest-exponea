<?php

namespace Rest\Exponea\Logger;

use Rest\Exponea\Application;

class Log
{
    /**
     * Данные, для записи в лог
     * @var array|null
     */
    protected $dataLog;
    /**
     * Название лог файла
     * @var string|null
     */
    protected $filename;

    /**
     * Путь, где хранятся лог записи
     * @return string|null
     */
    public function getPath(): ?string
    {
        $path = $this->getPathLogs();
        if (is_string($path)) {
            return $path . $this->filename . date("Y.m.d") . ".log";
        } else {
            return null;
        }
    }

    /**
     * Файл, для записи логов
     * @param string $fileName
     * @return bool
     */
    public function setFileName(string $fileName): bool
    {
        if (strlen($fileName)) {
            $this->filename = $fileName;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Добавление записи
     * @param mixed $data
     * @return void
     */
    public function addDataLog($data): void
    {
        $this->dataLog[] = $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setDataLog(array $data): void
    {
        $this->dataLog = $data;
    }

    /**
     * Получение данных для лог записи
     * @return array|null
     */
    public function getDataLog(): ?array
    {
        if (is_array($this->dataLog) === false && is_null($this->dataLog) === false) {
            $this->dataLog = null;
        }

        return $this->dataLog;
    }

    /**
     * Очистка лог данных
     * @return void
     */
    public function clearDataLog(): void
    {
        $this->dataLog = array();
    }

    /**
     * Создание лог записи
     * @return bool
     */
    public function write(): bool
    {
        $file = $this->getPath();
        $content = "";
        if (is_null($file)) {
            return false;
        }

        $content .= date("Y.m.d H:i:s") . " " . print_r($this->getDataLog(), true) . PHP_EOL;

        $this->clearDataLog();

        return (bool)file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Путь до лог записи
     * @return string|null
     */
    protected function getPathLogs(): string
    {
        if (defined(Application::class."::MODULE_ID")) {
            return $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . Application::MODULE_ID . "/logs/";
        } else {
            $path = __DIR__ . "/log/";
            if (file_exists($path) === false) {
                if (mkdir($path) === false) {
                    return __DIR__;
                }
            }
            return __DIR__ . $path;
        }
    }
}

?>