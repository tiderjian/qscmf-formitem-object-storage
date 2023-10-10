<?php

namespace FormItem\ObjectStorage\Behaviors;

use FormItem\ObjectStorage\Lib\File;
use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\Vendor\Context;

class HeicToJpgBehavior{

    protected $file;

    public function run(&$params)
    {
        if($params['url'] && $params['mime_type']){
            $this->file = new File($params['url'], $params['mime_type'], $params['vendor_type']);
            $this->formatHeicToJpg();
            $params['url'] = $this->file->getUrl();
        }
    }

    protected function formatHeicToJpg(){
        $url = $this->file->getUrl();
        $os_cls = Context::genVendorByType($this->file->getVendorType());
        if ($os_cls && $this->isHeic()) {
            $this->file->setUrl($os_cls->formatHeicToJpg($url));
        }
    }

    protected function isHeic():bool{
        return Common::isHeic($this->file->getMimeType());
    }

}