<?php


namespace FormItem\ObjectStorage\Lib\Vendor;


use FormItem\ObjectStorage\Lib\Common;

class TengxunCos implements IVendor
{
    public $vendor_type = Context::VENDOR_TENGXUN_COS;
    private $_bucket;
    private $_region;
    private $_config;
    private $_cos_client;

    public function getCosClient($type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        if(!$config){
            E('上传类型' . $type . '不存在!');
        }
        $this->_config = $config;

        if(!$config['cos_host']){
            E($type . '这不是cos上传配置类型!');
        }

        $handle = $this->_handleCosUrl($config['cos_host']);
        $this->_bucket = $handle['bucket'];
        $this->_region = $handle['region'];

        $config = array(
            'region' => $this->_region,
            'schema' => $handle['protocol'], //协议头部，默认为 http
            'credentials' => array(
                'secretId' => env("COS_SECRETID"),
                'secretKey' => env("COS_SECRETKEY")
            )
        );

        $this->_cos_client = new \Qcloud\Cos\Client($config);

        return $this;
    }


    public function getAuthorization($pathname,$method='GET',$queryParams = array(),$headers = array()){

        $secretId = env("COS_SECRETID"); //"云 API 密钥 SecretId";
        $secretKey = env("COS_SECRETKEY"); //"云 API 密钥 SecretKey";

        // 获取个人 API 密钥 https://console.qcloud.com/capi
        $sid = $secretId;
        $skey = $secretKey;

        // 工具方法
        function getObjectKeys($obj)
        {
            $list = array_keys($obj);
            sort($list);
            return $list;
        }

        function obj2str($obj)
        {
            $list = [];
            $keyList = getObjectKeys($obj);
            $len = count($keyList);
            for ($i = 0; $i < $len; $i++) {
                $key = $keyList[$i];
                $val = isset($obj[$key]) ? $obj[$key] : '';
                $key = strtolower($key);
                $key = urlencode($key);
                $list[] = $key . '=' . urlencode($val);
            }
            return implode('&', $list);
        }

        // 签名有效起止时间
        $now = time() - 1;
        $expired = $now + 600; // 签名过期时刻，600 秒后

        // 要用到的 Authorization 参数列表
        $qSignAlgorithm = 'sha1';
        $qAk = $sid;
        $qSignTime = $now . ';' . $expired;
        $qKeyTime = $now . ';' . $expired;
        $qHeaderList = strtolower(implode(';', getObjectKeys($headers)));
        $qUrlParamList = strtolower(implode(';', getObjectKeys($queryParams)));

        // 签名算法说明文档：https://www.qcloud.com/document/product/436/7778
        // 步骤一：计算 SignKey
        $signKey = hash_hmac("sha1", $qKeyTime, $skey);

        // 步骤二：构成 FormatString
        $formatString = implode("\n", array(strtolower($method), $pathname, obj2str($queryParams), obj2str($headers), ''));

        // 步骤三：计算 StringToSign
        $stringToSign = implode("\n", array('sha1', $qSignTime, sha1($formatString), ''));

        // 步骤四：计算 Signature
        $qSignature = hash_hmac('sha1', $stringToSign, $signKey);

        // 步骤五：构造 Authorization
        $authorization = implode('&',array(
            'q-sign-algorithm=' . $qSignAlgorithm,
            'q-ak=' . $qAk,
            'q-sign-time=' . $qSignTime,
            'q-key-time=' . $qKeyTime,
            'q-header-list=' . $qHeaderList,
            'q-url-param-list=' . $qUrlParamList,
            'q-signature=' . $qSignature
        ));

        return $authorization;

    }

    private function _handleCosUrl($url){
        $res=[];
        $parse=parse_url($url);
        $res['protocol']=$parse['scheme'];
        $res['key']=$parse['path'];

        $host=explode('.',$parse['host']);
        $res['region']=$host[2];
        $res['bucket']=$host[0];

        return $res;
    }

    /**
     * 获取对象元数据
     * @param $bucket_host
     * @param $key
     * @return array
     */
    public function headObj($bucket_host,$key){
        $obj = $this->_cos_client->headObject([
            'Bucket' => $this->_bucket,
            'Key' => $key
        ]);
        return $obj->toArray();
    }

    public function genSignedUrl(array $param)
    {
        return $this->_genSignUrl($param['object'],$param['timeout']);
    }

    private function _genSignUrl($key, $expire){

### 使用封装的 getObjectUrl 获取下载签名
        try {
            $bucket =  $this->_bucket; //存储桶，格式：BucketName-APPID
            $signedUrl = $this->_cos_client->getObjectUrl($bucket, $key, '+'.$expire.' seconds'); //签名的有效时间
            // 请求成功
            return $signedUrl;
        } catch (\Exception $e) {
            // 请求失败
            E($e);
        }
    }

    public function formatHeicToJpg(string $url):string{
        return self::combineImgOpt($url, 'format/jpg');
    }

    public function resizeImg(string $url, string $width = '', string $height = ''):string
    {
        return self::combineImgOpt($url, 'thumbnail/'.$width.'x'.$height);
    }

    public function combineImgOpt(string $url, string $img_opt):string
    {
        return Common::combineCosUrlImgOpt($url, $img_opt);
    }

    public function extraObject(?array $params = []){
        $body = file_get_contents('php://input');
        parse_str($body, $body_arr);

        $body_arr = $this->headObj($params['cos_host'],$params['cb_key']);
        $body_arr['filename'] = $params['cb_key'];
        $body_arr['mimeType'] = $this->_extraObjectMimeType($body_arr);

        return $body_arr;
    }

    public function policyGet($type){
        $config = C('UPLOAD_TYPE_' . strtoupper($type));
        $host = $config['cos_host']; //"";

        $ext='';
        if (I('get.title') && strpos(I('get.title'),'.')!==false){
            $ext = '.'.pathinfo(urldecode(I('get.title')),PATHINFO_EXTENSION);
        }

        $dir=Common::genObjectName($config,$ext);

        $pathname=$dir;
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

        $authorization=$this->getAuthorization($pathname,'POST');

        return [
            'url'=>$host.$pathname,
            'authorization'=>$authorization,
            'params'=>[
                'key'=>$dir,
                'success_action_redirect'=>Common::getCbUrlByType($type, $this->vendor_type, I('get.title'), I('get.hash_id'), I('get.resize')),
            ]
        ];
    }

    public function genClient(string $type){
        return self::getCosClient($type);
    }

    private function _extraObjectMimeType(?array $params = []){
        return $params['ContentType'];
    }

    public function extraFile(array $config, array $body_arr):array{
        if ($body_arr['title']){
            $file_data['title'] = $body_arr['title'];
        }else {
            $array = explode('/', $body_arr['Key']);
            $file_data['title'] = end($array);
        }

        $file_data['url'] = $config['cos_host'] . '/' . $body_arr['Key'];
        $file_data['size'] = $body_arr['ContentLength'];
        $file_data['security'] = $config['security'] ? 1 : 0;
        $file_data['file'] = '';

        return $file_data;
    }
}