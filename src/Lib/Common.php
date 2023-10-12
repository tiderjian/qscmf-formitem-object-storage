<?php

namespace FormItem\ObjectStorage\Lib;

use FormItem\ObjectStorage\Lib\Vendor\Context;
use FormItem\ObjectStorage\Lib\Vendor\IVendor;

class Common
{

    public static function extraTypeByUrl(string $url): ?string
    {
        if (!isUrl($url)){
            return null;
        }

        if (self::isOss($url)){
            return Context::VENDOR_ALIYUN_OSS;
        }

        if (self::isCos($url)){
            return Context::VENDOR_TENGXUN_COS;
        }

        if (self::isTos($url)){
            return Context::VENDOR_VOLCENGINE_TOS;
        }

        return null;
    }

    public static function isOs(string $url):bool{
        return self::extraTypeByUrl($url) !== null;
    }

    public static function isOss(string $url):bool{
        $host = env("ALIOSS_HOST");
        return $host && strpos($url, $host) !== false;
    }

    public static function isCos(string $url):bool{
        $host = env("COS_HOST");
        return $host && strpos($url, $host) !== false;
    }

    public static function isTos(string $url):bool{
        $host = env("VOLC_HOST");
        return $host && strpos($url, $host) !== false;
    }

    public static  function intersectMimeType(string $format, string $mime_type):string{
        if (empty($format)){
            return $mime_type;
        }
        $guess_mime_type = (new \Symfony\Component\Mime\MimeTypes())->getMimeTypes($format);
        if ($guess_mime_type){
            return $guess_mime_type[0];
        }

        return $mime_type;
    }

    public static  function getMaxSize($type){
        return (new UploadConfig($type))->getMaxSize();
    }

    public static function isHeic($mime_type):bool{
        return in_array($mime_type,self::getHeicMimeTypes());
    }

    public static function getHeicMimeTypes():array{
        return (new \Symfony\Component\Mime\MimeTypes())->getMimeTypes('heic');
    }

    public static function getVendorType(string $type = '', ?string $vendor_type = ''):string{
        if ($vendor_type){
            return $vendor_type;
        }

        if ($type && $config_vendor_type = (new UploadConfig($type))->getVendorType()){
            return $config_vendor_type;
        }

        return env("OS_VENDOR_TYPE");
    }

    public static function getCbUrlByType(string $type, string $vendor_type, string $title = '', string $hash_id = '', string $resize = ''):string{
        $params = ['type'=>$type, 'vendor_type' => $vendor_type];
        $title && $params['title'] = $title;
        $hash_id && $params['hash_id'] = $hash_id;
        $resize && $params['resize'] = $resize;
        return U('/extends/ObjectStorage/callBack',$params,true,true);
    }

    public static function genObjectName($config, $ext = ''):string{
        $sub_name = self::getName($config['subName']);
        $pre_path = $config['rootPath'] . $config['savePath'] . $sub_name .'/';
        $save_name = self::getName($config['saveName']);
        $dir = trim(trim($pre_path . $save_name, '.'), '/');
        if($ext){
            $dir .= $ext;
        }
        return $dir;
    }

    public static function getName($rule):string{
        $name = '';
        if(is_array($rule)){ //数组规则
            $func     = $rule[0];
            $param    = (array)$rule[1];
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)){ //字符串规则
            if(function_exists($rule)){
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

    public static function getFileByHash(string $hash_id, IVendor $os_cls, string $resize = ''):?array{
        $file_data = D("FilePic")->where(['hash_id' => $hash_id])->find();
        if ($file_data){
            $file_data = self::handleCbRes($file_data, $os_cls, $resize);
        }

        return $file_data;
    }

    public static function handleCbRes(array $file_data, IVendor $os_cls, ?string $resize = ''):array{
        if($file_data['security'] == 1){
            $parse=parse_url($file_data['url']);
            $params = [
                'object' => $parse['path'],
                'timeout' => 60
            ];
            $file_data['url'] = $os_cls->genClient($file_data['cate'])->genSignedUrl($params);
        }
        \Think\Hook::listen('heic_to_jpg', $file_data);

        if($resize === '1'){
            $file_data['small_url'] = $os_cls->resizeImg($file_data['url'], '40','40');
        }

        return $file_data;
    }

    public static function genItemDataUrl(string $type, ?string $vendor_type = '', ?array $custom_params = []):array{
        return self::genPolicyDataUrl($type, $vendor_type, $custom_params);
    }

    public static function genPolicyDataUrl(string $type, ?string $vendor_type = '', ?array $custom_params = []):array{
        return self::combinePolicyDataUrl('/extends/objectStorage/policyGet', $type, $vendor_type, $custom_params);
    }

    public static function combinePolicyDataUrl(string $method_path, string $type, ?string $vendor_type = '', ?array $custom_params = []):array{
        $param = $custom_params;
        $param['type'] = $type;
        $param['vendor_type'] = self::getVendorType($type,$vendor_type);

        return [U($method_path, $param), $param['vendor_type']];
    }

    public static function combineOssUrlImgOpt(string $url, string $img_opt):string
    {
        $oss_handle_prefix = 'x-oss-process=image';

        return self::combineOsUrlImgOpt($oss_handle_prefix, $url, $img_opt);
    }

    public static function combineCosUrlImgOpt(string $url, string $img_opt):string
    {
        $oss_handle_prefix = 'imageMogr2';

        return self::combineOsUrlImgOpt($oss_handle_prefix, $url, $img_opt);
    }

    public static function combineTosUrlImgOpt(string $url, string $img_opt):string
    {
        $oss_handle_prefix = 'x-tos-process=image';

        return self::combineOsUrlImgOpt($oss_handle_prefix, $url, $img_opt);
    }

    public static function combineOsUrlImgOpt(string $handle_prefix, string $url, string $img_opt):string
    {
        $img_opt = str_replace($handle_prefix.'/', '', $img_opt);
        if (empty($img_opt)) {
            return $url;
        }
        $img_opt = '/'.$img_opt;

        $has_img_opt = strpos($url, $handle_prefix);
        if ($has_img_opt === false){
            $has_query = strpos($url, '?');
            $join_str = $has_query === false ? '?' : '&';
            $url .= $join_str.$handle_prefix.$img_opt;
        }else{
            $url .= $img_opt;
        }

        return $url;
    }

    public static function checkUploadConfig(IVendor $vendor_cls):bool{
        $config_cls = $vendor_cls->getUploadConfig();
        $config = $config_cls->getAll();
        $type = $config_cls->getType();

        if(!$config){
            E('上传类型' . $type . '不存在!');
        }

        $vendor_type = $vendor_cls->vendor_type;
        if(!$config[$vendor_cls->getShowHostKey()]){
            E($type . '这不是'.$vendor_type.'上传配置类型!');
        }

        return true;
    }
}