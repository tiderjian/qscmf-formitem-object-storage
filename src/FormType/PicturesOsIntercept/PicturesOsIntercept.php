<?php
namespace FormItem\ObjectStorage\FormType\PicturesOsIntercept;

use FormItem\ObjectStorage\Lib\Common;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class PicturesOsIntercept implements FormType {

    public function build(array $form_type){

        $view = new View();
        $view->assign('data_url', Common::genItemDataUrl($form_type, 'image'));
        $view->assign('form', $form_type);
        $view->assign('vendor_type',  Common::getVendorType('image', $form_type['options']['vendor_type']));

        $content = $view->fetch(__DIR__ . '/pictures_os_intercept.html');
        return $content;
    }
}