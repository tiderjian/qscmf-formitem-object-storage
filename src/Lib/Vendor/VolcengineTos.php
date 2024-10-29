<?php

namespace FormItem\ObjectStorage\Lib\Vendor;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\UploadConfig;
use Illuminate\Support\Str;
use Tos\Model\Enum;
use Tos\Model\HeadObjectInput;
use Tos\Model\PreSignedURLInput;
use Tos\Model\PreSignedURLOutput;
use Tos\Model\PutObjectFromFileInput;
use Tos\Model\PutObjectFromFileOutput;
use Tos\TosClient;

class VolcengineTos implements IVendor
{

    public $vendor_type = Context::VENDOR_VOLCENGINE_TOS;

    private $_client;
    private $_upload_config;
    private $_vendor_config;

    public function __construct()
    {
        $this->setVendorConfig([
            'accessKey' => env('VOLC_ACCESS_KEY'),
            'secretKey' => env('VOLC_SECRET_KEY'),
            'bucket' => env('VOLC_BUCKET'),
            'endPoint' => env('VOLC_ENDPOINT'),
            'region' => env('VOLC_REGION'),
            'host' => env('VOLC_HOST'),
            'upload_host' => env('VOLC_UPLOAD_HOST'),
            'host_key' => 'tos_host',
            'upload_host_key' => 'upload_tos_host',
            'endpoint_key' => 'tos_endpoint',
        ]);
    }

    public function getVendorType():string
    {
        return $this->vendor_type;
    }

    public function setUploadConfig(string $type, ?array $config = []): IVendor
    {
        $this->_upload_config = new UploadConfig($type, $config);
        $config_endpoint = $this->getUploadConfig()->getEndPoint($this->getVendorConfig()->getEndPointKey());
        $config_endpoint && $this->setEndPoint($config_endpoint);

        return $this;
    }

    public function getUploadConfig():UploadConfig{
        return $this->_upload_config;
    }

    public function getClient()
    {
        return $this->_client;
    }

    public function getVendorConfig():VendorConfig
    {
        return $this->_vendor_config;
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

    public function setVendorConfig(array $config): IVendor
    {
        $this->_vendor_config = new VendorConfig($config);

        return $this;
    }

    public function getUploadHost(array $config):string{
        return $config[$this->getVendorConfig()->getUploadHostKey()] ?? $config[$this->getVendorConfig()->getHostKey()];
    }

    public function genClient(string $type, ?bool $check_config = true){
        if (!isset($this->_upload_config) || !$this->getUploadConfig()->getAll()){
            $this->setUploadConfig($type);
        }
        if (!$check_config || Common::checkUploadConfig($this)){

            $this->_client = new TosClient([
                'region' => $this->_vendor_config->getRegion(),
                'endpoint' => $this->_vendor_config->getEndPoint(),
                'ak' => $this->_vendor_config->getAccessKey(),
                'sk' => $this->_vendor_config->getSecretKey(),
            ]);

            return $this;
        }
    }

    public function genSignedUrl(array $param){
        $obj = $this->signUrl($this->_vendor_config->getBucket(), $param['object'], $param['timeout']);
        return $obj->getSignedUrl();
    }

    public function signUrl(string $bucket, string $key, $timeout = 60, $method = 'GET', $query = ''):PreSignedURLOutput{
        // 生成上传对象的预签名 URL
        $input = new PreSignedURLInput($method, $bucket, $key);
        // 设置秒为单位的有效期，最大 7 天
        $input->setExpires($timeout);
        $input->setQuery($query);

        return $this->_client->preSignedURL($input);
    }

    private function _cbParam(string $type, array $get_data):array{
        $hash_id = Common::getHashId();
        $callback_param = array('callbackUrl'=>Common::getCbUrlByType($type, $this->vendor_type, $get_data['title']??'', $hash_id, $get_data['resize']??'', true, $get_data),
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&upload_type=${x:upload_type}',
            'callbackBodyType'=>"application/x-www-form-urlencoded");

        $callback_var = array('x:upload_type' => $type);

        Common::injectCbParam($get_data, $callback_param, $callback_var);

        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);

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
        strpos($pathname, '/') === 0 && ($pathname = ltrim($pathname, '/'));

        try {
            $output = $this->genClient($type)->signUrl($this->_vendor_config->getBucket(), $pathname, 3600, Enum::HttpMethodPut, $query);
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

        return $this->_vendor_config->getAccessKey().'/'.$date.'/'.$region.'/tos/request';
    }

    private function _genSignKey(int $now, string $region):string{
        $date = date('Ymd', $now);

        $dateKey = hash_hmac('sha256', $date, $this->_vendor_config->getSecretKey(), true);
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
        $expire = 10;
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

        $policy = json_encode($arr);
        return base64_encode($policy);
    }

    private function _genPostObjParam(string $type, array $get_data){
        list($base64_callback_body,$base64_callback_var) = $this->_cbParam($type, $get_data);

        $this->setUploadConfig($type);
        $config = $this->getUploadConfig()->getAll();
        $host = $this->getUploadHost($config);
        $bucket = $this->_vendor_config->getBucket();
        $region = $this->_vendor_config->getRegion();

        $ext='';
        if ($get_data['title'] && strpos($get_data['title'],'.')!==false){
            $ext = '.'.pathinfo(urldecode($get_data['title']),PATHINFO_EXTENSION);
        }

        $dir=Common::genObjectName($config,$ext);

        $pathname=$dir;
        $pathname[0] !== '/' && ($pathname = '/' . $pathname);

        $now = microtime(true);

        $algorithm = $this->_genSignAlgorithm();
        $date = $this->_genSignDate($now);
        $credential = $this->_genCredential($now, $region);

        $common_params = [
            'Content-Type' => $get_data['file_type'],
            'name' => $get_data['title'],
            'x-tos-callback' => $base64_callback_body,
            'x-tos-callback-var' => $base64_callback_var,
            'x-tos-credential' => $credential,
            'x-tos-algorithm' => $algorithm,
            'x-tos-date' => $date,
//                'x-tos-security-token' => '',
        ];
        $upload_meta = $this->getUploadConfig()->getMeta();
        $upload_meta = Common::injectMeta($upload_meta, $get_data);
        if ($upload_meta){
            collect($upload_meta)->each(function($value, $name) use(&$common_params){
                $common_params[$name] = $value;
            });
        }

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
        $get_data = Common::extractParams(I('get.'));

        if (empty($get_data['title']) || empty($get_data['file_type'])){
            E("缺少必填参数");
        }
//        return $this->_genPutSignedParamsDemo($type);
       return $this->_genPostObjParam($type, $get_data);
    }

    private function _extraObjectViaInput(?array $params = []){
        $body = file_get_contents('php://input');
        parse_str($body, $body_arr);

        $body_arr['title'] = Common::extraBodyTitle($body_arr);

        return $body_arr;
    }

    private function _extraObjectViaHeadObj(?array $params = []){
        $body_obj = $this->headObj($params[$this->getVendorConfig()->getHostKey()],$params['cb_key']);

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
        $file_data['url'] = $config[$this->getVendorConfig()->getHostKey()] . '/' . $body_arr['filename'];
        $file_data['size'] = $body_arr['size'];
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';
        $file_data['vendor_type'] = $this->vendor_type;

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
        $input = new HeadObjectInput($this->_vendor_config->getBucket(), $key);

        $obj = $this->_client->headObject($input);

        return array($obj);
    }

    public function uploadFile(string $file_path, ?string $object_name = '', ?array $header_options = []){
        $input = new PutObjectFromFileInput($this->_vendor_config->getBucket(), $object_name, $file_path);
        $header_options && $this->_setUploadOptions($input, $header_options);
        $output = $this->_client->PutObjectFromFile($input);
        if ($output->getStatusCode() === 200){
            return $object_name;
        }

        return $output;
    }

    private function _transcodeKeyToFunName(string $key):string{
        return 'set'.ucfirst(Str::camel(str_replace('-', '_', $key)));
    }

    private function _setUploadOptions(PutObjectFromFileInput &$input, array $header_options){
        collect($header_options)->each(function($val,$name) use(&$input){
            $fun = $this->_transcodeKeyToFunName($name);
            $input->$fun($val);
        });
    }

}