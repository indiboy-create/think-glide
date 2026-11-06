# think-glide 示例

以下示例基于 ThinkPHP 8 + Glide 4，演示 `youvision/think-glide` 的安装、配置与常见用法。

## 目录结构

```
examples/
├── app/
│   ├── controller/Image.php   # 控制器中生成图片 URL
│   └── middleware.php         # 注册全局中间件
├── config/glide.php           # 配置文件示例
└── README.md
```

将 `app/` 与 `config/` 下的文件复制到你的 ThinkPHP 8 项目对应目录即可。

## 安装

```bash
composer require youvision/think-glide
```

要求 PHP >= 8.3，底层依赖 `league/glide` 4.x、`league/flysystem` 3.x、`intervention/image` 4.x。

## 配置

复制 `config/glide.php` 到项目的 `config/glide.php`，或执行：

```bash
php think glide:config
```

### 主要配置项

| 配置项 | 说明 |
| --- | --- |
| `web_root` | 项目根目录，默认 `app()->getRootPath()` |
| `source_dir` | 原图目录（相对 `web_root`），默认 `public/uploads` |
| `cache_dir` | 缓存目录（相对 `web_root`），默认 `runtime/glide` |
| `baseUrl` | 路由前缀，默认 `/images` |
| `group_cache_in_folders` | 缓存按源图分目录存放，缓存清除依赖此项 |
| `driver` | 图像驱动：`gd`、`imagick`、`vips`（vips 需额外安装驱动包） |
| `allowed_extensions` | 允许处理的源图扩展名 |
| `max_width` / `max_height` | 请求缩放尺寸上限（含 `dpr` 放大），超出返回 400 |
| `max_image_size` | 生成图片像素总量上限，超出自动等比缩小，`null` 不限制 |
| `defaults` | Glide 默认参数，URL 参数优先 |
| `cache_ttl` | 浏览器缓存秒数，控制 `Cache-Control` 与 304 |
| `cacheTime` | 兼容项，`cache_ttl` 为 `null` 时生效 |
| `cache_purge_param` / `cache_purge_value` | 缓存清除参数与口令 |
| `signKey` | 安全签名密钥，为空表示不校验 |

### 水印

| 配置项 | 说明 |
| --- | --- |
| `watermarks_enabled` | 是否启用水印，关闭时 `mark` 参数无效 |
| `watermarks` | 水印目录（相对 `web_root`） |
| `watermark_defaults` | 默认水印参数，URL 参数优先 |

### 固定尺寸

| 配置项 | 说明 |
| --- | --- |
| `preset_sizes_only` | 为 `true` 时仅允许 `p=预设名`，传 `w/h/fit/dpr/crop` 返回 400 |
| `preset_sizes` | 预设尺寸表，键为预设名，值为 Glide 参数 |

## 注册中间件

复制 `app/middleware.php` 到项目同名位置：

```php
return [
    \think\glide\middleware\Glide::class,
];
```

## 生成图片 URL

在控制器中调用助手函数 `glide_url()`：

```php
glide_url('user.jpg', ['w' => 100, 'h' => 100]);
```

或使用容器中的构建器：

```php
app('glide_builder')->getUrl('user.jpg', ['w' => 100, 'h' => 100]);
```

## 参数说明

所有参数通过 URL 查询字符串传递：

```
/images/photo.jpg?w=750&h=750&fit=cover&q=85&fm=webp
```

### 尺寸与变换

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `w` | 像素值 | 目标宽度 |
| `h` | 像素值 | 目标高度 |
| `fit` | `contain`（默认）、`max`、`fill`、`fill-max`、`stretch`、`cover`、`crop`、`crop-x%-y%[-zoom]`、`cover-top-left` 等九宫格位置 | 适配方式，`cover` 与 `crop` 等价 |
| `dpr` | `1` - `8` | 设备像素比，需配合 `w` 或 `h` |
| `or` | `auto`（默认）、`0`、`90`、`180`、`270` | 旋转，`auto` 按 Exif 校正 |
| `flip` | `v`、`h`、`both` | 垂直、水平或双向翻转 |
| `crop` | `width,height,x,y` | 缩放前按坐标裁剪 |

`fit=crop-25-75` 为焦点裁剪，`fit=crop-25-75-2` 可追加 1-100 的缩放倍数。

### 调整与特效

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `bri` | `-100` - `100` | 亮度，`0` 不调整 |
| `con` | `-100` - `100` | 对比度 |
| `gam` | `0.1` - `9.99` | Gamma |
| `sharp` | `0` - `100` | 锐化 |
| `blur` | `0` - `100` | 模糊，GD 高值较耗性能 |
| `pixel` | `0` - `1000` | 像素化 |
| `filt` | `greyscale`、`sepia` | 滤镜 |

### 水印

需先开启 `watermarks_enabled` 并配置 `watermarks` 目录。

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `mark` | 水印文件名 | 相对 `watermarks` 目录的路径 |
| `markw` / `markh` | 像素或相对尺寸 | 水印宽高，如 `200`、`5w` |
| `markfit` | `contain`、`max`、`stretch`、`crop`、`crop-*` | 水印适配方式 |
| `markx` / `marky` | 像素或相对尺寸 | 距左右/上下边缘距离 |
| `markpad` | 像素或相对尺寸 | 同时设置 `markx` 与 `marky` |
| `markpos` | `top-left`、`top`、`top-right`、`left`、`center`、`right`、`bottom-left`、`bottom`、`bottom-right` | 位置，默认 `bottom-right` |
| `markalpha` | `0` - `100` | 透明度，默认 `100` |

### 背景与边框

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `bg` | 颜色名或十六进制 | 背景色，如 `black`、`CCC`、`55CCCCCC` |
| `border` | `width,color,method` | `method` 为 `overlay`（默认）、`shrink`、`expand` |

### 编码输出

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `q` | `0` - `100` | 质量，默认 `90` |
| `fm` | `jpg`、`pjpg`、`png`、`gif`、`webp`、`avif`、`heic`、`jxl` | 格式，`heic`、`jxl` 需 Imagick |
| `interlace` | `1` | JPG 渐进式、PNG/GIF 隔行 |

### 预设尺寸

开启 `preset_sizes_only` 后仅允许 `p` 参数，可用逗号组合多个预设：

```
/images/photo.jpg?p=medium
```

### 相对尺寸

`markw`、`markh`、`markx`、`marky`、`markpad`、`border` 宽度支持相对尺寸，`5w` 表示主图宽度的 5%，`5h` 表示高度的 5%。

完整参数与交互示例见 [Glide 4 官方文档](https://glide.thephpleague.com/)。

## 安全签名

配置 `signKey` 后，直接改写查询参数会返回 403，必须通过 `glide_url()` 生成带 `sign` 的链接：

```php
echo glide_url('user.jpg', ['w' => 100, 'h' => 100]);
// /images/user.jpg?w=100&h=100&sign=af3dc18fc6bfb2afb521e587c348b904
```

## 清除缓存

配置 `cache_purge_value` 后，携带匹配的 `del` 参数即可删除该图全部缓存：

```
/images/user.jpg?del=12345678
```

需保持 `group_cache_in_folders` 为 `true`。

## Nginx 配置

```nginx
location ~* /images/(.*)$ {
    try_files $uri $uri/ /index.php/$request_uri;
}
```

缓存目录位于 `runtime/glide`，不在站点根目录下，无需额外禁止访问；若改用 `public` 下的目录，请显式禁止外部访问。