<?php

namespace FormItem\ObjectStorage\Lib;

class UploadConfig
{
    protected $config;
    protected $type;

    public function __construct(string $type, ?array $config = []){
        $this->type = $type;
        $this->config = $config ?: C('UPLOAD_TYPE_' . strtoupper($type));
    }

    public function getAll(){
        return $this->config;
    }

    public function getType(){
        return $this->type;
    }

    public function getExts(){
        return !empty($this->config['exts']) ? $this->config['exts'] : '*';
    }

    // v13 think-core 不兼容
    public function getMeta(){
        if (isset($this->config['os_upload_meta'])){
            return $this->config['os_upload_meta'];
        }

        return $this->config['oss_meta'];
    }

    public function getMaxSize(){
        $maxSize = $this->config['maxSize'];
        return is_numeric($maxSize) && $maxSize > 0 ? $maxSize : 1024*1024*1024*1024;
    }

    public function __call($method,$args) {
        if (function_exists($this->$method)){
            $this->$method($args);
        }
        if(substr($method,0,3)==='get') {
            $key = lcfirst(substr($method, 3));
            return $this->config[$key];
        }
    }

}