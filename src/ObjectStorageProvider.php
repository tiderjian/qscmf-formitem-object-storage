<?php
namespace FormItem\ObjectStorage;

use Bootstrap\Provider;
use Bootstrap\LaravelProvider;
use Bootstrap\RegisterContainer;
use FormItem\ObjectStorage\Controller\ObjectStorageController;
use FormItem\ObjectStorage\FormType\AudioOs\AudioOs;
use FormItem\ObjectStorage\FormType\AudiosOs\AudiosOs;
use FormItem\ObjectStorage\FormType\FileOs\FileOs;
use FormItem\ObjectStorage\FormType\FilesOs\FilesOs;
use FormItem\ObjectStorage\FormType\PictureOs\PictureOs;
use FormItem\ObjectStorage\FormType\PictureOsIntercept\PictureOsIntercept;
use FormItem\ObjectStorage\FormType\PicturesOs\PicturesOs;
use FormItem\ObjectStorage\FormType\PicturesOsIntercept\PicturesOsIntercept;
use FormItem\ObjectStorage\ColumnType\PictureOs\PictureOs as ColumnPictureOs;
use FormItem\ObjectStorage\ColumnType\FileOs\FileOs as ColumnFileOs;

class ObjectStorageProvider implements Provider, LaravelProvider {

    public function register(){
        $this->addHook();

        RegisterContainer::registerFormItem('audio_os', AudioOs::class);
        RegisterContainer::registerFormItem('audios_os', AudiosOs::class);
        RegisterContainer::registerFormItem('file_os', FileOs::class);
        RegisterContainer::registerFormItem('files_os', FilesOs::class);
        RegisterContainer::registerFormItem('picture_os', PictureOs::class);
        RegisterContainer::registerFormItem('pictures_os', PicturesOs::class);
        RegisterContainer::registerFormItem('picture_os_intercept', PictureOsIntercept::class);
        RegisterContainer::registerFormItem('pictures_os_intercept', PicturesOsIntercept::class);
        RegisterContainer::registerListColumnType("picture_os",ColumnPictureOs::class);
	    RegisterContainer::registerListColumnType("file_os",ColumnFileOs::class);

        RegisterContainer::registerController('extends', 'ObjectStorage', ObjectStorageController::class);

        RegisterContainer::registerSymLink(WWW_DIR . '/Public/object-storage', __DIR__ . '/../asset/object-storage');

        $this->injectHead();
    }

    protected function addHook(){
        \Think\Hook::add('heic_to_jpg', 'FormItem\\ObjectStorage\\Behaviors\\HeicToJpgBehavior');
        \Think\Hook::add('get_auth_url', 'FormItem\\ObjectStorage\\Behaviors\\SecurityUrlBehavior');
    }

    protected function injectHead(){
        $async = false;
        RegisterContainer::registerHeadJs(__ROOT__ . '/Public/object-storage/plupload-2.3.9/js/plupload.full.min.js', $async);
        RegisterContainer::registerHeadJs(__ROOT__ . '/Public/object-storage/os_upload.js', $async);
        RegisterContainer::registerHeadJs(__ROOT__ . '/Public/object-storage/file-md5-wasm/dist/index.js', $async);
    }

    public function registerLara()
    {
        RegisterContainer::registerMigration(__DIR__.'/migrations');
    }

}