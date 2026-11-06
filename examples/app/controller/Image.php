<?php
declare(strict_types=1);

namespace app\controller;

use think\Response;

class Image
{
    public function index(): Response
    {
        return json([
            'thumb' => glide_url('user.jpg', ['w' => 100, 'h' => 100, 'fit' => 'crop']),
            'cover' => glide_url('user.jpg', ['w' => 750, 'h' => 420, 'fit' => 'crop']),
            'watermark' => glide_url('user.jpg', [
                'w' => 800,
                'mark' => 'logo.png',
                'markw' => 100,
                'markpos' => 'bottom-right',
            ]),
            'webp' => glide_url('user.jpg', ['w' => 600, 'fm' => 'webp', 'q' => 90]),
        ]);
    }
}