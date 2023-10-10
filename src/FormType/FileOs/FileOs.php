<?php
namespace FormItem\ObjectStorage\FormType\FileOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FileFormType;
use Qscmf\Builder\FormType\FormType;
use Think\View;
use FormItem\ObjectStorage\Lib\CommonItemProp;

class FileOs extends FileFormType implements FormType{
    use TUploadConfig;
    use CommonItemProp;

    public function build(array $form_type){
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'file');
        $view = new View();
        if($form_type['value']){
            $file['id'] = $form_type['value'];
            $file['url'] = U('/extends/objectStorage/download', ['file_id'=>$form_type['value']], '', true);

            if($this->needPreview(showFileUrl($form_type['value']))){
                $file['preview_url'] = $this->genPreviewUrl(showFileUrl($form_type['value']));
            }

            $view->assign('file', $file);
        }

        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());
        $view->assign('js_fn', $this->buildJsFn());

        self::commonAssign($upload_type_cls, $view, $form_type['options']['vendor_type']);

        $content = $view->fetch(__DIR__ . '/file_os.html');
        return $content;
    }
}