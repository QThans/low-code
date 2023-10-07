# LowCode开发平台

完全开源的低代码开发平台，可以通过全配置+少量代码实现大量管理应用。

[Demo网站](http://81.68.176.146/admin) 测试账户：admin，密码：admin

该项目已被某些公司用于开发内部系统，先将核心开源。有一些发现的、未发现的BUG存在，如果疑问或者遇到了请提交**issue**。

## 教程

目前暂无，可自行摸索，并不难。作者提供付费指导，可扫底部二维码。

## 特点

### 表单拖拽式设计

应用全部推拽而成，列表也都是可配置。

![表单设计](https://img.thans.cn/bpm/image.png)
![表单](https://img.thans.cn/bpm/image-1.png)

### 组件强大的配置能力

组件配置项超多，可以使用JS附加功能。拥有几十个组件库。

![组件配置](https://img.thans.cn/bpm/image5.png)

### 可设计列表

![列表](https://img.thans.cn/bpm/image-2.png)
![数据表格](https://img.thans.cn/bpm/image-3.png)

# Requirements
- PHP 7.4
- PostgreSQL
- fileinfo
- Redis
- pgsql

# Install Steps

1. 创建 **Laravel 8.0** 应用

```
composer create-project --prefer-dist laravel/laravel {替换为项目名称} 7.*
```

2. 进入项目目录

 ```
 cd {替换为项目名称}
 ```

3. 安装本扩展

```
composer require thans/bpm
```


4. 在.env中配置数据库连接。**仅支持PostgreSQL**

```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=lowcode
DB_USERNAME=lowcode
DB_PASSWORD=
```

5. 运行命令：

```php artisan bpm:install```

6. 伪静态配置

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

# Licence

MIT

# 联系方式

![WeChat](https://img.thans.cn/wechat.jpg)

# Roadmap

1. 升级Laravel和Dcat-Admin
2. 功能完善

# Acknowledge

- Dcat-Admin
- Laravel

