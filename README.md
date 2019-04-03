# WeChat Robot
一款基于PHP开发的微信机器人程序（个人号非公众号），仅供个人学习及研究

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

## 更好的选择

1. [littlecodersh/ItChat](https://github.com/littlecodersh/ItChat) 
2. [HanSon/vbot](https://github.com/HanSon/vbot) 
3. [lbbniu/WebWechat](https://github.com/lbbniu/WebWechat) 

## 截图

 ![image](https://github.com/im050/wechat_robot/raw/master/screenshots/screenshot.png)