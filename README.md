# PHP 动态域名解析（基于 Dnspod）| PHP DDNS（base on DNSpod）

# 目录结构

```
.
├── README.md
├── bin    # 存放获取公网 IPv6 二进制文件
│   ├── get_public_ipv6-darwin-10.6-386
│   ├── get_public_ipv6-darwin-10.6-amd64
│   ├── get_public_ipv6-linux-386
│   ├── get_public_ipv6-linux-amd64
│   ├── get_public_ipv6-linux-arm-5
│   ├── get_public_ipv6-linux-arm-6
│   ├── get_public_ipv6-linux-arm-7
│   ├── get_public_ipv6-linux-arm64
│   ├── get_public_ipv6-linux-mips
│   ├── get_public_ipv6-linux-mips64
│   ├── get_public_ipv6-linux-mips64le
│   ├── get_public_ipv6-linux-mipsle
│   ├── get_public_ipv6-windows-4.0-386.exe
│   ├── get_public_ipv6-windows-4.0-amd64.exe
│   └── get_public_ipv6.go
└── run.php    # 程序逻辑代码
```

# 🍕 使用方法

1. 🎁 获取代码
    1. 你可以使用 `git` 命令克隆本项目 `git clone https://github.com/PrintNow/php-dnspod-ddns`
    2. 或者直接下载最新版
2. ✒ 配置 `run.php` 文件，请根据提示配置第 9、10、14、20、24、32、35 行代码

   > DNSPod 的 `ID` 和 `TOKEN` 获取方法：https://docs.dnspod.cn/account/5f2d466de8320f1a740d9ff3/

3. 📁 `bin` 目录说明，此目录下存放的是 **获取公网 IPv6 二进制文件**，作用是为了获取公网IPv6，是使用 `Golang` 编写的，然后使用 `xgo` 交叉编译成各个操作系统，不同 CPU
   架构的二进制文件。如果你不想使用本方法获取公网 IPv6，你可以自行重新编写（需要有 PHP 编程能力） `run.php` 的 `get_public_ipv6()` 函数

   > 小提示：你可以只保留符合你当前操作系统的 **二进制文件**，但是请不要更改其名字
   >
   > 另外，一般情况下，如果在各大云服务厂商购买的服务器，如果选择的是 Linux 或 Windows，它们的架构基本都是 x64 的，但也不排除一些 Linux 是 arm 架构的，请自我鉴别

# 🚗 准备运行

由于本脚本使用的是 `PHP` 编写的，所以你必须安装 `php`，还要安装 `curl` 扩展。Windows 用户**不太建议使用本脚本**

## 🐘 简要说明如何安装 PHP

> 已经安装了 PHP 请忽略
>
> 根据自己的操作系统执行对应命令即可。
>
> 请注意，我未做过多的测试，具体请网上搜索

CentOS：

```bash
sudo yum -y install php php-devel php-cli php-curl
```

Ubuntu / Debian：

```bash
sudo apt-get -y install php php-curl
```

其它操作系统： 请自行上网搜索

## 🚀 运行

> 可以部署好网站，直接通过网址访问 http://域名/run.php
> 前提就是必须取消禁用 `shell_exec` 函数

```bash
cd php-dnspod-ddns

php run.php
```

运行结果

```log
E:\PhpstormProjects\php-dnspod-ddns>php runTest.php
【iton.pw.mibook】AAAA记录值：2409:8a38:6820:f10:d934:cc96:e95a:8129 记录值更新成功！   ——2021-01-25 05:49:42
【iton.pw.mibook】AAAA记录值： 当前IP与解析的记录值(2409:8a38:6820:f10:d934:cc96:e95a:8129)相同，不更新 ——2021-01-25 05:50:58
```

# 定时执行，达到 DDNS 的目的

### 🐧 Linux 用户可以使用 `crontab` 命令定时执行脚本，命令如下：

> 请注意，我只是简要概述，你需要有一点 Linux 基础

```bash
crontab -e

# 输入以下内容，表示每5分钟执行一次
*/5 * * * *   php   /php-dnspod-ddns目录/run.php

# 写入文件，请按 Ctrl+O
# 然后按回车
# 然后再按 Ctrl + X 推出并保存
```

### Windows 用户可以使用“任务计划程序”

具体请上网搜索，不做过多说明