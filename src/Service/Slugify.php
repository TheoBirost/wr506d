<?php

namespace App\Service;

use Cocur\Slugify\Slugify as CocurSlugify;

class Slugify
{
    public function slugify(string $text): string
    {
        $slugify = new CocurSlugify();
        return $slugify->slugify($text);
    }
}
