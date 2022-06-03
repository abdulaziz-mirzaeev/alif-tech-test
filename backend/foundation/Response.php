<?php


namespace App;


class Response
{

    public static function json($response, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    }
}