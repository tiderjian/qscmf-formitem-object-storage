<?php

namespace FormItem\ObjectStorage\Lib\Vendor;

use FormItem\ObjectStorage\Lib\UploadConfig;

interface IVendor
{

    public function getVendorType():string;

    public function setVendorConfig(array $config):self;

    public function getVendorConfig():VendorConfig;

    public function setUploadConfig(string $type, ?array $config = []):self;

    public function getUploadConfig():UploadConfig;

    public function setBucket(string $bucket):self;

    public function setEndPoint(string $endPoint):self;

    public function getUploadHost(array $config):string;

    public function genClient(string $type, ?bool $check_config = true);

    public function getClient();

    public function genSignedUrl(array $param);

    public function policyGet(string $type);

    public function extraObject(?array $params = []);

    public function formatHeicToJpg(string $url):string;

    public function resizeImg(string $url, string $width = '', string $height = ''):string;

    public function combineImgOpt(string $url, string $img_opt):string;

    public function extraFile(array $config, array $body_arr):array;

    public function uploadFile(string $file_path, ?string $object_name = '', ?array $header_options = []);

}