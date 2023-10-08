<?php
namespace FormItem\ObjectStorage\ColumnType\FileOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use Qscmf\Builder\ColumnType\ColumnType;
use Illuminate\Support\Str;
use Qscmf\Builder\ColumnType\EditableInterface;
use Qscmf\Builder\ButtonType\Save\TargetFormTrait;
use Think\View;

class FileOs extends ColumnType implements EditableInterface {

    use TUploadConfig;
    use TargetFormTrait;

    public function build(array &$option, array $data, $listBuilder){
	    $view = new View();
	    $file = [
		    'url' => showFileUrl($data[$option['name']]),
		    'name' => showFileTitle($data[$option['name']]),
	    ];
	    $view->assign('file', $file);
	    $view->assign('gid', Str::uuid()->getHex());
	    $content = $view->fetch(__DIR__ . '/file_os.html');
	    return $content;
    }

    public function editBuild(array &$option, array $data, $listBuilder){
        $class = $this->getSaveTargetForm();

        $file= [
            'url' => showFileUrl($data[$option['name']]),
	        'name' => showFileTitle($data[$option['name']]),
        ];

        $upload_type_cls = $this->genUploadConfigCls($option['extra_attr'],'file');

        $view = new View();
        $view->assign('option', $option);
        $view->assign('file', $file);
        $view->assign('class', $class);
        $view->assign('file_id', $data[$option['name']]);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());
        $view->assign('data_url',  Common::genItemDataUrl($option, 'file'));
        $view->assign('vendor_type',  Common::getVendorType('file', $option['options']['vendor_type']));
        $content = $view->fetch(__DIR__ . '/file_os_editable.html');
        return $content;
    }
}