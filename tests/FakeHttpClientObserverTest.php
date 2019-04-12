<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class FakeHttpClientObserverTest extends TestCase
{
    /** @var FakeHttpClientObserver */
    private $observer;

    protected function setUp(): void
    {
        $this->observer = new FakeHttpClientObserver();
    }

    /** @test */
    public function reportsCompletedRequests()
    {
        $this->observer->requestComplete('uri1', 200, ['header1' => 'value1'], 'Some Data #1');
        $this->observer->requestComplete('uri2', 404, ['header2' => 'value2'], 'Some Data #2');

        $this->assertEquals(
            [
                ['uri1', 200, ['header1' => 'value1'], 'Some Data #1'],
                ['uri2', 404, ['header2' => 'value2'], 'Some Data #2'],
            ],
            $this->observer->flushCompletedRequests()
        );
    }

    /** @test */
    public function flushesCompletedRequests()
    {
        $this->observer->requestComplete('uri1', 200, ['header1' => 'value1'], 'Some Data #1');
        $this->observer->flushCompletedRequests();

        $this->assertEquals(
            [],
            $this->observer->flushCompletedRequests()
        );
    }
    
    /** @test */
    public function reportsRequestsWithErrors()
    {
        $this->observer->requestError('uri1', new \RuntimeException('Some runtime issue'));
        $this->observer->requestError('uri2', new \LogicException('Some logic issue'));

        $this->assertEquals(
            [
                ['uri1', new \RuntimeException('Some runtime issue')],
                ['uri2', new \LogicException('Some logic issue')],
            ],
            $this->observer->flushErrorRequests()
        );
    }

    /** @test */
    public function flushesRequestsWithErrors()
    {
        $this->observer->requestError('uri1', new \RuntimeException('Some runtime issue'));
        $this->observer->flushErrorRequests();

        $this->assertEquals(
            [],
            $this->observer->flushErrorRequests()
        );
    }

    /** @test */
    public function allowsToSpecifyWaitingForSpecificRequests()
    {
        $this->observer->waitForRequest('uri1');

        $this->assertTrue($this->observer->isWaiting());
    }

    /** @test */
    public function doesNotWaitForAnyRequestWhenNothingIsExpected()
    {
        $this->assertFalse($this->observer->isWaiting());
    }

    /** @test */
    public function resolvesAwaitedRequestOnSuccessFullResponse()
    {
        $this->observer->waitForRequest('uri1');
        $this->observer->requestComplete('uri1', 200, [], 'Something');

        $this->assertFalse($this->observer->isWaiting());
    }

    /** @test */
    public function resolvesOnlyRightAwaitedRequest()
    {
        $this->observer->waitForRequest('uri2');
        $this->observer->requestComplete('uri1', 200, [], 'Something');

        $this->assertTrue($this->observer->isWaiting());
    }

    /** @test */
    public function resolvesOnlyFirstAwaitedSameUrl()
    {
        $this->observer->waitForRequest('uri1');
        $this->observer->waitForRequest('uri1');
        $this->observer->requestComplete('uri1', 200, [], 'Something');

        $this->assertTrue($this->observer->isWaiting());
    }

    /** @test */
    public function resolvesAllAwaitedSameUrls()
    {
        $this->observer->waitForRequest('uri1');
        $this->observer->waitForRequest('uri1');
        $this->observer->requestComplete('uri1', 200, [], 'Something');
        $this->observer->requestComplete('uri1', 200, [], 'Something');

        $this->assertFalse($this->observer->isWaiting());
    }

    /** @test */
    public function resolvesAwaitedRequestWhenErrorIsReported()
    {
        $this->observer->waitForRequest('uri1');
        $this->observer->requestError('uri1', new \RuntimeException('Test'));

        $this->assertFalse($this->observer->isWaiting());
    }
}
