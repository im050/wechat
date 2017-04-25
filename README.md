# 微信机器人 for 个人号
## 依赖

| 环境          | 版本           |
| ------------- |:-------------:|
| PHP           | \>=5.6 | 
| Swoole        | \>=1.9.*      |

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

> php example/start.php