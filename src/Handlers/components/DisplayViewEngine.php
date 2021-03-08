<?php


namespace Handlers\components;


abstract class DisplayViewEngine extends HManager
{
    /**
     * @param $script
     * @param array $args
     * @param bool $autoShow
     * @return mixed
     */
    abstract function display($script, $args=array(), $autoShow=true);
}