<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use EcomDev\CacheWarmer\UrlRegexp;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    const PORT_FOR_TEST_SERVER = 8888;

    /** @var \Psr\Http\Message\ResponseInterface */
    private $lastResponse;

    /** @var UrlRegexp */
    private $urlRegExp;

    /** @var TestServerClient */
    private $testServerClient;

    /** @var BackgroundJob */
    private static $testHttpServer;

    public function __construct()
    {
        $this->testServerClient = TestServerClient::create(self::PORT_FOR_TEST_SERVER);
        $this->urlRegExp = new UrlRegexp();
    }

    /** @BeforeSuite */
    public static function startTestHttpServer()
    {
        self::$testHttpServer = BackgroundJob::create(function () {
            TestCachedHttpServer::create(self::PORT_FOR_TEST_SERVER)->run();
        });
    }


    /** @AfterSuite */
    public static function shutdownBackgroundServer()
    {
        self::$testHttpServer->sendSignal(TestCachedHttpServer::SHUTDOWN_SIGNAL);
    }

    /** @AfterScenario */
    public function clearTestServerInstance()
    {
        self::$testHttpServer->sendSignal(TestCachedHttpServer::CLEAR_CACHE_SIGNAL);
    }

    /**
     * @Given I already visited :path page
     * @When I visit :path page
     */
    public function visitPage(string $page)
    {
        $this->lastResponse = $this->testServerClient->visitPage($page);
    }


    /**
     * @Then I see that page is cached
     */
    public function lastPageResultIsCache()
    {
        Assert::assertEquals(
            304,
            $this->lastResponse->getStatusCode(),
            'Page response code is not cached one'
        );
    }

    /**
     * @When I flush cache for :path page
     */
    public function flushPageCache(string $path)
    {
        $this->testServerClient->banByPath($path);
    }


    /**
     * @Then I see that :path page cache was cleared
     */
    public function validatePageHasCacheHasBeenFlushed(string $path)
    {
        Assert::assertFalse($this->testServerClient->isCached($path));
    }

    /**
     * @Then I see that :path page cache was not cleared
     */
    public function validatePageHasCacheHasNotBeenFlushed($path)
    {
        Assert::assertTrue($this->testServerClient->isCached($path));
    }

    /**
     * @When I flush cache for :path page with variations
     */
    public function flushPageCacheWithVariations($path, TableNode $variations)
    {
        $this->testServerClient->banByRegExp(
            $this->urlRegExp->withPath($path)
                ->withVariation(...$variations->getColumn(0))
        );
    }

    /**
     * @Given I already visited :page page with response headers
     */
    public function visitPageWithCustomHeaders(string $page, TableNode $headers)
    {
        $headerHash = [];
        foreach ($headers->getRows() as $row) {
            $headerHash[$row[0]][] = $row[1];
        }

        $this->lastResponse = $this->testServerClient->visitPage($page, [
            'X-Response-Headers' => json_encode($headerHash)
        ]);
    }

    /**
     * @When I flush cache for pages with header :headerName equal to :headerValue
     */
    public function flushCacheByHeaderValue(string $headerName, string $headerValue)
    {
        $this->testServerClient->banByHeaderValue($headerName, $headerValue);
    }


}
