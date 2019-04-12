<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class ApplicationCommandsFactoryTest extends TestCase
{
    /** @var ApplicationCommandsFactory */
    private $factory;

    private $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new FakeHttpClient();
        $this->factory = ApplicationCommandsFactory::createWithHttpClient($this->httpClient);
    }

    /** @test */
    public function createsCleanPageCacheCommand()
    {
        $this->assertEquals(
            CleanPageCacheCommandFactory::create($this->httpClient)
                ->createFromInput(['url' => 'http://example.com/page']),
            $this->factory->createFromInput(['type' => 'clean_page_cache', 'url' => 'http://example.com/page'])
        );
    }

    /** @test */
    public function createsCleanPageCacheByHeaderCommand()
    {
        $this->assertEquals(
            CleanPageCacheByHeaderCommandFactory::create($this->httpClient)
                ->createFromInput([
                    'url' => 'http://example.com/',
                    'header_name' => 'X-Page-Tag',
                    'header_value' => 'Page1'
                ]),
            $this->factory
                ->createFromInput([
                    'type' => 'clean_cache_by_header',
                    'url' => 'http://example.com/',
                    'header_name' => 'X-Page-Tag',
                    'header_value' => 'Page1'
                ])
        );
    }
}
