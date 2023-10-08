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
        return strpos($url, $host) !== false;
    }

    public static function isCos(string $url):bool{
        $host = env("COS_HOST");
        return strpos($url, $host) !== false;
    }

    public static function isTos(string $url):bool{
        $host = env("TOS_HOST");
        return strpos($url, $host) !== false;
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

    public static function getFileByHash(string $hash_id, $os_cls = '', string $resize = ''):?array{
        $file_data = D("FilePic")->where(['hash_id' => $hash_id])->find();
        if ($file_data){
            $file_data = self::handleCbRes($file_data, $os_cls, $resize);
        }

        return $file_data;
    }

    public static function handleCbRes(array $file_data, ?IVendor $os_cls = null, ?string $resize = ''):array{
        !$os_cls && $os_cls = Context::genVendorByUrl($file_data['url']);
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

    public static function genItemDataUrl(array $form_type, string $type, ?array $custom_params = []):string{
        $param = $custom_params;
        $param['type'] = $type;
        if ($form_type['options']['vendor_type']){
            $param['vendor_type'] = $form_type['options']['vendor_type'];
        }
        return U('/extends/objectStorage/policyGet', $param);
    }
}