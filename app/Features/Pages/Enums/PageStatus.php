<?php

namespace App\Features\Pages\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
