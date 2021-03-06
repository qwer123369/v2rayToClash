<?php
/** v2ray to clash yml
 * Created by PhpStorm.
 * User: scjtqs
 * Date: 2020-10-03
 * Time: 09:33
 */
/**
 * 前置需要 安装 composer
 * 加载依赖命令：
 * composer require mustangostang/spyc
 */
require_once __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$file='clash.yaml';
//清空yaml文件
//file_put_contents($file,'');
$init=<<<EOL
port: 7890
socks-port: 7891
redir-port: 7892
allow-lan: true
mode: rule
log-level: silent
external-controller: '0.0.0.0:9090'
secret: ''
rules:
    - DOMAIN-SUFFIX,cloudfront.net,DIRECT
    - DOMAIN-SUFFIX,qq.com,DIRECT
    - IP-CIDR,192.168.0.0/16,DIRECT
    - IP-CIDR,10.0.0.0/16,DIRECT
    - IP-CIDR,192.168.50.0/8,DIRECT
    - IP-CIDR,172.16.0.0/12,DIRECT
    - IP-CIDR,127.0.0.0/8,DIRECT
    - IP-CIDR,100.64.0.0/10,DIRECT
    - IP-CIDR,224.0.0.0/4,DIRECT
    - IP-CIDR,119.28.28.28/32,DIRECT
    - IP-CIDR,182.254.116.0/24,DIRECT
    - GEOIP,CN,DIRECT
    - MATCH,v2ray
EOL;
$Spyc = new Spyc();
$yaml=$Spyc->load($init);
$url="你的订阅地址";
$rspBase64=base64_decode(file_get_contents($url));
$listArr=explode(PHP_EOL,$rspBase64);
if(empty($listArr))
{
    exit('get url result faild');
}
$yaml['proxies']=[];
//翻墙分组
$yaml['proxy-groups'][0]=[
    'name'=>'v2ray',
    'type'=>'url-test',
    'url'=>'http://www.gstatic.com/generate_204',
    'interval'=>300,
];
//直连分组
//$yaml['proxy-groups'][1]=[
//    'name'=>'直接连接',
//    'type'=>'select',
//    'proxies'=>['DIRECT']
//];
foreach ($listArr as $k=>$list)
{
    if(empty(trim($list)))
    {
        continue;
    }
    preg_match('/vmess[\r\n]?\:\/\/(\S+)/',$list,$str);
    $baseInfo=$str[1];
    $info=json_decode(base64_decode($baseInfo),true);
    if(!in_array($info['net'],['ws','tcp']))
    {
        continue;
    }
    //过滤掉一些回国链接
    if(strstr($info['ps'],'回国'))
    {
        continue;
    }
    if($info['net']=='ws')
    {
        $yaml['proxies'][]=[
            'name'=>(string)$info['ps'],
            'type'=>'vmess',
            'server'=>(string)$info['add'],
            'port'=>(int)$info['port'],
            'uuid'=>(string)$info['id'],
            'alterId'=>(int)$info['aid'],
            'cipher'=>'auto',
            'tls'=>(bool)$info['tls'],
            'network'=>(string)$info['net'],
            'ws-path'=>(string)$info['path'],
            'ws-headers'=>['Host'=>(string)$info['host']],
            'skip-cert-verify'=>(bool)$info['verify_cert']?false:true,
        ];
    }
    if($info['net']=='tcp')
    {
        $yaml['proxies'][]=[
            'name'=>(string)$info['ps'],
            'type'=>'vmess',
            'server'=>(string)$info['add'],
            'port'=>(int)$info['port'],
            'uuid'=>(string)$info['id'],
            'alterId'=>(int)$info['aid'],
            'cipher'=>'auto',
            ];
    }

    $yaml['proxy-groups'][0]['proxies'][]=$info['ps'];
    unset($str,$baseInfo,$info);
}
$yaml=Spyc::YAMLDump($yaml,2,0);
file_put_contents($file,$yaml);