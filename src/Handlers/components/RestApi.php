<?php


namespace Handlers\components;


class RestApi extends HManager
{
    private $headers;
    private $url;
    private $mode;
    private $data;
    private $verbose;
    private $security_enabled;
    private $send_mode;
    private $cert_path;
    private $last_http_error_code;

    const MODE_JSON = "json";
    const MODE_RAW = "RAW";

    const SEND_MODE_GET = 0;
    const SEND_MODE_POST = 1;


    function __construct($url) {
        $this->url = $url;

        $headers = array('Accept', ' */*');

        $this->mode = self::MODE_JSON;
        $this->verbose = false;
        $this->security_enabled = false;
        $this->send_mode = self::SEND_MODE_GET;
    }

    /**
     * @param mixed $cert_path
     */
    public function setCertPath($cert_path)
    {
        $this->cert_path = $cert_path;
    }



    function enableVerbose(){
        $this->verbose = true;
    }

    function enableSecurityCheck(){
        $this->security_enabled = true;
    }

    function setSendMode($mode){
        $this->send_mode = $mode;
    }

    function addHeader($name, $val){
        $this->headers[$name] = $val;
    }

    function addMultipleHeaders($headers_array){

        if(is_array($headers_array)){
            $this->headers = array_merge($this->headers, $headers_array);
        }
    }

    function setSendModeJSON(){
        $this->mode = self::MODE_JSON;
        $this->addHeader("Content-Type", "application/json");
    }

    private function buildHeaders(){
        $all = array();

        foreach ($this->headers as $key => $value) {
            $all[] = "$key: $value";
        }

        return $all;
    }

    function call(){
        $curl = curl_init();



        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->buildHeaders());

        if($this->verbose){
            $verbose_file = fopen('temp', 'w');

            curl_setopt($curl, CURLOPT_VERBOSE, true);
            curl_setopt($curl, CURLOPT_STDERR, $verbose_file);
        }

        curl_setopt($curl, CURLOPT_POST, $this->send_mode);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);

        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if($this->security_enabled){

            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

            curl_setopt($curl, CURLOPT_CAINFO, $this->cert_path);
        }else{
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }



        $result = curl_exec($curl);
        //fclose($verbose_file);

        if($this->verbose){
            echo "POST_dATA: " . $this->data;

            print_r(curl_getinfo($curl));


            $verboseLog = stream_get_contents($verbose_file);
            echo " LOG::" . $verboseLog;
        }

        if(!$result){
            echo " ERR::" . curl_errno($curl);
        }


        curl_close($curl);

        return $result;
    }

    function setData($data){
        $this->data = $data;
    }

    public function getLastHttpCode(){
        return $this->last_http_error_code;
    }
}