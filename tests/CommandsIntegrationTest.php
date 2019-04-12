<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactTestUtil\LoopFactory;
use PHPUnit\Framework\TestCase;

class CommandsIntegrationTest extends TestCase implements CommandObserver
{
    const SERVER_PORT = 9999;

    /** @var \BackgroundJob */
    private static $testServerJob;

    /** @var \TestServerClient */
    private $testServerClient;

    /** @var LoopFactory */
    private $loopFactory;

    /** @var string[] */
    private $completedCommands = [];

    public static function setUpBeforeClass(): void
    {
        self::$testServerJob = \BackgroundJob::create(
            function () {
                \TestCachedHttpServer::create(self::SERVER_PORT)->run();
            }
        );
    }

    public static function tearDownAfterClass(): void
    {
        self::$testServerJob->sendSignal(\TestCachedHttpServer::SHUTDOWN_SIGNAL);
    }

    public function setUp(): void
    {
        self::$testServerJob->sendSignal(\TestCachedHttpServer::CLEAR_CACHE_SIGNAL);

        $this->testServerClient = \TestCachedHttpServer::create(self::SERVER_PORT);
        $this->loopFactory = LoopFactory::create();
    }



    public function completeExecution(Command $command): void
    {
        $this->completedCommands[] = get_class($command);
    }

    public function isCommandComplete(string $type): callable
    {
        return function () use ($type) {
            return in_array($type, $this->completedCommands, true);
        };
    }
}
