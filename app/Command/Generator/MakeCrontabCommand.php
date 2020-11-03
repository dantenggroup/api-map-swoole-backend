<?php

declare(strict_types=1);

namespace App\Command\Generator;

use Hyperf\Command\Annotation\Command;
use Hyperf\Devtool\Generator\GeneratorCommand;
use Hyperf\Utils\Str;

/**
 * @Command
 */
class MakeCrontabCommand extends GeneratorCommand
{
    public function __construct()
    {
        parent::__construct('gen:crontab-command');
        $this->setDescription('Create a new crontab command class');
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/command.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());
        $stub = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
        return str_replace('dummy_command', Str::snake($this->getNameInput()), $stub);
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Command\\Crontab';
    }
}
