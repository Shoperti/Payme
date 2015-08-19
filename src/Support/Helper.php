<?php

namespace Shoperti\PayMe\Support;

class Helper
{
    /**
     * Remove all accents from string.
     *
     * @param string
     *
     * @return mixed
     */
    public static function cleanAccents($string)
    {
        $notAllowed = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','À','Ã','Ì','Ò','Ù','Ã™','Ã ','Ã¨','Ã¬','Ã²','Ã¹','ç','Ç','Ã¢','ê','Ã®','Ã´','Ã»','Ã‚','ÃŠ','ÃŽ','Ã”','Ã›','ü','Ã¶','Ã–','Ã¯','Ã¤','«','Ò','Ã','Ã„','Ã‹'];
        $allowed = ['a','e','i','o','u','A','E','I','O','U','n','N','A','E','I','O','U','a','e','i','o','u','c','C','a','e','i','o','u','A','E','I','O','U','u','o','O','i','a','e','U','I','A','E'];
        $text = str_replace($notAllowed, $allowed, $string);

        return $text;
    }
}
