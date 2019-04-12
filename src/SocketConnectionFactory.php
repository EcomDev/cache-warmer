<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class SocketConnectionFactory
{
    /**
     * @var CommandFactory
     */
    private $commandFactory;

    public function __construct(CommandFactory $commandFactory)
    {
        $this->commandFactory = $commandFactory;
    }

    public function create(): SocketConnection
    {
        return new SocketConnection($this->commandFactory);
    }
}
