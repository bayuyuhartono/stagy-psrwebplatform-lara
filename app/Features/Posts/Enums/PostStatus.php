<?php

namespace App\Features\Posts\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
