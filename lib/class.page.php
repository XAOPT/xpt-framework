<?php

class ClassPage
{
    public static $description = '';
    public static $keywords    = '';
    public static $title    = '';

    static function GetTitle()
    {
        return self::$title;
    }

    /**
     * @param string $text
     */
    static function SetTitle($text = '')
    {
        self::$title = $text;
    }

    /**
     * @param string $text
     */
    static function AddToTitle($text = '')
    {
        self::$title = $text . ' - ' . self::$title;
    }

    static function GetDescription()
    {
        $html = '<META name="description" content="'.self::$description.'">';
        return $html;
    }

    static function SetDescription($text = '')
    {
        self::$description = $text;
    }

    static function GetKeywords()
    {
        $html = '<META name="keywords" content="'.self::$keywords.'">';
        return $html;
    }

    static function SetKeywords($text = '')
    {
        self::$keywords = $text;
    }
}

?>