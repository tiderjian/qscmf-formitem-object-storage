<?php
namespace FormItem\ObjectStorage\FormType\FilesOs;

use FormItem\ObjectStorage\Lib\Common;
use Illuminate\Support\Str;
use Qscmf\Builder\FormType\FormType;
use Think\View;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Qscmf\Builder\FormType\FileFormType;

class FilesOs extends FileFormType implements FormType{
    use TUploadConfig;

    public function build(array $form_type){
        $form_type['options'] = (array)$form_type['options'];
        $upload_type_cls = $this->genUploadConfigCls($form_type['extra_attr'],'file');

        $view = new View();
        if($form_type['value']){
            $files = [];
            foreach(explode(',', $form_type['value']) as $file_id){
                $data = [];
                $data['url'] = U('/extends/ObjectStorage/download', ['file_id'=>$form_type['value']], '', true);

                $data['id'] = $file_id;
                if($this->needPreview(showFileUrl($file_id))){
                    $data['preview_url'] = $this->genPreviewUrl(showFileUrl($file_id));
                }
                $files[] = $data;
            }


            $view->assign('files', $files);
        }

        $view->assign('data_url', Common::genItemDataUrl($form_type, 'file'));
        $view->assign('form', $form_type);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());
        $view->assign('js_fn', $this->buildJsFn());
        $view->assign('vendor_type',  Common::getVendorType('file', $form_type['options']['vendor_type']));

        $content = $view->fetch(__DIR__ . '/files_os.html');
        return $content;
    }
}