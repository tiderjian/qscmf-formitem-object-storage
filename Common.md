## Common 帮助函数类

#### genPolicyDataUrl
```text
生成上传请求所需参数和供应商信息
```

| 参数名称         | 类型    | 是否必填 | 备注                           |
|-----------------|---------|----------|--------------------------------|
| type            | string  | 是       | 类型                           |
| vendor_type     | string  | 否       | 供应商类型           |
| custom_params   | array   | 否       | 自定义参数             |
| file_double_check   | int    | 否       | 是否查重，0 否，1 是 |



**返回值**

索引数组，第一个值为请求地址，第二个值为供应商信息，第三个值为是否查重



```php
list($data_url, $vendor_type, $file_double_check) = Common::genPolicyDataUrl('image');

// 返回值
// ['/extends/objectStorage/policyGet/type/image/vendor_type/volcengine_tos/file_double_check/1.html', 'volcengine_tos', 1]

list($data_url, $vendor_type, $file_double_check) = Common::genPolicyDataUrl('image','tengxun_cos',['resize' => 1]);

// 返回值
// ['/extends/objectStorage/policyGet/resize/1/type/image/vendor_type/tengxun_cos/file_double_check/1.html','tengxun_cos', 1]
```


#### combinePolicyDataUrl
```text
生成上传请求所需参数和供应商信息，支持自定义请求 method 路径
```

| 参数名称         | 类型     | 是否必填 | 备注                |
|-----------------|--------|----------|-------------------|
| method_path     | string | 是       | 请求路径              |
| type            | string | 是       | 类型                |
| vendor_type     | string | 否       | 供应商类型             |
| custom_params   | array  | 否       | 自定义参数             |
| file_double_check   | int    | 否       | 是否查重，0 否，1 是 |



**返回值**

索引数组，第一个值为请求地址，第二个值为供应商信息，第三个值为是否查重



```php
list($data_url, $vendor_type, $file_double_check) = Common::combinePolicyDataUrl('/extends/objectStorage/policyGet', 'image','volcengine_tos',['resize' => 1], 0);

// 返回值
// ['/extends/objectStorage/policyGet/resize/1/type/image/vendor_type/volcengine_tos/file_double_check/0.html', 'volcengine_tos', 0]
```

#### needCaclFileHash
```text
是否需要计算文件哈希值，0 关闭，1 开启
```