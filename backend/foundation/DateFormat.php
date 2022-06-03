<?php


namespace App;


class DateFormat
{
    public static function asDbFormat($date)
    {
        return date_format(date_create($date), 'Y-m-d H:i:s');
    }
}