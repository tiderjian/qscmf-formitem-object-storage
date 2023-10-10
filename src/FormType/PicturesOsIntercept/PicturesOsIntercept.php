<?php
namespace FormItem\ObjectStorage\FormType\PicturesOsIntercept;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class PicturesOsIntercept implements FormType {
    use TUploadConfig;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'image');

        list($data_url, $vendor_type) =  Common::genItemDataUrl($upload_type_cls->getType(),
            Common::getVendorType($upload_type_cls->getType(), $form_type['options']['vendor_type'])
            );

        $view = new View();
        $view->assign('data_url', $data_url);
        $view->assign('form', $form_type);
        $view->assign('vendor_type', $vendor_type);

        $content = $view->fetch(__DIR__ . '/pictures_os_intercept.html');
        return $content;
    }
}