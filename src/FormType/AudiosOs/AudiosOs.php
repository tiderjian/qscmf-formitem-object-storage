<?php
namespace FormItem\ObjectStorage\FormType\AudiosOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\CommonItemProp;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;

class AudiosOs implements FormType {

    use TUploadConfig;
    use CommonItemProp;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'audio');

        $view = new View();
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());

        self::commonAssign($upload_type_cls, $form_type, $view);

        $content = $view->fetch(__DIR__ . '/audios_os.html');
        return $content;
    }
}