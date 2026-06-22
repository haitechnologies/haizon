<?php

declare(strict_types=1);

namespace App\Helper;

class SlugHelper
{
    public static function slugify(string $value): string
    {
        $val = preg_replace('/\s+/u', '-', trim($value));
        $val = str_replace(['(', ')', ':'], '', $val);
        $val = preg_replace('/-+/', '-', $val);
        $val = str_replace(['/', '&', ','], ['', '-', ''], $val);
        return strtolower(trim($val, '-'));
    }
}
