# WeChat Robot
一款基于PHP开发的微信机器人程序（个人号非公众号）

![image](https://img.shields.io/badge/PHP-5.6-orange.svg?style=flat)
![image](https://img.shields.io/badge/license-MIT-green.svg?style=flat)

## 依赖

| 环境          | 版本           |
| ------------- | ------------- |
| [PHP](http://www.php.net)           | \>=5.6 | 
| [Swoole 扩展](http://www.swoole.com/)    | \>=1.9.*      |
| [Fileinfo 扩展](http://php.net/manual/en/book.fileinfo.php)  | \>=1.0.*      |
| [Posix 扩展](http://www.php.net/manual/en/book.posix.php)     | -             |

## 特点

1. 支持扫码后5分钟内免扫码登录
2. 异步回复消息（基于swoole的process）
3. 扫码登录后，支持以守护进程运行
4. 自动保存撤回消息文本及资源类型数据
5. 目前可识别的类型
    1. 文本消息
    2. 图片消息
    3. 动画表情消息
    4. 语音消息
    5. 视频消息
    6. 小视频消息
    7. 红包消息
    8. 撤回消息
5. 未完待续...

## 安装

1. 下载
```
git clone https://github.com/im050/wechat_robot.git
```
2. 更新依赖包
```
composer update
```

## 运行
```
php example/start.php
```

## 关于

项目开始之初，在网上无意发现基于`python`开发的微信机器人（ItChat），于是想以学习的目的使用PHP开发一个微信机器人。由于时间的关系，截至目前为止，很多功能均未完善，另外，由于使用了`swoole`扩展，所以请在`linux`环境下运行该程序。以下同类型的项目给予了我莫大的帮助，其次以下项目也更加健全，推荐大家使用。

1. [littlecodersh/ItChat](https://github.com/littlecodersh/ItChat) 
2. [HanSon/vbot](https://github.com/HanSon/vbot) 
3. [lbbniu/WebWechat](https://github.com/lbbniu/WebWechat) 

在此，感谢以上项目作者的无私奉献，我也会坚持将自己的微信机器人完善下去。

## 联系

    Email: service@im050.com
    QQ: 52619941
    WeChat: cnmemory

## 截图

 ![image](https://github.com/im050/wechat_robot/raw/master/screenshots/screenshot.png)