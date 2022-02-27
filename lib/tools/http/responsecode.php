<?php
namespace Rest\Exponea\Tools\Http;

class ResponseCode
{
    /**
     * @param string $answer
     * @return void
     */
    public static function setForbiden(string $answer = ""): void
    {
        header('HTTP/1.0 403 Forbidden');
        die($answer);
    }
}
?>