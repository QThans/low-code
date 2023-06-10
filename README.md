# LowCode开发平台

完全开源的低代码开发平台，可以通过全配置+少量代码实现大量管理应用。

[Demo网站](http://bpm.thans.cn)

该项目已被某些公司用于开发内部系统，先将核心开源。有一些发现的、未发现的BUG存在，如果疑问或者遇到了请提交**issue**。

## 教程

目前暂无，可自行摸索，并不难。作者提供付费指导，可扫底部二维码。

# Requirements
- PHP 7.4
- PostgreSQL
- fileinfo
- Redis
- pgsql

# Install Steps

1. 创建 **Laravel 8.0** 应用

```
composer create-project --prefer-dist laravel/laravel {替换为项目名称} 8.*
```

2. 进入项目目录

 ```
 cd {替换为项目名称}
 ```

3. 安装本扩展

```
composre require thans/bpm
```


4. 在.env中配置数据库连接。**仅支持PostgreSQL**

```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=lowcode
DB_USERNAME=lowcode
DB_PASSWORD=EY2mGyCLCh5h
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

全开源，无加密。

目前仅供学习使用，如需商用请联系作者购买授权。

99元/项目

ps: 后续视情况，可能会免费。概不退款。

# 付费项目

1. 使用指导 500元加WeChat，提供答疑。
2. 帮忙配置 2k/表单
3. 托管服务 私聊

# 免费项目

1. 请提交issue

# 联系方式

![WeChat](https://img.thans.cn/wechat.jpg)

# Roadmap

1. 升级Laravel和Dcat-Admin
2. 功能完善

# Acknowledge

- Dcat-Admin
- Laravel

