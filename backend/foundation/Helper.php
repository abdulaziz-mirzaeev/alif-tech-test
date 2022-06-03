<?php


namespace App;


class Helper
{
    public static function cleanString(string $string)
    {
        $string = str_replace(' ', '', $string);
        return preg_replace('/[^A-Za-z0-9]/', '', $string);
    }
}