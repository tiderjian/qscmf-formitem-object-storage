# qscmf-formitem-object-storage
上传文件至云服务商的一体化组件

#### 安装

```php
composer require quansitech/qscmf-formitem-object-storage
```



#### 特性

1. 支持上传至不同供应商
2. 支持文件查重，避免重复上传同一个文件



#### 使用

+ 配置使用的供应商，若同时配置，则优先级按顺序依次降低
  
  + 调用组件时设置 *options*
  
    ```php
    // 使用formItem
    ->addFormItem("picture_cos", "picture_os", "封面cos","",['vendor_type' => 'tengxun_cos'])
    // 使用columnItem
    ->addTableColumn("picture", "封面tos", 'picture_os', ['vendor_type' => 'volcengine_tos'], true)
    ```
  
  + 修改 *upload_config.php* 的上传配置
  
    ```php
    /* 图片上传相关配置 */
    'UPLOAD_TYPE_IMAGE' => array(
        // 其他配置省略
        'oss_host' => env("ALIOSS_HOST"),
        'oss_meta' => array('Cache-Control' => 'max-age=2592000'),
        'vendorType' => 'aliyun_oss',
    ),
    ```
  
  + 添加 *env* *OS_VENDOR_TYPE*，全局配置
  
    ```php
    OS_VENDOR_TYPE=aliyun_oss
    ```
  
+ 供应商可选值及其相关配置

  | 名称           | 供应商   |
  | -------------- | -------- |
  | aliyun_oss     | 阿里云   |
  | tengxun_cos    | 腾讯云   |
  | volcengine_tos | 火山引擎 |

  

  - **aliyun_oss**

    - *env* 配置

      | 名称                     |
      | ------------------------ |
      | ALIOSS_ACCESS_KEY_ID     |
      | ALIOSS_ACCESS_KEY_SECRET |
      | ALIOSS_HOST              |

      

    - *upload_config.php* 需要添加的配置

      | 名称            | 是否必填 | 备注       |
      | --------------- | -------- | ---------- |
      | oss_host        | 是       |            |
      | upload_oss_host | 否       | 上传用域名 |

      

  - **tengxun_cos**

    - *env* 配置

      | 名称          |
      | ------------- |
      | COS_SECRETID  |
      | COS_SECRETKEY |
      | COS_HOST      |

      

    - *upload_config.php* 需要添加的配置

      | 名称     | 是否必填 | 备注 |
      | -------- | -------- | ---- |
      | cos_host | 是       |      |

      

  - **volcengine_tos**

    - *env* 配置

      | 名称            |
      | --------------- |
      | VOLC_ACCESS_KEY |
      | VOLC_SECRET_KEY |
      | VOLC_HOST       |

      

    - *upload_config.php* 需要添加的配置

      | 名称     | 是否必填 | 备注 |
      | -------- | -------- | ---- |
      | tos_host | 是       |      |



#### 支持组件

##### ColumnItem

- 上传文件：file_os

  ```php
  ->addTableColumn("file_id", "单个文件", 'file_os', '', true)
  ```

  

- 上传图片：picture_os

  ```php
  ->addTableColumn("picture", "封面tos", 'picture_os', '', true)
  ```

  

##### FormItem

- 上传音频：audio_os/audios_os

  ```php
  ->addFormItem('audio_id', 'audio_os', '单个音频')
  ->addFormItem('audios_id', 'audios_os', '多个音频')
  ```

  

- 上传文件：file_os/files_os

  ```php
  ->addFormItem('file_id', 'file_os', '单个文件')
  ->addFormItem('files_id', 'files_os', '多个文件')
  ```

  

- 上传图片：picture_os/pictures_os

  ```php
  ->addFormItem('picture_id', 'picture_os', '单张图片')
  ->addFormItem('pictures_id', 'pictures_os', '多张图片')
  
  $options = ['process'=>'?x-oss-process=image/resize,m_fill,w_300,h_200'] //其中w_300,h_200为参数宽300高200，请参考不同供应商图片处理参数，根据实际需求填写
  // 如表单没有显示缩略图需求，$options可以不传，$options为空时显示原图
  ->addFormItem('picture_id', 'picture_os', '单张图片', '',$options , '', '')
  ```

  

- 上传裁剪后的图片：picture_os_intercept/pictures_os_intercept

  ```php
  $option = [
    'type' => 'image', // 默认值
    'width' => 1, // 裁剪框宽高比例，此为宽度，默认为1
    'height' => 1 // 裁剪框宽高比例，此为高度，默认1
  ];
  
  // 如没有特别需求，$option可不传
  ->addFormItem('cover', 'picture_os_intercept', '单张裁剪后的图片', '', $option)
  ->addFormItem('covers', 'pictures_os_intercept', '多张裁剪后的图片')
  ```

  

