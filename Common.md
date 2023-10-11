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



**返回值**

索引数组，第一个值为请求地址，第二个值为供应商信息



```php
list($data_url, $vendor_type) = Common::genPolicyDataUrl('image');

// 返回值
// ['/extends/objectStorage/policyGet/type/image/vendor_type/volcengine_tos.html', 'volcengine_tos']

list($data_url, $vendor_type) = Common::genPolicyDataUrl('image','tengxun_cos',['resize' => 1]);

// 返回值
// ['/extends/objectStorage/policyGet/resize/1/type/image/vendor_type/tengxun_cos.html','tengxun_cos']
```


#### combinePolicyDataUrl
```text
生成上传请求所需参数和供应商信息，支持自定义请求 method 路径
```

| 参数名称         | 类型    | 是否必填 | 备注                          |
|-----------------|---------|----------|-------------------------------|
| method_path     | string  | 是       | 请求路径                      |
| type            | string  | 是       | 类型                          |
| vendor_type     | string  | 否       | 供应商类型         |
| custom_params   | array   | 否       | 自定义参数             |



**返回值**

索引数组，第一个值为请求地址，第二个值为供应商信息



```php
list($data_url, $vendor_type) = Common::combinePolicyDataUrl('/extends/objectStorage/policyGet', 'image','volcengine_tos',['resize' => 1]);

// 返回值
// ['/extends/objectStorage/policyGet/resize/1/type/image/vendor_type/volcengine_tos.html', 'volcengine_tos']
```
