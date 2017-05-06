<p align="center">
  <b>雨林寒舍</b>
  <br/>
  <br/>
  <a target="_blank" href="https://www.im050.com">
    <img src="http://www.im050.com/wp-content/themes/simplehome/images/face.png" width=130>
  </a>
</p>

# WeChat Robot
一款基于PHP开发的微信机器人程序（个人号非公众号）

<p align="center">
<a href="http://hanc.cc"><img src="https://img.shields.io/badge/contact-@Im050-orange.svg?style=flat"></a>
<img src="https://img.shields.io/badge/license-MIT-green.svg?style=flat">
</p>

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
4. 未完待续...

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

本程序仅是我一时兴趣所筑，由于时间问题，截至目前为止，很多功能均为完善，以下有更好的作品可供使用，同时也为本程序在开发过程中遇到的疑问提供了参考。

1. [littlecodersh/ItChat](https://github.com/littlecodersh/ItChat) 参考登录流程
2. [HanSon/vbot](https://github.com/HanSon/vbot) 参考了微信协议
3. [lbbniu/WebWechat](https://github.com/lbbniu/WebWechat) 参考了微信协议

感谢以上项目作者的无私奉献，我也会坚持将WeChatRobot完善下去。



## 截图

 ![image](https://github.com/im050/wechat_robot/raw/master/screenshots/screenshot.png)