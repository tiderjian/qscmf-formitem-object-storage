<?php
namespace FormItem\ObjectStorage\Behaviors;

use FormItem\ObjectStorage\Lib\File;
use FormItem\ObjectStorage\Lib\Vendor\Context;

class SecurityUrlBehavior{
    protected $file;

    public function run(&$params)
    {
        if(isset($params['auth_url'])){
            return $params['auth_url'];
        }

        $file_ent = $params['file_ent'];
        $this->file = new File($file_ent['url'], $file_ent['mime_type'], $file_ent['vendor_type']);

        $os_cls = Context::genVendorByType($this->file->getVendorType());
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