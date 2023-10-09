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

    private $_host_key = 'tos_host';
    private $_upload_host_key = 'tos_host';

    public function getShowHostKey():string{
        return $this->_host_key;
    }

    public function getUploadHostKey():string{
        return $this->_upload_host_key;
    }

    public function getUploadHost(array $config):string{
        return $config[$this->getUploadHostKey()] ?? $config[$this->getShowHostKey()];
    }

    public function genClient(string $type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        if(!$config){
            E('上传类型' . $type . '不存在!');
        }
        $this->_config = $config;

        if(!$config[$this->getShowHostKey()]){
            E($type . '这不是tos上传配置类型!');
        }

        $handle = $this->_handleTosUrl($config[$this->getShowHostKey()]);
        $this->_bucket = $handle['bucket'];
        $this->_region = $handle['region'];
        $this->_end_point = $handle['endpoint'];

        $this->_tos_client = new TosClient([
            'region' => $this->_region,
            'endpoint' => $this->_end_point,
            'ak' => env('VOLC_ACCESS_KEY'),
            'sk' => env('VOLC_SECRET_KEY'),
        ]);

        return $this;
    }

    public function genSignedUrl(array $param){
        $obj = $this->signUrl(env('VOLC_BUCKET'), $param['object'], $param['timeout']);
        return $obj->getSignedUrl();
    }

    public function signUrl(string $bucket, string $key, $timeout = 60, $method = 'GET', $query = ''):PreSignedURLOutput{
        // 生成上传对象的预签名 URL
        $input = new PreSignedURLInput($method, $bucket, $key);
        // 设置秒为单位的有效期，最大 7 天
        $input->setExpires($timeout);
        $input->setQuery($query);

        return $this->_tos_client->preSignedURL($input);
    }

    private function _cbParam(string $type):array{
        $callback_param = array('callbackUrl'=>Common::getCbUrlByType($type, $this->vendor_type, I('get.title'), I('get.hash_id'), I('get.resize')),
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
        $base64_callback_var=base64_encode($callback_var);

        return [$base64_callback_body,$base64_callback_var];
    }

    private function _genPutSignedParamsDemo(string $type){
        list($base64_callback_body,$base64_callback_var) = $this->_cbParam($type);

        $query['x-tos-callback'] = $base64_callback_body;
        $query['x-tos-callback-var'] = $base64_callback_var;

        $config = C('UPLOAD_TYPE_' . strtoupper($type));

        $ext='';
        if (I('get.title') && strpos(I('get.title'),'.')!==false){
            $ext = '.'.pathinfo(urldecode(I('get.title')),PATHINFO_EXTENSION);
        }

        $dir = Common::genObjectName($config, $ext);

        $pathname=$dir;
        str_starts_with($pathname, '/') && ($pathname = ltrim($pathname, '/'));

        try {
            $output = $this->genClient($type)->signUrl(env('VOLC_BUCKET'), $pathname, 3600, Enum::HttpMethodPut, $query);
            // 获取预签名的 URL 和头域
            $sign_url = $output->getSignedUrl();
            $header = $output->getSignedHeader();

            return [
                'url'=> $sign_url,
                'dir'=> $dir,
                'headers' => $header,
            ];

        } catch (\RuntimeException $ex) {
            echo $ex->getMessage() . PHP_EOL;
        }
    }

    private function _genCredential(int $now, string $region):string{
        $date = date('Ymd', $now);

        return env('VOLC_ACCESS_KEY').'/'.$date.'/'.$region.'/tos/request';
    }

    private function _genSignKey(int $now, string $region):string{
        $date = date('Ymd', $now);

        $dateKey = hash_hmac('sha256', $date, env('VOLC_SECRET_KEY'), true);
        $regionKey = hash_hmac('sha256', $region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', 'tos', $regionKey, true);
        $signingKey = hash_hmac('sha256', 'request', $serviceKey, true);
        return $signingKey;
    }

    private function _genPostSignature(string $string_to_sign, int $now, string $region):string{
        return hash_hmac('sha256', $string_to_sign, $this->_genSignKey($now, $region));
    }

    private function _genSignDate(int $now):string{
        return date('Ymd\THis', $now).'Z';
    }

    private function _genSignAlgorithm():string{
        return 'TOS4-HMAC-SHA256';
    }

    private function _genPostSignPolicy(string $dir,int $now, string $bucket, array $common_params):string{
        $expire = 60*2;
        $end = $now + $expire;
        $expiration = date('Y-m-d\TH:i:s.z', $end).'Z';

        $conditions = [];
        $conditions[] = ['bucket' => $bucket];

        $start = ['starts-with', '$key', $dir];
        $conditions[] = $start;
        $common_params = collect($common_params)->map(function($val, $key){
            return [$key => $val];
        })->values()->all();
        $conditions = collect($conditions)->merge($common_params)->all();

        $arr = [
            'expiration'=>$expiration,
            'conditions'=>$conditions
        ];

        $policy = json_encode($arr, JSON_UNESCAPED_SLASHES);
        return base64_encode($policy);
    }

    private function _genPostObjParam(string $type){
        list($base64_callback_body,$base64_callback_var) = $this->_cbParam($type);

        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        $host = $this->getUploadHost($config);
        $handle = $this->_handleTosUrl($host);
        $bucket = $handle['bucket'];
        $region = $handle['region'];

        $ext='';
        if (I('get.title') && strpos(I('get.title'),'.')!==false){
            $ext = '.'.pathinfo(urldecode(I('get.title')),PATHINFO_EXTENSION);
        }

        $dir=Common::genObjectName($config,$ext);

        $pathname=$dir;
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

        $now = microtime(true);

        $algorithm = $this->_genSignAlgorithm();
        $date = $this->_genSignDate($now);
        $credential = $this->_genCredential($now, $region);

        $common_params = [
            'Content-Type' => I('get.file_type'),
            'name' => I('get.title'),
            'x-tos-callback' => $base64_callback_body,
            'x-tos-callback-var' => $base64_callback_var,
            'x-tos-credential' => $credential,
            'x-tos-algorithm' => $algorithm,
            'x-tos-date' => $date,
//                'x-tos-security-token' => '',
        ];

        $base64_policy = $this->_genPostSignPolicy($dir, $now, $bucket, $common_params);

        $signature = $this->_genPostSignature($base64_policy, $now, $region);

        return [
            'url' => $host.$pathname,
            'params' => array_merge($common_params,
                [
                    'key' => $dir,
                    'policy' => $base64_policy,
                    'x-tos-signature' => $signature,
                ]
            )
        ];
    }

    public function policyGet(string $type){
//        $this->_genPutSignedParamsDemo($type);
       return $this->_genPostObjParam($type);
    }

    private function _extraObjectViaInput(?array $params = []){
        $body = file_get_contents('php://input');
        parse_str($body, $body_arr);

        return $body_arr;
    }

    private function _extraObjectViaHeadObj(?array $params = []){
        $body_obj = $this->headObj($params[$this->getShowHostKey()],$params['cb_key']);

        $body_arr = [
            'filename' => $params['cb_key'],
            'mimeType' => $body_obj[0]->getContentType(),
            'size' => $body_obj[0]->getContentLength(),
        ];

        return $body_arr;
    }

    public function extraObject(?array $params = []){
        return $this->_extraObjectViaInput($params);
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
            $name_arr = explode('/', $body_arr['filename']);
            $file_data['title'] = end($name_arr);
        }
        $file_data['url'] = $config[$this->getShowHostKey()] . '/' . $body_arr['filename'];
        $file_data['size'] = $body_arr['size'];
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
        $res['region']=trim($host[1], 'tos-');
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