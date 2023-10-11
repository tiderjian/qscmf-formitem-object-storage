<?php

namespace FormItem\ObjectStorage\Lib\Vendor;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use FormItem\ObjectStorage\Lib\Common;

class AliyunOss implements IVendor {
    public $vendor_type = Context::VENDOR_ALIYUN_OSS;

    private $_client;
    private $_upload_config;
    private $_vendor_config;

    private $_host_key = 'oss_host';
    private $_upload_host_key = 'upload_oss_host';

    public function __construct()
    {
        $this->_vendor_config = $this->genVendorConfig([
            'accessKey' => env('ALIOSS_ACCESS_KEY_ID'),
            'secretKey' => env('ALIOSS_ACCESS_KEY_SECRET'),
            'bucket' => env('ALIOSS_BUCKET'),
            'endPoint' => env('ALIOSS_ENDPOINT'),
            'region' => env('ALIOSS_REGION'),
        ]);
    }

    public function setBucket(string $bucket): IVendor
    {
        $this->_vendor_config->setBucket($bucket);
        return $this;
    }

    public function setEndPoint(string $endPoint): IVendor
    {
        $this->_vendor_config->setEndPoint($endPoint);
        return $this;
    }

    public function genVendorConfig(array $config): VendorConfig
    {
        return new VendorConfig($config);
    }

    public function getShowHostKey():string{
        return $this->_host_key;
    }

    public function getUploadHostKey():string{
        return $this->_upload_host_key;
    }

    public function getUploadHost(array $config):string{
        return $config[$this->getUploadHostKey()] ?? $config[$this->getShowHostKey()];
    }

    public function genClient(string $type)
    {
        return $this->getOssClient($type);
    }

    public function getOssClient($type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));

        if (Common::checkUploadConfig($type, $this->vendor_type, $this->getShowHostKey(), $config)){
            $this->_upload_config = $config;

            $this->_client = new \OSS\OssClient($this->_vendor_config->getAccessKey(),
                $this->_vendor_config->getSecretKey(),
                $this->_vendor_config->getEndPoint());

            return $this;
        }
    }
    
    public function uploadFile(string $file_path, ?string $object_name = '', ?array $header_options = []){
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $object_name = $object_name ?: self::genOssObjectName($this->_upload_config, '.' . $ext);
        $header_options = array(\OSS\OssClient::OSS_HEADERS => $header_options);
        $res = $this->_client->uploadFile($this->_vendor_config->getBucket(), $object_name, $file_path, $header_options);
        if (isset($res['info']['http_code']) && $res['info']['http_code'] === 200){
            return $object_name;
        }

        return $res;
    }

    public function genSignedUrl(array $param){
        return $this->signUrl($param['object'], $param['timeout']);
    }
    
    public function signUrl($object, $timeout){
        
        $signedUrl = $this->_client->signUrl($this->_vendor_config->getBucket(), $object, $timeout);
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
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, env('ALIOSS_ACCESS_KEY_SECRET'), true));

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
        $response['accessid'] = env('ALIOSS_ACCESS_KEY_ID');
        $response['host'] = $this->getUploadHost($config);
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
        $file_data['url'] = $config[$this->getShowHostKey()] . '/' . $body_arr['filename'] . ($config['oss_style'] ? $config['oss_style'] : '');
        $file_data['size'] = $body_arr['size'];
        $file_data['cate'] = $body_arr['upload_type'];
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';
        $file_data['vendor_type'] = $this->vendor_type;

        return $file_data;
    }
}

