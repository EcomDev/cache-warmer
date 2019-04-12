<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class CleanPageCacheCommandTest extends TestCase implements CommandObserver
{
    /** @var CleanPageCacheCommandFactory */
    private $factory;

    /** @var FakeHttpClient */
    private $httpClient;

    /** @var Command[] */
    private $completedCommands = [];

    protected function setUp(): void
    {
        $this->httpClient = new FakeHttpClient();
        $this->factory = CleanPageCacheCommandFactory::create($this->httpClient);
    }

    /** @test */
    public function cleansCacheForSimplePage()
    {
        $command = $this->factory->createFromInput(['url' => 'http://localhost.com/page']);
        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page$']]
            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /** @test */
    public function cleansCacheForPageWithVariations()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['/one', '/two', '/three']
        ]);

        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page(/one|/two|/three)?$']]
            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /** @test */
    public function splitsVariationsIntoConfiguredHeaderValueLengthLimit()
    {
        $command = $this->factory
            ->withHeaderLengthLimit(32)
            ->createFromInput([
                'url' => 'http://localhost.com/page',
                'variations' => ['/one', '/two', '/three', '/four', '/five']
            ]);

        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page(/one|/two|/three|/four)?$']],
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page(/five)?$']]

            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /** @test */
    public function notifiesCompletionOfTheBanRequest()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['/one', '/two', '/three', '/four', '/five']
        ]);

        $command->execute($this);

        $this->assertEquals([$command], $this->completedCommands);
    }

    /** @test */
    public function notifiesCompletedCommandOncePerExecution()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['/one', '/two', '/three', '/four', '/five']
        ]);

        $command->execute($this);
        $command->execute($this);

        $this->assertEquals([$command, $command], $this->completedCommands);
    }


    /** @test */
    public function doesNotNotifyOfCompletionBeforeRequestIsComplete()
    {
        $factory = $this->factory->withHttpClient(
            $this->httpClient->withHangRequest('BAN', 'http://localhost.com/')
        );

        $command = $factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['/one', '/two', '/three', '/four', '/five']
        ]);

        $command->execute($this);

        $this->assertEquals([], $this->completedCommands);
    }

    /** @test */
    public function doesNotNotifyOfCompletionOfPartiallyCompletedRequests()
    {
        $command = $this->factory
            ->withHttpClient(
                $this->httpClient->withHangRequestAfter('BAN', 'http://localhost.com/', 1)
            )
            ->withHeaderLengthLimit(32)
            ->createFromInput([
                'url' => 'http://localhost.com/page',
                'variations' => ['/one', '/two', '/three', '/four', '/five']
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
            ->createFromInput(['url' => 'http://localhost.com/page']);

        $command->execute($this);

        $this->assertEquals([$command], $this->completedCommands);
    }

    /** @test */
    public function allowsToSpecifyPrefixForVariations()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['one', 'two', 'three'],
            'variation_prefix' => '/'
        ]);

        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page(/((one|two|three)(|/|/.*))?)?$']]
            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /** @test */
    public function allowsToSpecifyAnyQueryStringMatcher()
    {
        $command = $this->factory->createFromInput([
            'url' => 'http://localhost.com/page',
            'variations' => ['/one', '/two', '/three'],
            'any_query' => true
        ]);

        $command->execute($this);

        $this->assertEquals(
            [
                ['BAN', 'http://localhost.com/', ['X-Ban-Regexp' => '^/page(/one|/two|/three)?(\?.*|)$']]
            ],
            $this->httpClient->flushVisitedUrls()
        );
    }

    /**
     * @test
     * @testWith [[]]
     *  [{"url":""}]
     */
    public function doesNotAllowEmptyUrl(array $input)
    {
        $this->expectException(InvalidCommandInput::class);
        $this->expectExceptionMessage('Command requires at least an URL being specified');

        $this->factory->createFromInput($input);
    }

    /**
     * @test
     * @testWith ["/"]
     *           ["http://"]
     *           ["localhost"]
     */
    public function requiresFullUrlBeingSpecified(string $url)
    {
        $this->expectException(InvalidCommandInput::class);
        $this->expectExceptionMessage('Command requires an absolute URL');

        $this->factory->createFromInput(['url' => $url]);
    }
    
    /** @test */
    public function doesNotAllowMalformedVariations()
    {
        $this->expectException(InvalidCommandInput::class);
        $this->expectExceptionMessage('Variations must be an array of path strings');

        $this->factory->createFromInput(['url' => 'http://localhost.com/', 'variations' => false]);
    }

    public function completeExecution(Command $command): void
    {
        $this->completedCommands[] = $command;
    }
}
