<?php

namespace Rest\Exponea\Tools;

class Convert
{
    /**
     * Конвертирование массива в строку
     * @param array $arrData
     * @return string
     */
    public static function arrayToString(array $arrData): string
    {
        foreach ($arrData as $value) {
            if (is_array($value)) {
                $retVal[] = self::arrayToString($value);
            } else {
                $retVal[] = $value;
            }
        }
        return implode(". ", $retVal);
    }
}

?>