<?php
namespace FormItem\ObjectStorage\ColumnType\PictureOs;

use FormItem\ObjectStorage\Lib\Common;
use FormItem\ObjectStorage\Lib\TUploadConfig;
use FormItem\ObjectStorage\Lib\Vendor\Context;
use Qscmf\Builder\ColumnType\ColumnType;
use Illuminate\Support\Str;
use Qscmf\Builder\ColumnType\EditableInterface;
use Qscmf\Builder\ButtonType\Save\TargetFormTrait;
use Think\View;

class PictureOs extends ColumnType implements EditableInterface {

    use TUploadConfig;
    use TargetFormTrait;

    public function build(array &$option, array $data, $listBuilder){
        $view = new View();
        $image = [
            'url' => showFileUrl($data[$option['name']]),
        ];

        $upload_type_cls = $this->genUploadConfigCls($option['extra_attr'],'image');
        $vendor_type = Common::getVendorType($upload_type_cls->getType(), $option['options']['vendor_type']);

        $os_cls = Context::genVendorByType($vendor_type);
        $image['small_url'] = $os_cls->resizeImg($image['url'], '40','40');

        $view->assign('image', $image);
        $content = $view->fetch(__DIR__ . '/picture_os.html');
        return $content;
    }

    public function editBuild(array &$option, array $data, $listBuilder){
        $class = $this->getSaveTargetForm();

        $image = [
            'url' => showFileUrl($data[$option['name']]),
        ];

        $upload_type_cls = $this->genUploadConfigCls($option['extra_attr'],'image');
        list($data_url, $vendor_type) =  Common::genItemDataUrl($upload_type_cls->getType(),
            Common::getVendorType($upload_type_cls->getType(), $option['options']['vendor_type'])
            ,['resize' => '1']);

        $os_cls = Context::genVendorByType($vendor_type);
        $image['small_url'] = $os_cls->resizeImg($image['url'], '40','40');

        $view = new View();
        $view->assign('data_url', $data_url);
        $view->assign('option', $option);
        $view->assign('image', $image);
        $view->assign('class', $class);
        $view->assign('image_id', $data[$option['name']]);
        $view->assign('gid', Str::uuid()->getHex());
        $view->assign('file_ext',  $upload_type_cls->getExts());
        $view->assign('vendor_type',  $vendor_type);
        $content = $view->fetch(__DIR__ . '/picture_os_editable.html');
        return $content;
    }
}