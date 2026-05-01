<?php

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
