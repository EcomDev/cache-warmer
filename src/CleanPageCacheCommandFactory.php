<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CleanPageCacheCommandFactory implements CommandFactory
{
    const TWO_KILOBYTES = 2*1024;

    /** @var HttpClient */
    private $httpClient;
    /**
     * @var UrlRegexp
     */
    private $urlRegexp;

    /** @var int */
    private $headerLimit;

    public function __construct(
        HttpClient $httpClient,
        UrlRegexp $urlRegexp,
        int $headerLimit
    ) {
        $this->httpClient = $httpClient;
        $this->urlRegexp = $urlRegexp;
        $this->headerLimit = $headerLimit;
    }

    public static function create(HttpClient $httpClient): self
    {
        return new self($httpClient, new UrlRegexp(), self::TWO_KILOBYTES);
    }

    public function withHeaderLengthLimit(int $limit): self
    {
        $factory = clone $this;
        $factory->headerLimit = $limit;
        return $factory;
    }

    /**
     * {@inheritdoc}
     * @return CleanPageCacheCommand
     */
    public function createFromInput(array $input): Command
    {
        $url = $this->createUrl($input);

        $variations = $input['variations'] ?? [];
        if (!is_array($variations)) {
            throw new InvalidCommandInput('Variations must be an array of path strings');
        }

        $urlRegexp = $this->urlRegexp->withPath($url->buildRelativeUrl())
            ->withVariation(...$variations)
            ->withVariationPrefix($input['variation_prefix'] ?? '');

        $anyQuery = $input['any_query'] ?? false;

        if ($anyQuery === true) {
            $urlRegexp = $urlRegexp->withAnyQuery();
        }

        return new CleanPageCacheCommand(
            $this->httpClient,
            $url->buildBaseUrl(),
            $urlRegexp,
            $this->headerLimit
        );
    }

    public function withHttpClient(HttpClient $httpClient): self
    {
        $factory = clone $this;
        $factory->httpClient = $httpClient;
        return $factory;
    }

    private function createUrl(array $input): Url
    {
        if (empty($input['url'])) {
            throw new InvalidCommandInput('Command requires at least an URL being specified');
        }

        $url = Url::createFromString($input['url']);

        if (!$url->isAbsolute()) {
            throw new InvalidCommandInput('Command requires an absolute URL');
        }

        return $url;
    }
}
