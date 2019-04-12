<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class UrlRegexpTest extends TestCase
{
    /** @var UrlRegexp */
    private $urlRegexp;

    protected function setUp(): void
    {
        $this->urlRegexp = new UrlRegexp();
    }

    /** @test */
    public function createsEmptyRegexp()
    {
        $this->assertEquals('^$', $this->urlRegexp);
    }

    /** @test */
    public function createsStrictPathMatching()
    {
        $this->assertEquals('^/path$', $this->urlRegexp->withPath('/path'));
    }

    /** @test */
    public function escapesPathForRegexpChars()
    {
        $this->assertEquals('^/path\[0\]\.$', $this->urlRegexp->withPath('/path[0].'));
    }

    /** @test */
    public function createsPathWithVariation()
    {
        $this->assertEquals(
            '^/path(/)?$',
            $this->urlRegexp->withPath('/path')->withVariation('/')
        );
    }

    /** @test */
    public function createsPathWithMultipleVariationsByChaining()
    {
        $this->assertEquals(
            '^/path(/|/item1|/item2|/item3)?$',
            $this->urlRegexp->withPath('/path')
                ->withVariation('/')
                ->withVariation('/item1')
                ->withVariation('/item2')
                ->withVariation('/item3')
        );
    }

    /** @test */
    public function createsPathWithMultipleVariationsDirectly()
    {
        $this->assertEquals(
            '^/path(/|/item1|/item2|/item3)?$',
            $this->urlRegexp->withPath('/path')
                ->withVariation('/', '/item1', '/item2', '/item3')
        );
    }

    /** @test */
    public function createsPathWithVariationDirectly()
    {
        $this->assertEquals(
            '^/path(/|/item2|/item3)?$',
            $this->urlRegexp->withPath('/path')
                ->withVariation('/', '/item2', '/item3')
        );
    }
    
    /** @test */
    public function createsPathWithAnyQueryStringInTheEnd()
    {
        $this->assertEquals(
            '^/path(\?.*|)$',
            $this->urlRegexp->withPath('/path')
                ->withAnyQuery()
        );
    }

    /** @test */
    public function createsVariationsIncludingAnyQueryStringInTheEnd()
    {
        $this->assertEquals(
            '^/path(/|/one|/two)?(\?.*|)$',
            $this->urlRegexp
                ->withPath('/path')
                ->withVariation('/', '/one', '/two')
                ->withAnyQuery()
        );
    }

    /** @test */
    public function escapesVariationsToProtectFromRegexInjection()
    {
        $this->assertEquals(
            '^/path(/\.\?|/item2\*|/item3\+)?$',
            $this->urlRegexp->withPath('/path')
                ->withVariation('/.?', '/item2*', '/item3+')
        );
    }

    /** @test */
    public function allowsToSpecifyPrefixForVariations()
    {
        $this->assertEquals(
            '^/path(/((item1|item2)(|/|/.*))?)?$',
            (string)$this->urlRegexp->withPath('/path')
                ->withVariationPrefix('/')
                ->withVariation('item1', 'item2')
        );
    }

    /** @test */
    public function splitsVariationsIntoMultipleRegexpStatements()
    {

        $this->assertEquals(
            [
                $this->urlRegexp->withPath('/value')->withVariation('/one', '/two'),
                $this->urlRegexp->withPath('/value')->withVariation('/three', '/four'),
                $this->urlRegexp->withPath('/value')->withVariation('/five'),
            ],
            $this->urlRegexp->withPath('/value')
                ->withVariation('/one', '/two', '/three', '/four', '/five')
                ->splitByLimit(24)
        );
    }

    /** @test */
    public function splitsVariationsIntoMultipleRegexpStatementsWithQueryMatch()
    {
        $this->assertEquals(
            [
                $this->urlRegexp->withPath('/value')
                    ->withAnyQuery()
                    ->withVariation('/one'),
                $this->urlRegexp->withPath('/value')
                    ->withAnyQuery()
                    ->withVariation('/two'),
                $this->urlRegexp->withPath('/value')
                    ->withAnyQuery()
                    ->withVariation('/three'),
                $this->urlRegexp->withPath('/value')
                    ->withAnyQuery()
                    ->withVariation('/four'),
                $this->urlRegexp->withPath('/value')
                    ->withAnyQuery()
                    ->withVariation('/five'),
            ],
            $this->urlRegexp->withPath('/value')
                ->withVariation('/one', '/two', '/three', '/four', '/five')
                ->withAnyQuery()
                ->splitByLimit(24)
        );
    }

    /** @test */
    public function splitsVariationsIntoMultipleRegexpStatementsWithVariationPrefix()
    {
        $this->assertEquals(
            [
                $this->urlRegexp->withPath('/value')
                    ->withVariation('one', 'two')
                    ->withVariationPrefix('/'),
                $this->urlRegexp->withPath('/value')
                    ->withVariation('three')
                    ->withVariationPrefix('/'),
                $this->urlRegexp->withPath('/value')
                    ->withVariation('four')
                    ->withVariationPrefix('/'),
                $this->urlRegexp->withPath('/value')
                    ->withVariation('five')
                    ->withVariationPrefix('/'),
            ],
            $this->urlRegexp->withPath('/value')
                ->withVariation('one', 'two', 'three', 'four', 'five')
                ->withVariationPrefix('/')
                ->splitByLimit(32)
        );
    }
}
