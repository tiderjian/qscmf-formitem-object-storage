<?php

namespace FormItem\ObjectStorage\Lib\Vendor;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use FormItem\ObjectStorage\Lib\Common;

class AliyunOss implements IVendor {
    private $_bucket;
    private $_end_point;
    private $_oss_client;
    private $_config;

    public $vendor_type = Context::VENDOR_ALIYUN_OSS;

    public function genClient(string $type)
    {
        return $this->getOssClient($type);
    }

    public function getOssClient($type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        if(!$config){
            E('上传类型' . $type . '不存在!');
        }
        $this->_config = $config;
        
        if(!$config['oss_host']){
            E($type . '这不是oss上传配置类型!');
        }
        
        if(!preg_match('/https*:\/\/([\w\-_]+?)\.[\w\-_.]+/', $config['oss_host'], $match)){
            E($type . '类型上传配置项中匹配不到bucket项');
        }
        
        $this->_bucket = $match[1];
        $this->_end_point = str_replace($this->_bucket . '.', '', $config['oss_host']);
        $this->_oss_client = new \OSS\OssClient(C('ALIOSS_ACCESS_KEY_ID'), C('ALIOSS_ACCESS_KEY_SECRET'), $this->_end_point);
        return $this;
    }
    
    public function uploadFile($file, $options){
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $object = self::genOssObjectName($this->_config, '.' . $ext);
        $header_options = array(\OSS\OssClient::OSS_HEADERS => $options);
        return $this->_oss_client->uploadFile($this->_bucket, $object, $file, $header_options);
    }

    public function genSignedUrl(array $param){
        return $this->signUrl($param['object'], $param['timeout']);
    }
    
    public function signUrl($object, $timeout){
        
        $signedUrl = $this->_oss_client->signUrl($this->_bucket, $object, $timeout);
        return $signedUrl;
    }
    
    public static function genOssObjectName($config, $ext = ''){
        return Common::genObjectName($config, $ext);
    }

    public function policyGet($type){
        $callback_param = array('callbackUrl'=>Common::getCbUrlByType($type, $this->vendor_type),
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&upload_type=${x:upload_type}&image_format=${imageInfo.format}',
            'callbackBodyType'=>"application/x-www-form-urlencoded");
        if (I('get.title')){
            $callback_param['callbackBody'].='&title=${x:title}';
        }
        if (I('get.hash_id')){
            $callback_param['callbackBody'].='&hash_id=${x:hash_id}';
        }
        if (I('get.resize')){
            $callback_param['callbackBody'].='&resize=${x:resize}';
        }
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 10;
        $end = $now + $expire;
        $expiration = gmt_iso8601($end);

        $config = C('UPLOAD_TYPE_' . strtoupper($type));

        $dir = self::genOssObjectName($config);
        $condition = array(0=>'content-length-range', 1=>0, 2=> Common::getMaxSize($type));

        $conditions[] = $condition;

        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;

        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);

        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, C('ALIOSS_ACCESS_KEY_SECRET'), true));

        $callback_var = array('x:upload_type' => $type);
        if (I('get.title')){
            $callback_var['x:title'].=I('get.title');
        }
        if (I('get.hash_id')){
            $callback_var['x:hash_id'].=I('get.hash_id');
        }
        if (I('get.resize')){
            $callback_var['x:resize'].=I('get.resize');
        }
        $callback_var=json_encode($callback_var);

        $response = array();
        $response['accessid'] = C('ALIOSS_ACCESS_KEY_ID');
        $response['host'] = $config['upload_oss_host'] ?? $config['oss_host'];
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['callback_var'] = $callback_var;
        if($config['oss_meta']){
            $get_data = I('get.');
            foreach($config['oss_meta'] as $k => &$vo){
                $vo = preg_replace_callback('/__(\w+?)__/', function($matches) use($get_data){
                    return $get_data[$matches[1]];
                }, $vo);


                if(strtolower($k) == 'content-disposition' && preg_match("/attachment;\s*?filename=(.+)/", $vo, $matches)){
                    $vo = preg_replace_callback("/attachment;\s*?filename=(.+)/", function($matches){
                        return 'attachment;filename=' . urlencode($matches[1]) . ";filename*=utf-8''" . urlencode($matches[1]);
                    }, $vo);
                }
            }
            $response['oss_meta'] = json_encode($config['oss_meta']);
        }
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;

        return $response;
    }

    public function extraObject(?array $params = []){
        $r = $this->_veryfy($body);
        if ($r === false){
            return false;
        }
        parse_str($body, $body_arr);

        $body_arr['mimeType'] = $this->_extraObjectMimeType($body_arr);

        return $body_arr;
    }

    private function _extraObjectMimeType(?array $params = []){
        return $params['image_format']!=='${imageInfo.format}' ?
            Common::intersectMimeType($params['image_format'],$params['mimeType']) :
            $params['mimeType'];
    }

    private function _veryfy(?string &$body){
        $authorizationBase64 = "";
        $pubKeyUrlBase64 = "";

        if (isset($_SERVER['HTTP_AUTHORIZATION']))
        {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL']))
        {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '')
        {
            return false;
        }

        $authorization = base64_decode($authorizationBase64);
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);

        if ($pubKey == "")
        {
            return false;
        }

        $body = file_get_contents('php://input');
        $authStr = '';
        $path = REQUEST_URI;
        $pos = strpos($path, '?');

        if ($pos === false)
        {
            $authStr = urldecode($path)."\n".$body;
        }
        else
        {
            $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
        }

        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if ($ok == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function formatHeicToJpg(string $url):string
    {
        return self::combineImgOpt($url, 'format,jpg');
    }

    public function resizeImg(string $url, string $width = '', string $height = ''):string
    {
        !empty($width) && $width = 'w_'.$width;
        !empty($height) && $height = 'h_'.$height;
        $opt_str = implode(',', array_filter([$width, $height]));

        return self::combineImgOpt($url, 'resize,m_fill,'.$opt_str);
    }

    public function combineImgOpt(string $url, string $img_opt):string
    {
        return Common::combineOssUrlImgOpt($url, $img_opt);
    }

    public function extraFile(array $config, array $body_arr):array{
        if ($body_arr['title']){
            $file_data['title'] = $body_arr['title'];
        }else {
            $name_arr = explode('/', $body_arr['filename']);
            $file_data['title'] = end($name_arr);
        }
        $file_data['url'] = $config['oss_host'] . '/' . $body_arr['filename'] . ($config['oss_style'] ? $config['oss_style'] : '');
        $file_data['size'] = $body_arr['size'];
        $file_data['cate'] = $body_arr['upload_type'];
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';

        return $file_data;
    }
}

