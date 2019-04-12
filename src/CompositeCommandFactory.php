<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CompositeCommandFactory implements CommandFactory
{
    /** @var CommandFactory[] */
    private $factoryByType = [];

    public function createFromInput(array $input): Command
    {
        $type = $input['type'] ?? '';

        if (isset($this->factoryByType[$type])) {
            return $this->factoryByType[$type]->createFromInput($input);
        }

        throw new InvalidCommandInput();
    }

    public function withFactory(string $type, CommandFactory $factory): self
    {
        $duplicate = clone $this;
        $duplicate->factoryByType[$type] = $factory;
        return $duplicate;
    }
}
