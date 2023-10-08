<?php
namespace FormItem\ObjectStorage\FormType\PictureOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class PictureOs implements FormType {
    use TUploadConfig;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'image');

        $view = new View();
        $view->assign('data_url', Common::genItemDataUrl($form_type, 'image'));
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());
        $view->assign('vendor_type',  Common::getVendorType('image', $form_type['options']['vendor_type']));

        $content = $view->fetch(__DIR__ . '/picture_os.html');
        return $content;
    }
}