<?php
/**
 * 本项目是根据 DNSPOD 提供的 API 开发的 PHP 版 DDNS（动态域名解析）
 * 本代码写了较为详细的注释，我想你应该能够看懂
 * 请不要因为注释多而觉得配置很麻烦
 */

//请前往 https://console.dnspod.cn/account/token 创建获取
//详细教程 https://docs.dnspod.cn/account/5f2d466de8320f1a740d9ff3/
define("ID", "");
define("TOKEN", "");

//根域名，比如你注册的是 iton.pw 那么 iton.pw 就是根域名
//特殊根域名，比如 google.com.hk，你不应该填写 com.hk 应该填写 google.com.cn
define("DOMAIN", "nowtime.cc");

//子域名，假设你的根域名是 iton.pw
//你想解析 chuwen.iton.pw 那么你就填写 chuwen
//你想解析 666.chuwen.iton.pw 那么你就填写 666.chuwen
//以此类推
define("SUB_DOMAIN", "www");

//解析类型，如果需要解析的是 IPv6 那么填写 AAAA  （4个A）
//如果需要解析的是 IPv4 那么填写 A
define("TYPE", "AAAA");


//----------------------------------------------------------------------//
//一般情况下，如果在各大云服务厂商购买的服务器，如果选择的是 Linux 或 Windows，
//它们的架构都是 x64 的，但也不排除一些 Linux 是 arm 架构的，请自我鉴别

//操作系统，默认是 linux。请根据自己设备实际情况填写，只能够填写 win、linux、darwin （不区分大小写）
define("OS", "linux");

//CPU 架构，默认是 x64。请根据自己设备实际情况填写，只能够填写 x86、x64、mips、mips64、mips64le、mipsle、arm、arm64、arm5、arm6、arm7（不区分大小写）
define("Arch", "x64");
//----------------------------------------------------------------------//


//获取公网 IP 地址
if (TYPE === 'AAAA') {
    try {
        $ip = get_public_ipv6('win', 'x64');
    } catch (Exception $e) {
        die($e->getMessage());
    }
} else {
    $ip = get_public_ipv4();
}

try {
    $record = get_record_id(DOMAIN, SUB_DOMAIN, TYPE);
} catch (Exception $e) {
    die($e->getMessage());
}

//如果不存在该记录值就添加
if (!$record) {
    add_record(DOMAIN, SUB_DOMAIN, TYPE, $ip);
}

//如果存在该记录值就去修改

//如果当前的IP和记录值相同就终止程序运行
if ($record['value'] === $ip) {
    print_info_log("当前IP与解析的记录值({$ip})相同，不更新", SUB_DOMAIN, DOMAIN, TYPE, "");
}

//修改记录值，函数里会作出提示，故这里只调用即可
modify_record($record['id'], DOMAIN, SUB_DOMAIN, TYPE, $ip);

/**
 * 修改域名记录值
 * @param $record_id
 * @param $domain
 * @param $sub_domain
 * @param $type
 * @param $value
 */
function modify_record($record_id, $domain, $sub_domain, $type, $value)
{
    $res = curl('https://dnsapi.cn/Record.Modify', get_request_param([
        'domain' => $domain,
        'record_id' => $record_id,
        'sub_domain' => $sub_domain,
        'record_type' => strtoupper($type),//记录类型：A、AAAA
        'value' => $value,//记录值
        'record_line' => '默认',//记录线路
    ]), 1);

    $response = json_decode($res, true);
    if ($response['status']['code'] === '1') {
        print_info_log("记录值更新成功！", $sub_domain, $domain, $type, $value);
    }

    print_info_log("记录值修改失败，原因：{$response['status']['message']}", $sub_domain, $domain, $type, $value);
}

/**
 * 添加域名记录
 * @param string $domain
 * @param string $sub_domain
 * @param string $type
 * @param $value
 */
function add_record($domain, $sub_domain, $type, $value)
{
    $res = curl('https://dnsapi.cn/Record.Create', get_request_param([
        'domain' => $domain,
        'sub_domain' => $sub_domain,
        'record_type' => strtoupper($type),//记录类型：A、AAAA
        'value' => $value,//记录值
    ]), 1);

    $response = json_decode($res, true);
    if ($response['status']['code'] === '1') {
        print_info_log("记录值添加成功！", $sub_domain, $domain, $type, $value);
    }

    print_info_log("记录值添加失败，原因：{$response['status']['message']}", $domain, $sub_domain, $type, $value);
}

/**
 * 获取指定域名的子域名 记录列表
 *
 * @param string $domain
 * @param string $sub_domain
 * @param string $type all：表示只获取 A 和 AAAA 记录值
 * @return false|array
 * @throws Exception
 */
function get_record_id($domain, $sub_domain, $type)
{
    $res = curl('https://dnsapi.cn/Record.List', get_request_param([
        'domain' => $domain,
        'sub_domain' => $sub_domain,
        'record_type' => strtoupper($type)
    ]), 1);

    $response = json_decode($res, true);

    if ($response['status']['code'] !== '1') {
        throw new Exception("请求 DNSPod API失败，原因：{$response['status']['message']}");
    }

    if (isset($response['records'][0]['id'])) {
        return $response['records'][0];
    }

    return null;
}

/**
 * 拼接请求参数数组
 *
 * @param array $arr
 * @return array
 */
function get_request_param($arr = [])
{
    return array_merge([
        'login_token' => sprintf("%s,%s", ID, TOKEN),
        'format' => 'json',
        'lang' => 'cn',
        'error_on_empty' => 'no',
    ], $arr);
}

/**
 * 获取公网 Pv6 地址，一般情况下，如果在各大云服务厂商购买的服务器，如果选择的是 Linux 或 Windows，
 * 它们的架构都是 x64 的，但也不排除一些 Linux 是amd64架构的，请自我甄别
 *
 * @param string $OS 操作系统，默认是 linux。
 *                   只能够填写 win、linux、darwin （不区分大小写）
 * @param string $Arch CPU 架构，默认是 x64。
 *                     只能够填写 x86、x64、mips、mips64、mips64le、mipsle、arm、arm64、arm5、arm6、arm7（不区分大小写）
 * @return string   如果获取到 IPv6 将会返回长度大于 0 的字符串，否则返回空字符串。
 *                  如果因为一些原因报错了，也会返回报错信息，但是他不是作为返回值输出，而是直接打印在屏幕上
 *
 * @throws Exception
 */
function get_public_ipv6($OS = 'linux', $Arch = 'x64')
{
    $OS = strtolower($OS);
    $Arch = strtolower($Arch);

    if ($OS === 'win') $OS = 'windows-4.0';
    else if ($OS === 'darwin') $OS = 'darwin-10.6';

    if ($Arch === 'x86') $Arch = '386';
    else if ($Arch === 'x64') $Arch = 'amd64';

    $binPath = sprintf(__DIR__ . "/bin/get_public_ipv6-%s-%s%s", $OS, $Arch, $OS === 'windows-4.0' ? '.exe' : '');

    if (!file_exists($binPath)) {
        throw new Exception("请确认传入的参数是否正确，如果正确请检查 {$binPath} 二进制文件是否存在于 bin 目录下，否则请从 GitHub 重新拉取源码");
    }

    $ipv6 = trim(shell_exec($binPath));
    if (filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $ipv6;
    }

    return '';
}

/**
 * 获取公网 IPv4 地址
 * @param int $index 使用哪个接口，默认使用的是 $apis 下标为 0 的接口，即 https://ipv4.nowtool.cn <br/>
 *                   如果想随机使用接口请使用传入 -1，如果想传入自己指定接口请自己传入指定下标
 * @param int $try 访问接口错误，尝试多少次，默认 2 次。如果第一次访问错误了，那么将会随机访问接口列表中的一个
 * @return string   公网 IPv4，。如果获取失败就返回空字符串
 */
function get_public_ipv4($index = 0, $try = 2)
{
    //获取公网 IPv4 接口
    //必须是访问就返回 IPv4 的接口
    $apis = [
        'https://ipv4.nowtool.cn',
        'https://api-ipv4.ip.sb/ip'
    ];
    $isTry = 0;

    if ($index === -1) $index = rand(0, count($apis) - 1);

    while ($isTry++ < $try) {
        $ipv4 = trim(curl($apis[$index]));

        //判断是否为合法 IPv4，如果不是就再次尝试获取
        if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipv4;
        }

        //因为到达此步已经出错了，所以随机获取一个接口下标，供程序获取
        unset($apis[$index]);
        if ($isTry > 0) $index = array_rand($apis, 1);
    }

    return "";
}


/**
 * 打印日志并终止脚本执行
 * @param $reason
 * @param $domain
 * @param $sub_domain
 * @param $type
 * @param $value
 */
function print_info_log($reason, $domain, $sub_domain, $type, $value)
{
    die(sprintf("【%s.%s】%s记录值：%s %s\t——%s" . PHP_EOL, $sub_domain, $domain, $type, $value, $reason, date("Y-m-d H:i:s")));
}


/**
 * cURL GET / POST 封装方法
 * 来源于：https://blog.csdn.net/weixin_41970498/article/details/83010683
 *
 * @param $url
 * @param bool $params
 * @param bool $isPost
 * @param int $https
 * @return bool|string
 */
function curl($url, $params = false, $isPost = false, $https = 1)
{
    $ch = curl_init();
//    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, $isPost ? '' : 'Chuwen DDNS Client/1.0.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
    }
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    $response = curl_exec($ch);

    if ($response === FALSE) {
        return false;
    }

    curl_close($ch);
    return $response;
}