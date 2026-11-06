<?php
// +----------------------------------------------------------------------
// | thinkphp8 glide [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: youvision <indiboy@gmail.com>
// +----------------------------------------------------------------------

use think\Request;

return [
    // 项目根目录，一般无需修改
    'web_root' => app()->getRootPath(),
    // 原图目录（相对 web_root，不要以 / 开头），同时作为 URL 前缀
    'source_dir' => 'public' . DIRECTORY_SEPARATOR . 'uploads',
    // 缩略图缓存目录（相对 web_root）
    'cache_dir' => 'runtime' . DIRECTORY_SEPARATOR . 'glide',
    // 路由前缀，匹配到该前缀时中间件开始执行
    'baseUrl' => '/images',
    // 缓存按源图分目录存放，缓存清除依赖此项
    'group_cache_in_folders' => true,
    // 图像处理驱动：gd、imagick 或 vips（vips 需安装 intervention/image-driver-vips）
    'driver' => 'gd',
    // 允许处理的源图扩展名
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    // 缩放尺寸上限，防止恶意超大参数耗尽内存
    'max_width' => 4000,
    'max_height' => 4000,
    // 生成图片像素总量上限（宽×高），超出会自动等比缩小，null 表示不限制
    'max_image_size' => null,
    // 默认参数，URL 参数优先级更高
    'defaults' => [
        'q' => 85,
    ],
    // 是否启用水印功能，开启后 mark 参数生效
    'watermarks_enabled' => false,
    // 水印文件目录（相对 web_root）
    'watermarks' => 'public' . DIRECTORY_SEPARATOR . 'watermarks',
    // 默认水印参数，URL 参数优先级更高
    'watermark_defaults' => [],
    // true = 仅允许生成 preset_sizes 中定义的固定尺寸
    'preset_sizes_only' => false,
    // 固定预设尺寸
    'preset_sizes' => [
        'small' => ['w' => 300, 'h' => 400, 'fit' => 'cover'],
        'medium' => ['w' => 800],
        'large' => ['w' => 1600],
    ],
    // 浏览器端缓存有效期（秒）
    'cache_ttl' => 31536000,
    // 缓存时间，仅当 cache_ttl 为 null 时生效，示例 +2 days
    'cacheTime' => '+1 day',
    // 缓存清除参数名与口令，口令留空禁用
    'cache_purge_param' => 'del',
    'cache_purge_value' => '12345678',
    // 安全签名
    'signKey' => '',
    'glide' => [],
    // 异常处理handler
    'onException' => function (\Exception $exception, Request $request, $server) {
        if ($exception instanceof \League\Glide\Signatures\SignatureException) {
            return response('图片资源签名错误', 403);
        }

        if ($exception instanceof \InvalidArgumentException) {
            return response($exception->getMessage(), 400);
        }

        return response(sprintf('你访问的图片资源 "%s" 不存在', $request->pathinfo()), 404);
    },
];