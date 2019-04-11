# WeChat Robot
一款基于PHP开发的微信机器人程序（个人号非公众号），仅供个人学习及研究

![image](https://img.shields.io/badge/PHP-7.0-orange.svg?style=flat)
![image](https://img.shields.io/badge/license-MIT-green.svg?style=flat)

## 依赖

| 环境          | 版本           |
| ------------- | ------------- |
| [PHP](http://www.php.net)           | \>=7.0 | 
| [Swoole 扩展](http://www.swoole.com/)    | \>=1.9.*      |
| [Fileinfo 扩展](http://php.net/manual/en/book.fileinfo.php)  | \>=1.0.*      |
| [Posix 扩展](http://www.php.net/manual/en/book.posix.php)     | -             |

## 特点

1. 支持扫码后5分钟内免扫码登录
2. 异步回复消息（基于swoole的process）
3. 扫码登录后，支持以守护进程运行
4. 自动保存撤回消息文本及资源类型数据
5. 支持定时任务 (类似Crontab)
6. 目前可识别的类型
    1. 文本消息
    2. 图片消息
    3. 动画表情消息
    4. 语音消息
    5. 视频消息
    6. 小视频消息
    7. 红包消息
    8. 撤回消息
    9. 转账消息
    10. 群系统消息

## Todo

1. 好友请求通知及通过好友请求
2. 逐步提升稳定性
3. 提供HTTP协议API

## 安装

#### 通过Git

1. 下载
```
git clone https://github.com/im050/wechat_robot.git
```
2. 更新依赖包
```
composer update
```

#### 通过Composer (推荐)

```
composer require im050/wechat
```

## 运行
```
php example/start.php
```

## 更好的选择

1. [littlecodersh/ItChat](https://github.com/littlecodersh/ItChat) 
2. [HanSon/vbot](https://github.com/HanSon/vbot) 
3. [lbbniu/WebWechat](https://github.com/lbbniu/WebWechat) 

## 常见问题

1. **Q: 无法通过getContactByNickName获取到指定群？**    
> A: 将群聊保存至通讯录
2. **Q: 同步消息失败等无法获取最新消息**    
> A: 尝试删除临时文件目录下的cookies.txt后重新登录
3. **Q: 免扫码登录不起作用**  
> A: 经测试发现，未绑定手机号的微信账号无法免扫码登录

## 配置参数说明
    private $config = [
        'tmp_path'         => '',              //临时目录
        'log_level'        => Logger::INFO,    //日志级别
        'save_qrcode'      => true,            //是否保存二维码
        'auto_download'    => true,            //是否自动下载
        'daemonize'        => false,           //是否守护进程
        'task_process_num' => 1                //任务队列进程数，推荐1个就行
    ];
    
    //额外内置可用的配置参数cookiefile_path, cookie_path, message_log_path
    

## 截图

 ![image](https://github.com/im050/wechat_robot/raw/master/screenshots/screenshot.png)