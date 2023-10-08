<?php
namespace FormItem\ObjectStorage\Behaviors;

use FormItem\ObjectStorage\Lib\Vendor\Context;

class SecurityUrlBehavior{

    public function run(&$params)
    {
        if(isset($params['auth_url'])){
            return $params['auth_url'];
        }

        $file_ent = $params['file_ent'];
        $os_cls =  Context::genVendorByUrl($file_ent['url']);
        if ($os_cls){
            $config = C('UPLOAD_TYPE_' . strtoupper($file_ent['cate']));
            $object = trim(str_replace($config[$os_cls->getShowHostKey()], '', $file_ent['url']), '/');

            $url = $os_cls->genClient($file_ent['cate'])->genSignedUrl(['object' => $object, 'timeout' => $params['timeout']]);
            if($url){
                $params['auth_url'] = $url;
            }
        }
    }
}