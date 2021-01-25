<?php
/**
 * 本项目是根据 DNSPOD 提供的 API 开发的 PHP 版 DDNS（动态域名解析）
 * 本代码写了较为详细的注释，我想你应该能够看懂
 * 请不要因为注释多而觉得配置很麻烦
 */

//请前往 https://console.dnspod.cn/account/token 创建获取
define("API_TOKEN", "");

//根域名，比如你注册的是 iton.pw 那么 iton.pw 就是根域名
//特殊根域名，比如 google.com.hk，你不应该填写 com.hk 应该填写 google.com.cn
define("DOMAIN", "iton.pw");

//子域名，假设你的根域名是 iton.pw
//你想解析 chuwen.iton.pw 那么你就填写 chuwen
//你想解析 666.chuwen.iton.pw 那么你就填写 666.chuwen
//以此类推
define("SUB_DOMAIN", "nas");

//是否开启 IPv4 解析，默认是 false 即不解析。true：解析
//请确认你已经拥有公网 IPv4 再开启！
define("A", false);

//是否开启 IPv4 解析，默认是 true 即解析。false：不解析
//请确认你已经拥有公网 IPv6 再开启！
define("AAAA", true);

var_dump(get_public_ipv4());


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
 * cURL GET / POST 封装方法
 * 来源于：https://blog.csdn.net/weixin_41970498/article/details/83010683
 *
 * @param $url
 * @param false $params
 * @param int $isPost
 * @param int $https
 * @return bool|string
 */
function curl($url, $params = false, $isPost = 0, $https = 1)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'chuwen/Qcloud-DDNS @0.1.0');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
    }
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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