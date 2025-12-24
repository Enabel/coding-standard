<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Config;

enum ConflictResolution: string
{
    case SKIP = 'skip';
    case REPLACE = 'replace';
    case ASK = 'ask';
}
