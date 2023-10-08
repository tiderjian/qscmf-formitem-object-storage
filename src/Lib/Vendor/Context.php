<?php

namespace FormItem\ObjectStorage\Lib\Vendor;

use FormItem\ObjectStorage\Lib\Common;

class Context
{
    const VENDOR_ALIYUN_OSS = 'aliyun_oss';
    const VENDOR_TENGXUN_COS = 'tengxun_cos';
    const VENDOR_VOLCENGINE_TOS = 'volcengine_tos';

    public static function genVendorByType(string $vendor_type):?IVendor{
        return self::genByType($vendor_type);
    }

    public static function genVendorByUrl(string $url):?IVendor{
        $vendor_type = Common::extraTypeByUrl($url);
        return $vendor_type ? self::genByType($vendor_type) : null;
    }

    private static function genByType(string $vendor_type):IVendor{
        $cls = '';
        switch ($vendor_type){
            case self::VENDOR_ALIYUN_OSS:
                $cls = new AliyunOss();
                break;
            case self::VENDOR_TENGXUN_COS:
                $cls = new TengxunCos();
                break;
            case self::VENDOR_VOLCENGINE_TOS:
                $cls = new VolcengineTos();
                break;
        }

        return $cls;
    }

}