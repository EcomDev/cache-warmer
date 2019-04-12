<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @test
     * @testWith ["http://localhost.com/", "http://localhost.com/"]
     *           ["http://127.0.0.1:8080/", "http://127.0.0.1:8080/"]
     *           ["http://localhost.com/path", "http://localhost.com/"]
     *           ["/path", "/"]
     */
    public function buildsBaseUrl(string $providedUrl, string $expectedBaseUrl)
    {
        $this->assertEquals($expectedBaseUrl, Url::createFromString($providedUrl)->buildBaseUrl());
    }

    /**
     * @test
     * @testWith ["http://localhost.com/", "/"]
     *           ["http://127.0.0.1:8080/", "/"]
     *           ["http://localhost.com/path", "/path"]
     *           ["http://localhost.com/path/other?path", "/path/other?path"]
     */
    public function buildsRelativeUrl(string $providedUrl, string $expectedRelativeUrl)
    {
        $this->assertEquals($expectedRelativeUrl, Url::createFromString($providedUrl)->buildRelativeUrl());
    }

    /**
     * @test
     * @testWith ["http://localhost.com/", true]
     *           ["http://127.0.0.1:8080/", true]
     *           ["http://localhost.com/path", true]
     *           ["/path", false]
     *           ["http://", false]
     */
    public function detectsIfProvidedUrlIsAbsolute(string $providedUrl, bool $isAbsolute)
    {
        $this->assertEquals($isAbsolute, Url::createFromString($providedUrl)->isAbsolute());
    }

    /**
     * @test
     * @testWith ["http://localhost.com/", true]
     *           ["http://localhost.comr", true]
     *           ["http://127.0.0.1:8080/", true]
     *           ["http://localhost.com/path", true]
     *           ["/path", true]
     *           ["http://", false]
     *           ["", false]
     */
    public function detectsIfUrlIsValid(string $providedUrl, bool $isValid)
    {
        $this->assertEquals($isValid, Url::createFromString($providedUrl)->isValid());
    }
}
