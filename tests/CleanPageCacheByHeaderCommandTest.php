<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class CleanPageCacheByHeaderCommandTest extends TestCase implements CommandObserver
{
    /** @var CleanPageCacheByHeaderCommandFactory */
    private $factory;

    /** @var FakeHttpClient */
    private $httpClient;

    /** @var Command[] */
    private $completedCommands = [];

    protected function setUp(): void
    {
        $this->httpClient = new FakeHttpClient();
        $this->factory = CleanPageCacheByHeaderCommandFactory::create($this->httpClient);
    }

    /** @test */
    public function cleansPagesByHeaderValue()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com',
            'header_name' => 'X-Page-Tag',
            'header_value' => 'Page1'
        ]);

        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Header-Name' => 'X-Page-Tag', 'X-Ban-Header-Value' => 'Page1']]
            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /** @test */
    public function notifiesOfCompletedBanProcess()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com',
            'header_name' => 'X-Page-Tag',
            'header_value' => 'Page1'
        ]);

        $command->execute($this);

        $this->assertEquals([$command], $this->completedCommands);
    }

    /** @test */
    public function notifiesCompletedCommandOncePerExecution()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com',
            'header_name' => 'X-Page-Tag',
            'header_value' => 'Page1'
        ]);

        $command->execute($this);
        $command->execute($this);

        $this->assertEquals([$command, $command], $this->completedCommands);
    }

    /** @test */
    public function doesNotNotifyOfCompletionBeforeRequestIsComplete()
    {
        $command = $this->factory
            ->withHttpClient(
                $this->httpClient->withHangRequest('BAN', 'http://localhost.com/')
            )
            ->createFromInput([
                'url' => 'http://localhost.com',
                'header_name' => 'X-Page-Tag',
                'header_value' => 'Page1'
            ]);

        $command->execute($this);

        $this->assertEquals([], $this->completedCommands);
    }

    /** @test */
    public function completesExecutionWhenRequestResultedInError()
    {
        $command = $this->factory
            ->withHttpClient(
                $this->httpClient->withErrorRequest('BAN', 'http://localhost.com/')
            )
            ->createFromInput([
                'url' => 'http://localhost.com',
                'header_name' => 'X-Page-Tag',
                'header_value' => 'Page1'
            ]);

        $command->execute($this);

        $this->assertEquals([$command], $this->completedCommands);
    }

    public function completeExecution(Command $command): void
    {
        $this->completedCommands[] = $command;
    }
}
