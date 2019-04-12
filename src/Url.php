<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class Url
{
    /**
     * @var array
     */
    private $urlParts;

    public function __construct(array $urlParts)
    {
        $this->urlParts = $urlParts;
    }

    public static function createFromString(string $url)
    {
        $urlParts = parse_url($url);

        if ($urlParts === false) {
            return new self([]);
        }

        return new self($urlParts);
    }

    public function buildBaseUrl(): string
    {
        return $this->buildAbsoluteUrlPart() . '/';
    }

    private function buildAbsoluteUrlPart(): string
    {
        if (!isset($this->urlParts['scheme'])) {
            return '';
        }

        return $this->urlParts['scheme']
            . '://'
            . $this->urlParts['host']
            . (isset($this->urlParts['port']) ? ':' . $this->urlParts['port'] : '');
    }

    public function buildRelativeUrl(): string
    {
         return $this->urlParts['path'] . (isset($this->urlParts['query']) ? '?' . $this->urlParts['query'] : '');
    }

    public function isAbsolute(): bool
    {
        return isset($this->urlParts['scheme']);
    }

    public function isValid(): bool
    {
        return !!$this->urlParts && $this->urlParts !== ['path' => ''];
    }
}
