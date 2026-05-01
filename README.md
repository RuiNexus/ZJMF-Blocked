# ZJMF-Blocked

基于 IP 归属地的访问拦截插件，通过注入魔方财务系统入口文件实现地区访问控制。

支持 **黑名单模式**（屏蔽指定国家）和 **白名单模式**（只允许指定国家），同时支持 **IPv4** 和 **IPv6**。

## 目录结构

```
ZJMF-Blocked/
├── config.php                            # 配置文件
├── init.php                              # 插件入口逻辑
├── README.md
└── ip2region/
    ├── binding/php/xdb/Searcher.class.php # ip2region PHP 查询引擎
    └── data/
        ├── ip2region_v4.xdb              # IPv4 归属地数据库
        └── ip2region_v6.xdb              # IPv6 归属地数据库
```

## 安装

1. 将 `ZJMF-Blocked` 文件夹放置在 魔方财务 项目根目录（与 `public/` 平级）

2. 修改 `public/index.php`，在 `namespace think;` 之后添加：

```php
// 地区访问拦截插件
$zjmfBlockedFile = dirname(__DIR__) . '/ZJMF-Blocked/init.php';
if (file_exists($zjmfBlockedFile)) {
    require_once $zjmfBlockedFile;
}
```

## 配置

打开 `config.php`：

```php
return [
    // 模式：'block' = 黑名单模式（屏蔽以下国家，其他放行）
    //      'allow' = 白名单模式（只允许以下国家，其他屏蔽）
    'mode' => 'block',

    // 国家/地区名称列表（匹配不区分大小写，支持中英文）
    // block 模式：屏蔽这些国家
    // allow 模式：只允许这些国家
    // 如果一种名称匹配不上，可以运行 debug.php 查一下实际返回什么
    'regions' => ['United States'],

    // 被拦截时返回的提示信息
    'block_message' => 'You have been blocked from accessing',

    // 是否启用 HTTP 调试模式
    // 开启后访问任意页面加 ?debug_ip=8.8.8.8 可查看 IP 归属地查询结果
    // 生产环境建议关闭
    'debug_enabled' => false,
];
```

## 调试模式

当 `config.php` 中 `debug_enabled` 设置为 `true` 时，可通过 HTTP 请求快速查看任意 IP 的归属地查询结果。

**访问方式：**

```
https://你的域名/?debug_ip=8.8.8.8
https://你的域名/?debug_ip=4.2.2.2
https://你的域名/?debug_ip=2001:4860:4860::8888
```

**返回示例：**
```
IP: 8.8.8.8

[IPv4] 0|美国|0|0|0|0
  → 国家字段: [美国]
```

> 注意：`debug_enabled` 仅对携带 `?debug_ip=` 参数的请求生效，不会影响正常访问。生产环境建议关闭，避免泄露内部路径信息。

## 工作原理

1. 获取用户真实 IP（支持 Cloudflare、X-Forwarded-For 等代理透传）
2. 自动判断 IPv4/IPv6，使用对应的 xdb 数据库查询归属地
3. 从查询结果 `国家|区域|省份|城市|ISP` 格式中提取国家名称
4. 根据配置的模式判断是否拦截
5. 拦截时返回 HTTP 403 和自定义提示信息

## 注意事项

- 插件仅在程序入口执行一次，性能开销极低
- 如果 xdb 数据库或 Searcher 类不存在，插件自动跳过，不影响正常访问
- 如果使用了 CDN（如 Cloudflare），会自动读取真实的用户 IP

## 协议与致谢

本插件依赖 [ip2region](https://github.com/lionsoul2014/ip2region) 离线 IP 定位库。

### ip2region 开源协议

ip2region 整体采用 **Apache License 2.0** 许可，其中 ip2region 库本身同时采用 **MIT 许可**：

```
                                 Apache License
                           Version 2.0, January 2004
                        http://www.apache.org/licenses/

   TERMS AND CONDITIONS FOR USE, REPRODUCTION, AND DISTRIBUTION

   （完整 Apache 2.0 条款请参见 https://www.apache.org/licenses/LICENSE-2.0）

==========================================================================
The following license applies to the ip2region library
--------------------------------------------------------------------------
Copyright (c) 2015 Lionsoul <chenxin619315@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```

更多信息请访问：[https://github.com/lionsoul2014/ip2region](https://github.com/lionsoul2014/ip2region)
