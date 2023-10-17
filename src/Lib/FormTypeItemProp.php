<?php
namespace FormItem\ObjectStorage\Lib;

use Think\View;

trait FormTypeItemProp {

    public static function commonAssign(UploadConfig $upload_type_cls, View &$view,?array $option = []
        , ?array $custom_params = []){
        list($data_url, $vendor_type, $cacl_file_hash) =  Common::genItemDataUrl($upload_type_cls->getType(),
            $option['options']['vendor_type']??null,$custom_params, $option['options']['file_double_check']??null
        );

        $view->assign('data_url', $data_url);
        $view->assign('vendor_type', $vendor_type);
        $view->assign('cacl_file_hash', $cacl_file_hash);

    }
}