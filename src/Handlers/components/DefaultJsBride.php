<?php


namespace Handlers\components;


class DefaultJsBride implements JsBridge
{

    function windowReload($script = false, $auto=true)
    {
        $command = "";
        if($script){
            $command = "<script>";
        }

        if($script){
            $command .=  "window.location='$script'";
        }else{
            $command .=  "location.reload(true)";
        }

        if($script){
            $command .=  "</script>";
        }

        if($auto){
            echo $command;
        }

        return $command;
    }

    function asyncLoad($action, $dest, $param, $noEcho = false, $escape = true, $msg = "")
    {
        //muestra el sql si se habilita el modo depuracion


        if($escape){
            $param = http_build_query($param, '', '&');
        }else{
            $p= "";
            foreach ($param as $key => $value) {
                $p .= "$key=$value&";
            }
            $param = substr($p, 0, -1);
        }

        $msg = addslashes($msg);

        if(trim($msg) == ""){
            $comand = "dom_update('$action', '$param', '$dest')";
        }else{
            $comand = "dom_confirm('$action', '$param', '$dest', '$msg')";
        }



        if(!$noEcho){
            echo "<script>";
            echo $comand;
            echo "</script>";

        }

        return $comand;
    }

    function asyncModal($action, $dest, $param, $noEcho = false, $escape = true, $msg = "")
    {
        $command = " $('#$dest').empty();  ";
        $command .= self::asyncLoad($action, $dest, $param,$noEcho,$escape, $msg);
        $command .= "; " . self::goAnchor(ConfigParams::$APP_MODAL_HANDLER);

        //si es false, si se quiere que se muestre automaticamente
        if(!$noEcho){
            echo "<script>" . $command . "</script>";
        }


        return $command;
    }

    function syncLoad($action, $dest, $param, $noEcho = false, $escape = true)
    {


        if($escape){
            $param = http_build_query($param, '', '&');
        }else{
            $p= "";
            foreach ($param as $key => $value) {
                $p .= "$key=$value&";
            }
            $param = substr($p, 0, -1);
        }

        if($dest==""){
            $comand = "window.location.href='".ConfigParams::$PATH_ROOT."$action?$param'";
        }else{
            $comand = "window.open('".ConfigParams::$PATH_ROOT."$action?$param')";
        }




        if(!$noEcho){
            echo "<script>";
            echo $comand;
            echo "</script>";

        }

        return $comand;
    }

    function goAnchor($anchor, $autoshow = false)
    {


        $comand = "location.hash = '#$anchor'";

        if($autoshow){
            echo "<script>$comand</script>";
        }


        return $comand;
    }

    function asyncLoadInterval($action, $dest, $param, $noEcho = false, $escape = true, $interval = 5)
    {

        if($escape){
            $param = http_build_query($param, '', '&');
        }else{
            $p= "";
            foreach ($param as $key => $value) {
                $p .= "$key=$value&";
            }
            $param = substr($p, 0, -1);
        }

        $comand = "dom_update_refresh('$action', '$param', '$dest', '$interval')";



        if(!$noEcho){
            echo "<script>";
            echo $comand;
            echo "</script>";


        }
        return $comand;
    }

    function showPagination($name, $totalRows, $action, $param, $controls = null,$auto=true)
    {
        $param = http_build_query($param, '', '&');
        $action = ConfigParams::$PATH_ROOT . $action;

        $show = array();

        if($controls){

            foreach($controls as $control){
                $show[$control]=true;
            }
        }
        $show = json_encode($show);


        $command = "<script>";

        $command .= "showPagination($totalRows,'$name','$action','$param', '" . ConfigParams::$APP_DEFAULT_LIMIT_PER_PAGE . "', $show) ";
        $command .= "</script>";

        if($auto){
            echo $command;
        }

        return $command;
    }


    function showTableControls($name, $totalRows, $action, $param, $controls = null, $auto = true)
    {
        // TODO: Implement showTableControls() method.
    }
}