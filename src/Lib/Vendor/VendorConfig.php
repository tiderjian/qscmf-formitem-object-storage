<?php

namespace FormItem\ObjectStorage\Lib\Vendor;

class VendorConfig
{
    protected $config;

    public function __construct(array $config){
        if ($this->_checkRequired($config) === true){
            $this->config = $config;
        }
    }

    private function _checkRequired(array $config):bool{
        foreach($this->_getRequired() as $k => $v){
            if (is_numeric($k)){
                $key = $v;
                $value = $v;
            }else{
                $key = $k;
                $value = $v;
            }
            if(qsEmpty($config[$key])){
                E($value. ' is empty');
            }
        }
        return true;
    }

    private function _getRequired():array{
        return [
            'accessKey',
            'secretKey',
            'bucket',
            'endPoint',
            'region',
        ];
    }

    public function getBucket():string{
        return $this->config['bucket'];
    }

    public function getEndPoint():string{
        return $this->config['endPoint'];
    }

    public function getRegion():string{
        return $this->config['region'];
    }

    public function getAccessKey():string{
        return $this->config['accessKey'];
    }

    public function getSecretKey():string{
        return $this->config['secretKey'];
    }

    public function setBucket(string $bucket):self{
        $this->config['bucket'] = $bucket;
        return $this;
    }

    public function setEndPoint(string $endPoint):self{
        $this->config['endPoint'] = $endPoint;
        return $this;
    }

    public function __call($method,$args) {
        if (function_exists($this->$method)){
            $this->$method($args);
        }
        if(substr($method,0,3)==='get') {
            $key = lcfirst(substr($method, 3));
            return $this->config[$key];
        }
        if(substr($method,0,3)==='set') {
            $key = lcfirst(substr($method, 3));
            $this->config[$key] = $args;
            return $this;
        }
    }

}