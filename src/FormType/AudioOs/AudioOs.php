<?php
namespace FormItem\ObjectStorage\FormType\AudioOs;

use FormItem\ObjectStorage\Lib\FormTypeItemProp;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;
use FormItem\ObjectStorage\Lib\TUploadConfig;

class AudioOs implements FormType {

    use TUploadConfig;
    use FormTypeItemProp;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'audio');

        $view = new View();
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());

        self::commonAssign($upload_type_cls, $view, $form_type);

        $content = $view->fetch(__DIR__ . '/audio_os.html');
        return $content;
    }
}