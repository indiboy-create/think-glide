<?php
/**
 * +----------------------------------------------------------------------
 * | think-glide [thinkphp8]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Glide.php
 * ( (  \.---./  ) )            | AUTHOR: youvision
 *  '.__/o   o\__.'             | EMAIL: indiboy@gmail.com
 *     {=  ^  =}                |
 *     /       \                | DATETIME: 2019/11/6 12:58
 *    //       \\               |
 *   //|   .   |\\              |
 *   "'\       /'"_.-~^`'-.     |
 *      \  _  /--'         `    |
 *    ___)( )(___               |-----------------------------------------
 *   (((__) (__)))              | 高山仰止,景行行止.虽不能至,心向往之。
 * +----------------------------------------------------------------------
 * | Copyright (c) 2019 All rights reserved.
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think;

use League\Glide\Server;
use League\Glide\ServerFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use think\glide\ResponseFactory;
use think\glide\SignatureFactory;
use think\glide\UrlBuilderFactory;

class Glide
{
    /**
     * 当前应用对象
     * @var App
     */
    protected App $app;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var array
     */
    protected array $query = [];

    public function __construct(App $app, array $options = [])
    {
        $this->app = $app;
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'web_root' => $app->getRootPath(),
            'source_dir' => 'public' . DIRECTORY_SEPARATOR . 'uploads',
            'cache_dir' => 'runtime' . DIRECTORY_SEPARATOR . 'glide',
            'source' => null,
            'cache' => null,
            'baseUrl' => '/images',
            'group_cache_in_folders' => true,
            'driver' => 'gd',
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_width' => 4000,
            'max_height' => 4000,
            'max_image_size' => null,
            'defaults' => ['q' => 85],
            'watermarks_enabled' => false,
            'watermarks' => 'public' . DIRECTORY_SEPARATOR . 'watermarks',
            'watermark_defaults' => [],
            'preset_sizes_only' => false,
            'preset_sizes' => [
                'small' => ['w' => 300, 'h' => 400, 'fit' => 'cover'],
                'medium' => ['w' => 800],
                'large' => ['w' => 1600],
            ],
            'cache_ttl' => 31536000,
            'cacheTime' => '+1 day',
            'cache_purge_param' => 'del',
            'cache_purge_value' => '',
            'signKey' => '',
            'glide' => [],
            'onException' => function (\Exception $exception, Request $request, Server $server) {
                if ($exception instanceof \League\Glide\Signatures\SignatureException) {
                    return Response::create('图片资源签名错误')->code(403);
                }

                if ($exception instanceof \InvalidArgumentException) {
                    return Response::create($exception->getMessage())->code(400);
                }

                return Response::create(sprintf('你访问的图片资源 "%s" 不存在', $request->pathinfo()))->code(404);
            },
        ]);
        $resolver->setAllowedTypes('cache_ttl', ['int', 'null']);
        $resolver->setAllowedTypes('max_image_size', ['int', 'null']);
        $this->options = $resolver->resolve($options);

        $urlBuilder = UrlBuilderFactory::create($this->options['baseUrl'], $this->options['signKey']);
        $this->app->bind('glide_builder', $urlBuilder);
    }

    /**
     * 把对象当成一个函数去执行
     * @param Request $request
     * @param $next
     * @author youvision <indiboy@gmail.com>
     * @return mixed
     */
    public function __invoke($request, $next)
    {
        $uri = urldecode($request->pathinfo());
        $this->query = $request->query();
        unset($this->query['s']);

        $baseUrl = '/' . trim((string) $this->options['baseUrl'], '/');
        if (!preg_match('#^' . preg_quote($baseUrl, '#') . '(?=/|$)#', '/' . $uri)) {
            return $next($request);
        }

        $server = $this->createGlideServer();

        try {
            $this->validateRequest($uri);
            $response = $this->purgeCache($server, $uri);
            if (null === $response) {
                $this->checkSignature($uri);
                $response = $this->handleRequest($server, $request);
            }
        } catch (\Exception $exception) {
            $response = call_user_func($this->options['onException'], $exception, $request, $server);
        }

        return $response;
    }

    /**
     * @param string $uri
     * @throws \InvalidArgumentException
     */
    protected function validateRequest(string $uri): void
    {
        $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        $allowed = array_map('strtolower', (array) $this->options['allowed_extensions']);

        if ($extension !== '' && $allowed && !in_array($extension, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('不支持的图片格式 "%s"', $extension));
        }

        if ($this->options['preset_sizes_only']) {
            foreach (['w', 'h', 'fit', 'dpr', 'crop'] as $field) {
                if (isset($this->query[$field])) {
                    throw new \InvalidArgumentException('仅支持通过预设尺寸参数 p 请求图片');
                }
            }

            if (isset($this->query['p'])) {
                if (!is_scalar($this->query['p'])) {
                    throw new \InvalidArgumentException('预设尺寸参数无效');
                }

                foreach (explode(',', (string) $this->query['p']) as $preset) {
                    if (!isset($this->options['preset_sizes'][$preset])) {
                        throw new \InvalidArgumentException(sprintf('预设尺寸 "%s" 不存在', $preset));
                    }
                }
            }
        }

        $dpr = 1.0;
        if (isset($this->query['dpr']) && is_scalar($this->query['dpr']) && is_numeric($this->query['dpr'])) {
            $dpr = min(8.0, max(1.0, (float) $this->query['dpr']));
        }

        $width = isset($this->query['w']) && is_scalar($this->query['w']) ? (int) $this->query['w'] : 0;
        $height = isset($this->query['h']) && is_scalar($this->query['h']) ? (int) $this->query['h'] : 0;
        $width = (int) round($width * $dpr);
        $height = (int) round($height * $dpr);

        if (($this->options['max_width'] > 0 && $width > $this->options['max_width'])
            || ($this->options['max_height'] > 0 && $height > $this->options['max_height'])) {
            throw new \InvalidArgumentException('请求的图片尺寸超出限制');
        }
    }

    /**
     * @param Server $server
     * @param string $uri
     * @return Response|null
     * @throws \InvalidArgumentException
     */
    protected function purgeCache(Server $server, string $uri): ?Response
    {
        $param = (string) $this->options['cache_purge_param'];
        $purgeValue = (string) $this->options['cache_purge_value'];

        if ($param === '' || $purgeValue === '' || !isset($this->query[$param]) || !is_scalar($this->query[$param])) {
            return null;
        }

        if ((string) $this->query[$param] !== $purgeValue) {
            return null;
        }

        if (!$this->options['group_cache_in_folders']) {
            throw new \InvalidArgumentException('未启用分组缓存，无法清除缓存');
        }

        $result = $server->deleteCache(ltrim($uri, '/'));

        return Response::create($result ? 'cache purged' : 'cache not found')->code($result ? 200 : 404);
    }

    /**
     * @param Server  $server
     * @param Request $request
     * @author youvision <indiboy@gmail.com>
     * @return mixed
     * @throws \League\Flysystem\FilesystemException
     * @throws \League\Glide\Filesystem\FileNotFoundException
     */
    protected function handleRequest(Server $server, Request $request): mixed
    {
        //检查是否重新更新了
        $modifiedTime = null;
        if ($this->cacheTtl() > 0) {
            $modifiedTime = $server->getSource()
                ->lastModified($server->getSourcePath($request->pathinfo()));
            $response = $this->applyModified($modifiedTime, $request);
            if (false !== $response) {
                return $response;
            }
        }
        //如果已经更新了重新从缓存拉取图像
        if (null === $server->getResponseFactory()) {
            $server->setResponseFactory(new ResponseFactory());
        }
        $response = $server->getImageResponse($request->pathinfo(), $this->query);

        return $response instanceof Response ? $this->applyCacheHeaders($response, $modifiedTime) : $response;
    }

    /**
     * 附加缓存标识
     * @param Response $response
     * @param $modifiedTime
     * @return Response
     */
    protected function applyCacheHeaders(Response $response, $modifiedTime): Response
    {
        $ttl = max(0, $this->cacheTtl());

        return $response
            ->header([
                'Cache-Control' => 'public,max-age=' . $ttl,
                'Date' => gmdate('D, j M Y G:i:s \G\M\T', time()),
                'Last-Modified' => gmdate('D, j M Y G:i:s \G\M\T', (int) $modifiedTime),
                'Expires' => gmdate('D, j M Y G:i:s \G\M\T', time() + $ttl)
            ]);
    }

    /**
     * @return int
     */
    protected function cacheTtl(): int
    {
        if (null !== $this->options['cache_ttl']) {
            return (int) $this->options['cache_ttl'];
        }

        $cacheTime = $this->options['cacheTime'];

        return $cacheTime ? max(0, strtotime($cacheTime) - time()) : 0;
    }

    /**
     * @param int     $modifiedTime
     * @param Request $request
     * @author youvision <indiboy@gmail.com>
     * @return false|Response
     */
    protected function applyModified($modifiedTime, Request $request)
    {
        //如果没有修改直接返回
        if ($this->isNotModified($request, $modifiedTime)) {
            $response = Response::create('')->code(304);
            return $this->applyCacheHeaders($response, $modifiedTime);
        }
        return false;
    }

    /**
     * @param Request $request
     * @param $modifiedTime
     * @author youvision <indiboy@gmail.com>
     * @return bool
     */
    protected function isNotModified(Request $request, $modifiedTime)
    {
        $modifiedSince = $request->header('If-Modified-Since');
        if (!$modifiedSince) {
            return false;
        }
        return strtotime($modifiedSince) === (int) $modifiedTime;
    }

    /**
     * @param string $uri
     * @throws \League\Glide\Signatures\SignatureException
     */
    protected function checkSignature(string $uri)
    {
        if (!$this->options['signKey']) {
            return;
        }
        SignatureFactory::create($this->options['signKey'])->validateRequest(
            $uri,
            $this->query
        );
    }

    /**
     * @author youvision <indiboy@gmail.com>
     * @return Server
     */
    protected function createGlideServer(): Server
    {
        $defaults = (array) $this->options['defaults'];

        if ($this->options['watermarks_enabled']) {
            $defaults = array_merge($defaults, (array) $this->options['watermark_defaults']);
        }

        $config = [
            'source' => $this->options['source'] ?: $this->resolvePath($this->options['source_dir']),
            'cache' => $this->options['cache'] ?: $this->resolvePath($this->options['cache_dir']),
            'base_url' => $this->options['baseUrl'],
            'group_cache_in_folders' => (bool) $this->options['group_cache_in_folders'],
            'driver' => $this->options['driver'],
            'max_image_size' => $this->options['max_image_size'],
            'defaults' => $defaults,
            'presets' => (array) $this->options['preset_sizes'],
        ];

        if ($this->options['watermarks_enabled'] && $this->options['watermarks']) {
            $config['watermarks'] = $this->resolvePath($this->options['watermarks']);
        }

        return ServerFactory::create(array_merge($config, (array) $this->options['glide']));
    }

    /**
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if ($path[0] === '/' || $path[0] === '\\' || preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            return $path;
        }

        return rtrim((string) $this->options['web_root'], '/\\') . DIRECTORY_SEPARATOR . trim($path, '/\\');
    }
}