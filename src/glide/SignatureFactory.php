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

use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureInterface;

class SignatureFactory implements SignatureInterface
{
    /**
     * Secret key used to generate signature.
     * @var string
     */
    protected string $signKey;

    /**
     * Create Signature instance.
     * @param string $signKey Secret key used to generate signature.
     */
    public function __construct(string $signKey)
    {
        $this->signKey = $signKey;
    }

    /**
     * Add an HTTP signature to manipulation parameters.
     * @param string $path The resource path.
     * @param array $params The manipulation parameters.
     * @return array  The updated manipulation parameters.
     */
    public function addSignature(string $path, array $params): array
    {
        return array_merge($params, ['sign' => $this->generateSignature($path, $params)]);
    }

    /**
     * Validate a request signature.
     * @param string $path The resource path.
     * @param array $params The manipulation params.
     * @throws SignatureException
     */
    public function validateRequest(string $path, array $params): void
    {
        if (!isset($params['sign'])) {
            throw new SignatureException('Signature is missing.');
        }

        if ($params['sign'] !== $this->generateSignature($path, $params)) {
            throw new SignatureException('Signature is not valid.');
        }
    }

    /**
     * Generate an HTTP signature.
     * @param string $path The resource path.
     * @param array $params The manipulation parameters.
     * @return string The generated HTTP signature.
     */
    public function generateSignature(string $path, array $params): string
    {
        unset($params['sign']);
        ksort($params);

        return md5($this->signKey . ':' . ltrim($path, '/') . '?' . http_build_query($params));
    }

    /**
     * Create HttpSignature instance.
     * @param string $signKey Secret key used to generate signature.
     * @return SignatureFactory The HttpSignature instance.
     */
    public static function create(string $signKey): self
    {
        return new self($signKey);
    }
}