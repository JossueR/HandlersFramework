<?php


namespace Handlers\models;


class Connection
{
    public $connection;

    public $host;

    public $user;

    public $pass;

    public $db;

    function __construct($host=null,$bd=null,$usuario=null,$pass=null, $connection = null){
        $this->host=$host;
        $this->user=$usuario;
        $this->db=$bd;
        $this->pass=$pass;
        $this->connection=$connection;
    }
}