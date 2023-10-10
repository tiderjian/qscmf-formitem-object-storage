<?php
namespace FormItem\ObjectStorage\FormType\PictureOsIntercept;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Qscmf\Builder\FormType\FormType;
use Think\View;
use FormItem\ObjectStorage\Lib\CommonItemProp;

class PictureOsIntercept implements FormType {
    use TUploadConfig;
    use CommonItemProp;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'image');

        $view = new View();


        $view->assign('form', $form_type);

        self::commonAssign($upload_type_cls, $form_type, $view);

        $content = $view->fetch(__DIR__ . '/picture_os_intercept.html');
        return $content;
    }
}