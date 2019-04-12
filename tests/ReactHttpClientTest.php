<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactTestUtil\LoopFactory;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class ReactHttpClientTest extends TestCase
{
    const SERVER_PORT = 8889;
    const DYNAMIC_PAGE_RESPONSE = 'Dynamic Page Response';
    const NOT_CACHED_RESPONSE = 'Page is not cached';
    const CACHED_RESPONSE = 'Page is cached';
    const CACHE_CLEARED_RESPONSE = 'Cache is cleared';
    const NOT_MODIFIED_RESPONSE = '';

    /** @var \BackgroundJob */
    private static $testServerJob;

    /** @var ReactHttpClient */
    private $client;

    /** @var LoopInterface */
    private $loop;

    /** @var FakeHttpClientObserver */
    private $observer;

    public static function setUpBeforeClass(): void
    {
        self::$testServerJob = \BackgroundJob::create(
            function () {
                \TestCachedHttpServer::create(self::SERVER_PORT)->run();
            }
        );
        usleep(1000);
    }

    public static function tearDownAfterClass(): void
    {
        self::$testServerJob->sendSignal(\TestCachedHttpServer::SHUTDOWN_SIGNAL);
    }

    public function setUp(): void
    {
        self::$testServerJob->sendSignal(\TestCachedHttpServer::CLEAR_CACHE_SIGNAL);
        $this->observer = new FakeHttpClientObserver();

        $this->loop = LoopFactory::create()->createConditionRunLoopWithTimeout(function () {
            return !$this->observer->isWaiting();
        }, 10);

        $this->client = ReactHttpClientFactory::createWithEventLoop($this->loop)->create();
    }

    /** @test */
    public function allowsToRunSimpleGetRequestOnServer()
    {
        $this->visitUrl('GET', $this->createUrl('page'), []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse(
                $this->createUrl('page'),
                200,
                self::DYNAMIC_PAGE_RESPONSE
            )
        );
    }

    /** @test */
    public function executesGetEveryTimeItIsRequestedEveryTimeItAsks()
    {
        $url = $this->createUrl('page');
        $this->visitUrl('GET', $url, []);
        $this->visitUrl('GET', $url, []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($url, 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($url, 304, self::NOT_MODIFIED_RESPONSE)
        );
    }

    /** @test */
    public function executesCustomRequestMethods()
    {
        $url = $this->createUrl('page1');
        $this->visitUrl('CACHED', $url, []);
        $this->visitUrl('GET', $url, []);
        $this->visitUrl('CACHED', $url, []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($url, 404, self::NOT_CACHED_RESPONSE),
            $this->expectedResponse($url, 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($url, 200, self::CACHED_RESPONSE)
        );
    }

    /** @test */
    public function passesCustomRequestHeaders()
    {
        $url = $this->createUrl('page1');
        $rootUrl = $this->createUrl('/');

        $this->visitUrl('GET', $url, []);
        $this->visitUrl('CACHED', $url, []);
        $this->visitUrl('BAN', $rootUrl, ['X-Ban-Url' => '/page1']);
        $this->visitUrl('CACHED', $url, []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($url, 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($url, 200, self::CACHED_RESPONSE),
            $this->expectedResponse($rootUrl, 200, self::CACHE_CLEARED_RESPONSE),
            $this->expectedResponse($url, 404, self::NOT_CACHED_RESPONSE)
        );
    }

    /** @test */
    public function worksWellWithServerRegexpMatch()
    {
        $urls = [
            'root' => $this->createUrl('/'),
            'page1' => $this->createUrl('page1'),
            'page2' => $this->createUrl('page2'),
            'page3' => $this->createUrl('page3'),
        ];


        $this->visitUrl('GET', $urls['page1'], []);
        $this->visitUrl('GET', $urls['page2'], []);
        $this->visitUrl('GET', $urls['page3'], []);
        $this->visitUrl('BAN', $urls['root'], ['X-Ban-RegExp' => '/page[12]']);
        $this->visitUrl('CACHED', $urls['page1'], []);
        $this->visitUrl('CACHED', $urls['page2'], []);
        $this->visitUrl('CACHED', $urls['page3'], []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($urls['page1'], 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($urls['page2'], 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($urls['page3'], 200, self::DYNAMIC_PAGE_RESPONSE),
            $this->expectedResponse($urls['root'], 200, self::CACHE_CLEARED_RESPONSE),
            $this->expectedResponse($urls['page1'], 404, self::NOT_CACHED_RESPONSE),
            $this->expectedResponse($urls['page2'], 404, self::NOT_CACHED_RESPONSE),
            $this->expectedResponse($urls['page3'], 200, self::CACHED_RESPONSE)
        );
    }

    /** @test */
    public function notifiesOfErrorsInObtainingPages()
    {
        $this->visitUrl('GET', 'http://127.0.0.1:9999/', []);

        $this->loop->run();

        $this->assertEquals(
            [
                [
                    'http://127.0.0.1:9999/',
                    new \RuntimeException('Connection to tcp://127.0.0.1:9999 failed: Connection refused')
                ]
            ],
            $this->observer->flushErrorRequests()
        );
    }
    
    /** @test */
    public function allowsToSpecifyBaseUrlForRequest()
    {
        $this->client = $this->client->withBaseUrl($this->createUrl(''));

        $this->visitUrlRelative('GET', 'page1', []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($this->createUrl('page1'), 200, self::DYNAMIC_PAGE_RESPONSE)
        );
    }

    /** @test */
    public function baseUrlShouldBePossibleToUseEvenWithLeadingSlashInRelativeUrl()
    {
        $this->client = $this->client->withBaseUrl($this->createUrl(''));

        $this->visitUrlRelative('GET', '/page1', []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($this->createUrl('page1'), 200, self::DYNAMIC_PAGE_RESPONSE)
        );
    }

    
    /** @test */
    public function visitsAbsoluteUrlProperlyEvenIfBaseUrlIsSet()
    {
        $this->client = $this->client->withBaseUrl($this->createUrl(''));

        $this->visitUrl('GET', $this->createUrl('page2'), []);

        $this->loop->run();

        $this->assertCompletedRequests(
            $this->expectedResponse($this->createUrl('page2'), 200, self::DYNAMIC_PAGE_RESPONSE)
        );
    }


    private function visitUrl(string $method, string $url, array $headers): void
    {
        $this->observer->waitForRequest($url);
        $this->executeVisitUrlOnClient($method, $url, $headers);
    }

    private function visitUrlRelative(string $method, string $url, array $headers): void
    {
        $this->observer->waitForRequest($this->createUrl($url));

        $this->executeVisitUrlOnClient($method, $url, $headers);
    }

    private function createUrl(string $path): string
    {
        $url = sprintf('http://127.0.0.1:%d/%s', self::SERVER_PORT, ltrim($path, '/'));

        return $url;
    }

    private function assertCompletedRequests(array... $completedRequests): void
    {
        $this->assertEquals(
            $completedRequests,
            $this->observer->flushCompletedRequests()
        );
    }

    private function expectedResponse(string $url, int $status, string $responseText): array
    {
        return [
            $url,
            $status,
            [
                'Content-Type' => 'text/plain',
                'Content-Length' => strlen($responseText)
            ],
            $responseText
        ];
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $headers
     *
     */
    private function executeVisitUrlOnClient(string $method, string $url, array $headers): void
    {
        $this->client->visitUrl($method, $url, $headers, $this->observer);
    }
}
