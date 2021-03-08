<?php


namespace Handlers\components;


class SimpleDisplayViewEngine extends DisplayViewEngine
{

    function display($script, $args = array(), $autoShow = true)
    {
        extract($args);

        if(!$autoShow){
            ob_start();
        }
        /** @noinspection PhpIncludeInspection */
        include($script);

        if(!$autoShow){
            return ob_get_clean();
        }else{
            return true;
        }
    }
}