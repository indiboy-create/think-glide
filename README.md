## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# ThinkPHP8 图片动态裁剪缩放库

[Glide](https://github.com/thephpleague/glide) 是一个可以帮助你根据指定参数动态的生成图片内容给浏览器的图片操作库，从而实现
图片动态裁剪，打水印等，本库对 Glide 进行了一些友好的包装与扩展，屏蔽了原生库的一些底层抽象从而使得 ThinkPHP 用户可以在 ThinkPHP8 项目中
更好的添加图片的动态裁剪功能。

## Installation

执行下面命令安装:

```bash
$ composer require youvision/think-glide
```

> 要求 PHP >= 8.3、ThinkPHP 8，底层使用 [Glide 4.x](https://glide.thephpleague.com/)。

## Usage

完整示例见 [examples](examples/README.md)。

### Quick start

- ThinkPHP8 使用 middleware 注册：

    打开 `app/middleware.php` 文件（如果不存在创建即可），注册 middleware：
    
    ```php
    return [
        //...
    
        \think\glide\middleware\Glide::class
    ];
    ```

### 生成配置

系统安装后会自动在 config 目录中生成 glide.php 的配置文件，
如果系统未生成可在命令行执行

```php
php think glide:config 
```

快速生成配置文件

### 参数说明

| 参数名 | 类型 | 说明 | 是否必选 |
| --- | --- | --- | --- |
| source_dir | string | 原图目录，相对 `web_root`，默认 `public/uploads` | 否 |
| cache_dir | string | 缓存目录，相对 `web_root`，默认 `runtime/glide` | 否 |
| web_root | string | 项目根目录，默认 `app()->getRootPath()` | 否 |
| baseUrl | string | 路由前缀，默认 `/images` | 否 |
| group_cache_in_folders | bool | 缓存按源图分目录存放，缓存清除依赖此项 | 否 |
| driver | string | 图像驱动：`gd`、`imagick`、`vips`（vips 需额外安装驱动包） | 否 |
| allowed_extensions | array | 允许处理的源图扩展名 | 否 |
| max_width / max_height | int | 请求缩放尺寸上限（含 `dpr` 放大），超出返回 400 | 否 |
| max_image_size | int\|null | 生成图片像素总量上限，超出自动等比缩小 | 否 |
| defaults | array | Glide 默认参数，URL 参数优先 | 否 |
| cache_ttl | int\|null | 浏览器缓存秒数，控制 `Cache-Control` 与 304 | 否 |
| cacheTime | string | 兼容项，`cache_ttl` 为 `null` 时生效 | 否 |
| watermarks_enabled | bool | 是否启用水印，关闭时 `mark` 无效 | 否 |
| watermarks | string | 水印目录，相对 `web_root` | 否 |
| watermark_defaults | array | 默认水印参数 | 否 |
| preset_sizes_only | bool | 仅允许 `p=预设名`，传 `w/h/fit/dpr/crop` 返回 400 | 否 |
| preset_sizes | array | 预设尺寸表 | 否 |
| cache_purge_param | string | 缓存清除参数名，默认 `del` | 否 |
| cache_purge_value | string | 缓存清除口令，留空禁用 | 否 |
| signKey | string | 安全签名密钥 | 否 |
| onException | callable | 异常处理 handler，必须返回 `think\Response` | 否 |

`source_dir` 是原图目录（相对 `web_root`），假设该目录下有图片 `user.jpg`, 打开浏览器访问下面链接：
 
```
http://youdomain.com/images/user.jpg?w=100&h=100
```
即可得到缩小后的图片。

### 安全签名

不开启安全签名的情况下用户可以调整query里面的参数自行对图片进行裁剪，如果你不打算这么做的话，你可以通过
`signKey` 进行校验，

这种情况下用户自行调整参数将会无效；生成安全的URL:

```php
echo glide_url('user.jpg', ['w' => 100, 'h' => 100]);
或
echo app('glide_builder')->getUrl('user.jpg', ['w' => 100, 'h' => 100]);

//你会得到如下链接：/images/user.jpg?w=100&h=100&sign=af3dc18fc6bfb2afb521e587c348b904
```

### 异常处理

如果用户访问了一张不存在的图片或者没有进行安全校验，系统会抛出异常，你可以通过修改配置文件中 `onException` 进行替换默认行为：

```php
return [
    //...

    'onException' => function(\Exception $exception, $request, $server){
    
        if ($exception instanceof \League\Glide\Signatures\SignatureException) {
            $response = response('签名错误', 403);
        } else {
            $response = response(sprintf('你访问的资源 "%s" 不存在', $request->path()), 404);
        }
        
        return $response;
    }
])
```

#### nginx 配置
```
location ~* /images/(.*)$ {
  try_files $uri $uri/ /index.php/$request_uri;
}
```

注意该闭包必须返回一个 `think\Response` 实例；

### Glide 参数说明

所有参数通过 URL 查询字符串传递，多个参数用 `&` 连接：

```
/images/photo.jpg?w=750&h=750&fit=cover&q=85&fm=webp
```

#### 尺寸与变换

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `w` | 像素值 | 目标宽度 |
| `h` | 像素值 | 目标高度 |
| `fit` | `contain`（默认）、`max`、`fill`、`fill-max`、`stretch`、`cover`、`crop`、`crop-x%-y%[-zoom]`、`cover-top-left` 等九宫格位置 | 图片适配方式，`cover` 与 `crop` 等价 |
| `dpr` | `1` - `8` | 设备像素比，需配合 `w` 或 `h` 使用 |
| `or` | `auto`（默认）、`0`、`90`、`180`、`270` | 旋转，`auto` 按 Exif 自动校正 |
| `flip` | `v`、`h`、`both` | 垂直、水平或双向翻转 |
| `crop` | `width,height,x,y` | 缩放前按坐标裁剪 |

`fit=crop-25-75` 表示以横向 25%、纵向 75% 为焦点裁剪，`fit=crop-25-75-2` 可追加缩放倍数（1-100）。

#### 调整与特效

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `bri` | `-100` - `100` | 亮度，`0` 不调整 |
| `con` | `-100` - `100` | 对比度，`0` 不调整 |
| `gam` | `0.1` - `9.99` | Gamma |
| `sharp` | `0` - `100` | 锐化 |
| `blur` | `0` - `100` | 模糊，GD 驱动高值较耗性能 |
| `pixel` | `0` - `1000` | 像素化 |
| `filt` | `greyscale`、`sepia` | 滤镜 |

#### 水印

需先开启 `watermarks_enabled` 并配置 `watermarks` 目录。

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `mark` | 水印文件名 | 水印路径，相对 `watermarks` 目录 |
| `markw` | 像素或相对尺寸 | 水印宽度，如 `200`、`5w` |
| `markh` | 像素或相对尺寸 | 水印高度 |
| `markfit` | `contain`、`max`、`stretch`、`crop`、`crop-*` | 水印适配方式 |
| `markx` | 像素或相对尺寸 | 距左右边缘距离，`markpos=center` 时忽略 |
| `marky` | 像素或相对尺寸 | 距上下边缘距离，`markpos=center` 时忽略 |
| `markpad` | 像素或相对尺寸 | 同时设置 `markx` 与 `marky` |
| `markpos` | `top-left`、`top`、`top-right`、`left`、`center`、`right`、`bottom-left`、`bottom`、`bottom-right` | 水印位置，默认 `bottom-right` |
| `markalpha` | `0` - `100` | 水印透明度，默认 `100` |

#### 背景与边框

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `bg` | 颜色名或十六进制 | 背景色，如 `black`、`CCC`、`55CCCCCC` |
| `border` | `width,color,method` | 边框，`method` 为 `overlay`（默认）、`shrink`、`expand` |

颜色支持 140 个 CSS 颜色名，以及 3 位 RGB、4 位 ARGB、6 位 RGB、8 位 ARGB 十六进制。

#### 编码输出

| 参数 | 取值 | 说明 |
| --- | --- | --- |
| `q` | `0` - `100` | 输出质量，默认 `90` |
| `fm` | `jpg`、`pjpg`、`png`、`gif`、`webp`、`avif`、`heic`、`jxl` | 输出格式，`heic`、`jxl` 需 Imagick |
| `interlace` | `1` | JPG 渐进式、PNG/GIF 隔行 |

#### 预设尺寸

开启 `preset_sizes_only` 后，仅允许通过 `p` 指定预设尺寸，可用逗号组合多个预设：

```
/images/photo.jpg?p=medium
```

#### 相对尺寸

`markw`、`markh`、`markx`、`marky`、`markpad`、`border` 宽度支持相对尺寸，`5w` 表示主图宽度的 5%，`5h` 表示高度的 5%。

完整参数与交互示例见 [Glide 4 官方文档](https://glide.thephpleague.com/)。 