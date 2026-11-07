<?php

namespace App\Features\Portfolios\Enums;

enum PortfolioStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
