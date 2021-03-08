<?php


namespace Handlers\data_access;


class QueryDynamicParams
{
    const MERGE_TYPE_HAVING = "HAVING";
    const MERGE_TYPE_WHERE = "WHERE";
    const MERGE_TYPE_AND = "AND";
    const MERGE_TYPE_OR = "OR";

    public $groups;
    public $filters;
    public $filter_keys;
    private $order_fields;
    private $page;
    private $cant_by_page;
    public $filter_marge_tag;

    private $enable_paging = false;
    public $enable_order = false;
    public $enable_filters = false;
    public $enable_groups = false;

    /**
     * @param bool $enable_paging
     */
    public function setEnablePaging($cant_by_page, $page=0)
    {
        $this->enable_paging = true;
        $this->cant_by_page = $cant_by_page;
        $this->page = $page;
    }

    /**
     * @return mixed
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return mixed
     */
    public function getCantByPage()
    {
        return $this->cant_by_page;
    }

    /**
     * @return bool
     */
    public function isEnablePaging()
    {
        return $this->enable_paging;
    }





    public function addOrderField($field, $asc=true){
        $this->order_fields[$field] = $asc;
    }

    public function removeOrder(){
        $this->order_fields = array();
    }

    /**
     * @return array
     */
    public function getOrderFields()
    {
        if(is_null($this->order_fields)){
            $this->order_fields = array();
        }
        return $this->order_fields;
    }


}