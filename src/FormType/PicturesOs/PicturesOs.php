<?php
namespace FormItem\ObjectStorage\FormType\PicturesOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;
use FormItem\ObjectStorage\Lib\CommonItemProp;

class PicturesOs implements FormType {

    use TUploadConfig;
    use CommonItemProp;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'image');

        $view = new View();

        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());

        self::commonAssign($upload_type_cls, $view, $form_type['options']['vendor_type']);

        $content = $view->fetch(__DIR__ . '/pictures_os.html');
        return $content;
    }
}