<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard;

use Enabel\CodingStandard\Command\CiAddCommand;
use Enabel\CodingStandard\Command\CiUpdateCommand;
use Enabel\CodingStandard\Command\InitCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public const NAME = 'Enabel Coding Standard Initializer';
    public const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->add(new InitCommand());
        $this->add(new CiUpdateCommand());
        $this->add(new CiAddCommand());
        $this->setDefaultCommand('init');
    }
}
