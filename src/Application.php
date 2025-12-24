<?php

declare(strict_types=1);

namespace Enabel\CodingStandard;

use Enabel\CodingStandard\Command\InitCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public const NAME = 'Enabel Coding Standard Initializer';
    public const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommand(new InitCommand());
        $this->setDefaultCommand('init');
    }
}
