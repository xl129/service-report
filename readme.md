### 加载
```
composer require yuanxinhealthy/service-report

php bin/hyperf.php vendor:publish yuanxinhealthy/service-report
```
### 配置
* 配置config/autoload/server.php
```php
'server'       => [
    'settings' => [
        Constant::OPTION_ADMIN_SERVER => '0.0.0.0:9502', // 新增
    ],
],
```