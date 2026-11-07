<?php

namespace App\Features\Services\Enums;

enum ServiceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
