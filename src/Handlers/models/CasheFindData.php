<?php


namespace Handlers\models;


class CasheFindData
{
    private $id;
    private $sumary;

    function __construct($id, $sumary) {
        $this->id = $id;
        $this->sumary = $sumary;
    }

    function getSummary(){
        return $this->sumary;
    }

    function getId(){
        return $this->id;
    }

    function equals($id){
        $status = false;


        if(is_array($id) && is_array($this->id)){

            if($id == $this->id){
                $status = true;
            }
        }

        return $status;
    }
}