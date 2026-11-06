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

namespace think\glide\middleware;

use think\App;
use think\facade\Config;
use think\facade\Request;
use think\Glide AS GlideFactory;

class Glide
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app  = $app;
    }

    /**
     * 图片缩放中间件
     * @param $request
     * @param \Closure $next
     * @author youvision <indiboy@gmail.com>
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        $config = Config::get('glide', []);
        $middleware = new GlideFactory($this->app, $config);

        return $middleware($request, $next);
    }
}