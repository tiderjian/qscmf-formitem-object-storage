<?php
namespace FormItem\ObjectStorage\Lib;

use Think\View;

trait CommonItemProp {

    public static function commonAssign(UploadConfig $upload_type_cls, array $form_options, View &$view){
        list($data_url, $vendor_type) =  Common::genItemDataUrl($upload_type_cls->getType(),
            Common::getVendorType($upload_type_cls->getType(), $form_options['options']['vendor_type'])
            );

        $view->assign('data_url', $data_url);
        $view->assign('vendor_type', $vendor_type);

    }
}