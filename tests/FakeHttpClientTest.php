<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class FakeHttpClientTest extends TestCase
{
    /** @var FakeHttpClient */
    private $client;

    /** @var FakeHttpClientObserver */
    private $observer;


    protected function setUp(): void
    {
        $this->client = new FakeHttpClient();
        $this->observer = new FakeHttpClientObserver();
    }

    /** @test */
    public function respondsNotFoundPageWhenNoPageResponsesAreConfigured()
    {
        $this->client->visitUrl('GET', 'http://localhost/', [], $this->observer);
        $this->assertReportedCompletedRequests(
            ['http://localhost/', 404, ['Content-Type' => 'text/plain', 'Content-Length' => 14], 'Page not found']
        );
    }

    /** @test */
    public function respondsWithPageContentAndStatusWhenConfigured()
    {
        $this->client->withPageContent('GET', 'http://localhost/', 'Home page')
            ->visitUrl('GET', 'http://localhost/', [], $this->observer);

        $this->assertReportedCompletedRequests(
            ['http://localhost/', 200, ['Content-Type' => 'text/plain', 'Content-Length' => 9], 'Home page']
        );
    }

    /** @test */
    public function allowsToSpecifyCustomResponseHeaders()
    {
        $this->client
            ->withPageContent(
                'GET',
                'http://localhost/',
                '{"page":"Home page"}',
                ['Content-Type' => 'application/json']
            )
            ->visitUrl(
                'GET',
                'http://localhost/',
                [],
                $this->observer
            );

        $this->assertReportedCompletedRequests(
            [
                'http://localhost/',
                200,
                ['Content-Type' => 'application/json', 'Content-Length' => 20],
                '{"page":"Home page"}'
            ]
        );
    }

    /** @test */
    public function allowsToSpecifyCustomResponseStatus()
    {
        $this->client
            ->withPageContent(
                'GET',
                'http://localhost/',
                'You are redirected',
                ['Location' => 'http://localhost/another'],
                301
            )
            ->visitUrl('GET', 'http://localhost/', [], $this->observer);

        $this->assertReportedCompletedRequests(
            [
                'http://localhost/',
                301,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Length' => 18,
                    'Location' => 'http://localhost/another'
                ],
                'You are redirected'
            ]
        );
    }

    /** @test */
    public function differentRequestMethodsResultInDifferentResponses()
    {
        $client = $this->client
            ->withPageContent('GET', 'http://localhost/one', 'Get request')
            ->withPageContent('POST', 'http://localhost/one', 'Post request');

        $client->visitUrl('GET', 'http://localhost/one', [], $this->observer);
        $client->visitUrl('POST', 'http://localhost/one', [], $this->observer);

        $this->assertReportedCompletedRequests(
            [
                'http://localhost/one',
                200,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Length' => 11,
                ],
                'Get request'
            ],
            [
                'http://localhost/one',
                200,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Length' => 12,
                ],
                'Post request'
            ]
        );
    }

    /** @test */
    public function allowsToSpecifyHangUrls()
    {
        $this->client
            ->withHangRequest('GET', 'http://localhost/hang')
            ->visitUrl('GET', 'http://localhost/hang', [], $this->observer);

        $this->assertReportedCompletedRequests();
    }

    /** @test */
    public function allowsToSpecifyNumberOfSuccessfulRequestsBeforeHangingRequest()
    {
        $client = $this->client
            ->withHangRequestAfter('GET', 'http://localhost/hang', 2);

        $client->visitUrl('GET', 'http://localhost/hang', [], $this->observer);
        $client->visitUrl('GET', 'http://localhost/hang', [], $this->observer);
        $client->visitUrl('GET', 'http://localhost/hang', [], $this->observer);

        $this->assertReportedCompletedRequests(
            ['http://localhost/hang', 404, ['Content-Type' => 'text/plain', 'Content-Length' => 14], 'Page not found'],
            ['http://localhost/hang', 404, ['Content-Type' => 'text/plain', 'Content-Length' => 14], 'Page not found']
        );
    }

    /** @test */
    public function allowsToSpecifyBaseUrlForUrlVisits()
    {
        $client = $this->client->withBaseUrl('http://localhost.com/');

        $client->visitUrl('GET', '/page', [], $this->observer);
        $client->visitUrl('GET', 'page', [], $this->observer);
        $client->visitUrl('GET', 'http://localhost/page', [], $this->observer);


        $this->assertReportedCompletedRequests(
            [
                'http://localhost.com/page',
                404,
                ['Content-Type' => 'text/plain', 'Content-Length' => 14],
                'Page not found'
            ],
            [
                'http://localhost.com/page',
                404,
                ['Content-Type' => 'text/plain', 'Content-Length' => 14],
                'Page not found'
            ],
            [
                'http://localhost/page',
                404,
                ['Content-Type' => 'text/plain', 'Content-Length' => 14],
                'Page not found'
            ]
        );
    }
    
    
    /** @test */
    public function noErrorsAreReportedWhenNoErrorUrlsAreProvided()
    {
        $this->client
            ->visitUrl('GET', 'http://localhost/error', [], $this->observer);

        $this->assertReportedErrorRequests();
    }

    /** @test */
    public function errorsAreReportedForConfiguredUrls()
    {
        $this->client
            ->withErrorRequest('GET', 'http://localhost/error')
            ->visitUrl('GET', 'http://localhost/error', [], $this->observer);

        $this->assertReportedErrorRequests(
            ['http://localhost/error', new \RuntimeException('Failed to load http://localhost/error')]
        );
    }

    /** @test */
    public function reportsErrorsOnlyForConfiguredRequestMethod()
    {
        $this->client
            ->withErrorRequest('POST', 'http://localhost/error')
            ->visitUrl('GET', 'http://localhost/error', [], $this->observer);

        $this->assertReportedErrorRequests();
    }

    /** @test */
    public function recordsVisitedUrls()
    {
        $this->client->visitUrl('GET', 'http://url.com/1', ['header' => 'value'], $this->observer);
        $this->client->visitUrl('POST', 'http://url.com/2', [], $this->observer);

        $this->assertEquals(
            [
                ['GET', 'http://url.com/1', ['header' => 'value']],
                ['POST', 'http://url.com/2', []],
            ],
            $this->client->flushVisitedUrls()
        );
    }

    /** @test */
    public function recordedVisitedUrlsAreFlushedAfterRetrieval()
    {
        $this->client->visitUrl('GET', 'http://url.com/1', ['header' => 'value'], $this->observer);
        $this->client->visitUrl('POST', 'http://url.com/2', [], $this->observer);

        $this->client->flushVisitedUrls();
        $this->client->visitUrl('POST', 'http://url.com/3', [], $this->observer);

        $this->assertEquals(
            [
                ['POST', 'http://url.com/3', []],
            ],
            $this->client->flushVisitedUrls()
        );
    }

    private function assertReportedCompletedRequests(array ...$completedRequests): void
    {
        $this->assertEquals(
            $completedRequests,
            $this->observer->flushCompletedRequests()
        );
    }

    private function assertReportedErrorRequests(array ...$errorRequests): void
    {
        $this->assertEquals(
            $errorRequests,
            $this->observer->flushErrorRequests()
        );
    }
}
