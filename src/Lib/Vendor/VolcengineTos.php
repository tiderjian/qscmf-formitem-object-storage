<?php

namespace FormItem\ObjectStorage\Lib\Vendor;

use FormItem\ObjectStorage\Lib\Common;
use Tos\Model\Enum;
use Tos\Model\HeadObjectInput;
use Tos\Model\PreSignedURLInput;
use Tos\Model\PreSignedURLOutput;
use Tos\TosClient;

class VolcengineTos implements IVendor
{

    public $vendor_type = Context::VENDOR_VOLCENGINE_TOS;
    private $_bucket;
    private $_region;
    private $_end_point;
    private $_config;
    private $_tos_client;

    public function genClient(string $type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        if(!$config){
            E('上传类型' . $type . '不存在!');
        }
        $this->_config = $config;

        if(!$config['tos_host']){
            E($type . '这不是tos上传配置类型!');
        }

        $handle = $this->_handleTosUrl($config['tos_host']);
        $this->_bucket = $handle['bucket'];
        $this->_region = $handle['region'];
        $this->_end_point = $handle['endpoint'];

        $this->_tos_client = new TosClient([
            'region' => $this->_region,
            'endpoint' => $this->_end_point,
            'ak' => env('VOLC_ACCESSKEY'),
            'sk' => env('VOLC_SECRETKEY'),
        ]);

        return $this;
    }

    public function genSignedUrl(array $param){
        return $this->signUrl(env('VOLC_BUCKET'), $param['object'], $param['timeout']);
    }

    public function signUrl(string $bucket, string $key, $timeout = 60, $method = 'GET', $query = ''):PreSignedURLOutput{
        // 生成上传对象的预签名 URL
        $input = new PreSignedURLInput($method, $bucket, $key);
        // 设置秒为单位的有效期，最大 7 天
        $input->setExpires($timeout);
        $input->setQuery($query);

        return $this->_tos_client->preSignedURL($input);
    }

    public function policyGet(string $type){
//        $callback_param = array('callbackUrl'=>Common::getCbUrlByType($type, $this->vendor_type),
//            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&upload_type=${x:upload_type}&image_format=${imageInfo.format}',
//            'callbackBodyType'=>"application/x-www-form-urlencoded");
//        if (I('get.title')){
//            $callback_param['callbackBody'].='&title=${x:title}';
//        }
//        if (I('get.hash_id')){
//            $callback_param['callbackBody'].='&hash_id=${x:hash_id}';
//        }
//        if (I('get.resize')){
//            $callback_param['callbackBody'].='&resize=${x:resize}';
//        }
//        $callback_string = json_encode($callback_param);
//        $base64_callback_body = base64_encode($callback_string);
//
//        $callback_var = array('x:upload_type' => $type);
//        if (I('get.title')){
//            $callback_var['x:title'].=I('get.title');
//        }
//        if (I('get.hash_id')){
//            $callback_var['x:hash_id'].=I('get.hash_id');
//        }
//        if (I('get.resize')){
//            $callback_var['x:resize'].=I('get.resize');
//        }
//        $callback_var=json_encode($callback_var);
//        $base64_callback_var=base64_encode($callback_var);
//        $query['x-tos-callback'] = $base64_callback_body;
//        $query['x-tos-callback-var'] = $base64_callback_var;
//
//        $config = C('UPLOAD_TYPE_' . strtoupper($type));
//
//        $ext='';
//        if (I('get.title') && strpos(I('get.title'),'.')!==false){
//            $ext = '.'.pathinfo(urldecode(I('get.title')),PATHINFO_EXTENSION);
//        }
//
//        $dir = Common::genObjectName($config, $ext);
//
//        $pathname=$dir;
//        str_starts_with($pathname, '/') && ($pathname = ltrim($pathname, '/'));
//
//        try {
//            $output = $this->genClient($type)->signUrl(env('VOLC_BUCKET'), $pathname, 3600, Enum::HttpMethodPut, $query);
//            // 获取预签名的 URL 和头域
//            $sign_url = $output->getSignedUrl();
//            $header = $output->getSignedHeader();
//
//            return [
//                'url'=> $sign_url,
//                'dir'=> $dir,
//                'headers' => $header,
//            ];
//
//        } catch (\RuntimeException $ex) {
//            echo $ex->getMessage() . PHP_EOL;
//        }
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        $host = $config['tos_host']; //"";

        $ext='';
        if (I('get.title') && strpos(I('get.title'),'.')!==false){
            $ext = '.'.pathinfo(urldecode(I('get.title')),PATHINFO_EXTENSION);
        }

        $dir=Common::genObjectName($config,$ext);

        $pathname=$dir;
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

//        $authorization=$this->getAuthorization($pathname,'POST');

        return [
            'url'=>$host.$pathname,
//            'authorization'=>$authorization,
            'params'=>[
                'key'=>$dir,
                'success_action_redirect'=>Common::getCbUrlByType($type, $this->vendor_type, I('get.title'), I('get.hash_id'), I('get.resize')),
            ]
        ];
    }

    public function extraObject(?array $params = []){
        $body_obj = $this->headObj($params['tos_host'],$params['cb_key']);
        $body_arr = [
            'key' => $params['cb_key'],
            'contentType' => $body_obj[0]->getContentType(),
            'contentLength' => $body_obj[0]->getContentLength(),
        ];

        $body_arr['mimeType'] = $this->_extraObjectMimeType($body_arr);

        return $body_arr;
    }

    public function formatHeicToJpg(string $url):string{
        return self::combineImgOpt($url, 'format,jpg');
    }

    public function combineImgOpt(string $url, string $img_opt):string
    {
        return Common::combineTosUrlImgOpt($url, $img_opt);
    }

    public function resizeImg(string $url, string $width = '', string $height = ''):string
    {
        !empty($width) && $width = 'w_'.$width;
        !empty($height) && $height = 'h_'.$height;
        $opt_str = implode(',', array_filter([$width, $height]));

        return self::combineImgOpt($url, 'resize,m_fill,'.$opt_str);
    }

    private function _extraObjectMimeType(?array $params = []){
        return $params['contentType'];
    }

    public function extraFile(array $config, array $body_arr):array{
        if ($body_arr['title']){
            $file_data['title'] = $body_arr['title'];
        }else {
            $name_arr = explode('/', $body_arr['key']);
            $file_data['title'] = end($name_arr);
        }
        $file_data['url'] = $config['tos_host'] . '/' . $body_arr['key'];
        $file_data['size'] = $body_arr['contentLength'];
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';

        return $file_data;
    }

    private function _handleTosUrl($url){
        $res=[];
        $parse=parse_url($url);
        $res['protocol']=$parse['scheme'];
        $res['key']=$parse['path'];

        $host=explode('.',$parse['host']);
        $res['region']=$host[1];
        $res['bucket']=$host[0];
        $res['endpoint']=$host[1].'.'.$host[2].'.'.$host[3];

        return $res;
    }

    public function headObj($bucket_host,$key){
        $input = new HeadObjectInput($this->_bucket, $key);

        $obj = $this->_tos_client->headObject($input);

        return array($obj);
    }

}