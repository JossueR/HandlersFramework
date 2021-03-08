<?php

namespace Handlers\data_access;

abstract class AutoImplementedDAO extends AbstractBaseDAO
{
    private $fields;
    private $master_prototype;
    private $master_map;

    /**
     * AutoImplementedDAO constructor.
     * @param string $table
     * @param array $id
     */
    function __construct($table, $id) {
        parent::__construct($table, $id);

        $this->loadFieldConfig();
    }

    private function loadFieldConfig(){
        $sql = "SELECT * FROM " . $this->table . " LIMIT 0";
        $this->find($sql);

        $total = self::getNumFields($this->getSumary());
        for($i=0; $i< $total;$i++){
           $field_info =  self::getFieldInfo($this->getSumary(),$i);

            $this->fields[] = $field_info->name;

            $this->master_prototype[$field_info->name] = null;

            $this->master_map[$field_info->name] = $field_info->name;
        }
    }

    function getPrototype(){
        return $this->master_prototype;
    }


    function getDBMap(){
        return $this->master_map;
    }

    function getBaseSelec(){
        $sql = "SELECT *
					FROM $this->table
					WHERE ";

        return $sql;
    }

    /**
     * @param array $fields_array
     */
    function setFieldsPrototype($fields_array){
        $this->master_prototype = array();
        foreach ($fields_array as $field){
            $this->master_prototype[$field] = null;
        }
    }

}