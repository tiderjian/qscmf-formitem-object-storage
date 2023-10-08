<?php

namespace FormItem\ObjectStorage\Lib\Vendor;;

interface IVendor
{

    public function genClient(string $type);

    public function genSignedUrl(array $param);

    public function policyGet(string $type);

    public function extraObject(?array $params = []);

    public function formatHeicToJpg(string $url):string;

    public function resizeImg(string $url, string $width = '', string $height = ''):string;

    public function combineImgOpt(string $url, string $img_opt):string;

    public function extraFile(array $config, array $body_arr):array;

}