## Context 供应商类

#### genVendorByType
```text
生成供应商实例
```

| 参数名称         | 类型    | 是否必填 | 备注                           |
|-----------------|---------|------|--------------------------------|
| vendor_type     | string  | 是    | 供应商类型           |

**返回值**

FormItem\ObjectStorage\Lib\Vendor\IVendor

#### genClient
```text
生成供应商SDK的实例 
```

| 参数名称         | 类型    | 是否必填 | 备注                          |
|-----------------|---------|----------|-------------------------------|
| type            | string  | 是       | 与 upload_config.php 中 UPLOAD_TYPE_XXX 的 XXX 对应，如图片 image；文件 file                         |


**返回值**

FormItem\ObjectStorage\Lib\Vendor\IVendor


#### uploadFile
```text
服务器上传文件
```

| 参数名称       | 类型     | 是否必填 | 备注                                  |
|------------|--------|------|-------------------------------------|
| file_path  | string | 是    | 文件绝对路径                              |
| object     | string | 是    | 文件上传至云服务商的 key                      |
| options    | array  | 否    | 额外参数,可设置 HTTP 标准头域,如 Content-Type 等 |

**返回值**

###### 正常
返回参数 $object ,如 Uploads/image/xxx/xxx.jpg

###### 异常
以接口实际返回值为准

```php
$os_vendor->genClient('image')->uploadFile($file_path, $object, $options);
```