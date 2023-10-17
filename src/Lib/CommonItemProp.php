<?php
namespace FormItem\ObjectStorage\Lib;

use Think\View;

trait CommonItemProp {

    public static function commonAssign(UploadConfig $upload_type_cls, View &$view,?string $vendor_type = ''){
        list($data_url, $vendor_type) =  Common::genItemDataUrl($upload_type_cls->getType(),
            $vendor_type
        );

        $view->assign('data_url', $data_url);
        $view->assign('vendor_type', $vendor_type);
        $view->assign('cacl_file_hash', Common::needCaclFileHash());

    }
}