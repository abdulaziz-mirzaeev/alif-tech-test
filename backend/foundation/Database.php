<?php


namespace App;


use PDO;
use PDOException;

class Database
{
    private $dsn;
    private $user;
    private $password;
    private $connection;


    public function __construct($dsn, $user, $password)
    {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
    }

    public function dbConnection()
    {
        try {
            $this->connection = new PDO($this->dsn, $this->user, $this->password);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->connection;
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            exit;
        }
    }

    public function closeConnection()
    {
        $this->connection = null;
    }
}