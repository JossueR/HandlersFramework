<?php


namespace Handlers\models;


class LangDAO extends \Handlers\data_access\AutoImplementedDAO
{

    /**
     * LangDAO constructor.
     */
    public function __construct()
    {
        parent::__construct("i18n", array("key"));
    }

    public function getByLang($ln){
        $sql = "SELECT `key`, " . $ln . " FROM i18n";

        $this->find($sql);
    }
}