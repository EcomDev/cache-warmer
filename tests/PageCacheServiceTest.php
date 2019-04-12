<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class PageCacheServiceTest extends TestCase
{
    /** @var PageCacheService */
    private $cacheService;

    protected function setUp(): void
    {
        $this->cacheService = new PageCacheService();
    }

    /** @test */
    public function notVisitedPageReportedAsNotCached()
    {
        $this->assertFalse($this->cacheService->isCached('/page1'));
    }

    /** @test */
    public function visitedPagesAreCached()
    {
        $this->cacheService->visit('/page2', []);

        $this->assertTrue($this->cacheService->isCached('/page2'));
    }
    
    /** @test */
    public function reportsOnlyVisitedPagesNotOther()
    {
        $this->cacheService->visit('/page3', []);

        $this->assertFalse($this->cacheService->isCached('/page2'));
    }

    /** @test */
    public function clearsCachedVisitByPath()
    {
        $this->cacheService->visit('/page1', []);
        $this->cacheService->clearByPath('/page1');

        $this->assertFalse($this->cacheService->isCached('/page1'));
    }

    /** @test */
    public function clearsCachedVisitsByRegExp()
    {
        $this->cacheService->visit('/page1', []);
        $this->cacheService->visit('/page1/', []);
        $this->cacheService->visit('/page2', []);
        $this->cacheService->visit('/page3', []);

        $this->cacheService->clearByRegExp('/page[12]$');

        $this->assertEquals(
            [
                false,
                true,
                false,
                true
            ],
            [
                $this->cacheService->isCached('/page1'),
                $this->cacheService->isCached('/page1/'),
                $this->cacheService->isCached('/page2'),
                $this->cacheService->isCached('/page3'),
            ]
        );
    }

    /** @test */
    public function clearsCacheByHeaderMatch()
    {
        $this->cacheService->visit('/page1', ['Tag' => ['flag']]);
        $this->cacheService->visit('/page2', []);
        $this->cacheService->visit('/page3', ['Tag' => ['flag2', 'flag']]);
        $this->cacheService->visit('/page4', ['Tag' => ['flag2']]);

        $this->cacheService->clearByHeader('Tag', 'flag');

        $this->assertEquals(
            [
                false,
                true,
                false,
                true
            ],
            [
                $this->cacheService->isCached('/page1'),
                $this->cacheService->isCached('/page2'),
                $this->cacheService->isCached('/page3'),
                $this->cacheService->isCached('/page4'),
            ]
        );
    }

    /** @test */
    public function clearsAllCachedPages()
    {
        $this->cacheService->visit('/page1', []);
        $this->cacheService->visit('/page2', []);
        $this->cacheService->visit('/page3', []);
        $this->cacheService->visit('/page4', []);

        $this->cacheService->clearAll();

        $this->assertEquals(
            [
                false,
                false,
                false,
                false
            ],
            [
                $this->cacheService->isCached('/page1'),
                $this->cacheService->isCached('/page2'),
                $this->cacheService->isCached('/page3'),
                $this->cacheService->isCached('/page4'),
            ]
        );
    }
}
