<?php
/**
 * +----------------------------------------------------------------------
 * | think-glide [thinkphp8]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Response.php
 * ( (  \.---./  ) )            | AUTHOR: youvision
 *  '.__/o   o\__.'             | EMAIL: indiboy@gmail.com
 *     {=  ^  =}                |
 *     /       \                | DATETIME: 2019/11/6 14:33
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

namespace think\glide;

use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use think\Response;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create response
     * @param FilesystemOperator $cache
     * @param string             $path
     * @author youvision <indiboy@gmail.com>
     * @return Response
     * @throws \League\Flysystem\FilesystemException
     */
    public function create(FilesystemOperator $cache, string $path): Response
    {
        $contentType = $cache->mimeType($path);
        $contentLength = $cache->fileSize($path);

        return Response::create()->data(stream_get_contents($cache->readStream($path)))
            ->header([
                'Content-Type' => $contentType,
                'Content-Length' => $contentLength
            ]);
    }
}