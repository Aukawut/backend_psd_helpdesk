<?php

class Model
{
    protected $conn;
    public $LineNotifyController;
    public function __construct()
    {
        $username = $_ENV["DB_USER"];
        $password = $_ENV["DB_PASS"];
        $databaseName = $_ENV["DB_NAME"];
        $databaseHost = $_ENV["DB_HOST"];
        $this->LineNotifyController = new LineNotifyController();
        try {
            $this->conn = new PDO("sqlsrv:Server=$databaseHost;Database=$databaseName", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
           
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }
}
