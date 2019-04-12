<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class UrlRegexp
{
    const VARIATION_PREFIX_REGEXP_SUFFIX = ')(|%1$s|%1$s.*))?';
    const VARIATION_PREFIX_REGEXP = '%s((';
    const PATTERN_WITH_VARIATIONS = '^%s(%s%s%s)?%s';
    const PATTERN_WITHOUT_VARIATIONS = '^%s%s';
    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string[]
     */
    private $variations = [];

    /** @var string */
    private $endMatcher = '$';

    /** @var string */
    private $variationPrefix = '';

    /** @var int[] */
    private $length = [];

    public function __construct()
    {
        $this->calculateLength();
    }

    public function __toString(): string
    {
        if ($this->variations) {
            return $this->renderWithVariations(implode('|', $this->variations));
        }

        return $this->renderWithoutVariations();
    }

    public function withPath(string $path): self
    {
        $regexp = clone $this;
        $regexp->path = $path;
        $regexp->calculateLength();
        return $regexp;
    }

    public function withVariation(string... $variations): self
    {
        $regexp = clone $this;
        foreach ($variations as $variation) {
            $regexpVariation = preg_quote($variation);

            $regexp->variations[] = $regexpVariation;
            $regexp->length['variations'] += strlen($regexpVariation) + (count($this->variations) > 1 ? 1 : 0);
        }

        return $regexp;
    }

    public function withAnyQuery(): self
    {
        $regexp = clone $this;
        $regexp->endMatcher = '(\?.*|)$';
        $regexp->calculateLength();
        return $regexp;
    }

    public function withVariationPrefix(string $prefix): self
    {
        $regexp = clone $this;
        $regexp->variationPrefix = $prefix;
        $regexp->calculateLength();
        return $regexp;
    }

    /** @return self[] */
    public function splitByLimit(int $bytes): array
    {
        $limitedItem = $this->withoutVariations();
        $splitItems = [];

        foreach ($this->variations as $variation) {
            if ($limitedItem->isLimitReached($bytes, strlen($variation))) {
                $splitItems[] = $limitedItem;
                $limitedItem = $limitedItem->withoutVariations();
            }

            $limitedItem = $limitedItem->withVariation($variation);
        }

        $splitItems[] = $limitedItem;
        return $splitItems;
    }

    private function withoutVariations(): self
    {
        $withoutVariations = clone $this;
        $withoutVariations->variations = [];
        $withoutVariations->length['variations'] = 0;
        return $withoutVariations;
    }

    public function isLimitReached(int $limit, int $variationLength): bool
    {
        return ($this->length['variations'] + $this->length['pattern_with_variation'] + $variationLength) >= $limit;
    }

    private function calculateLength(): void
    {
        $this->length['pattern_with_variation'] = strlen($this->renderWithVariations(''));
        $this->length['variations'] = $this->length['variations'] ?? 0;
    }

    private function renderWithVariations(string $variations): string
    {
        return sprintf(
            self::PATTERN_WITH_VARIATIONS,
            preg_quote($this->path),
            $this->variationPrefix ? sprintf(self::VARIATION_PREFIX_REGEXP, $this->variationPrefix) : '',
            $variations,
            $this->variationPrefix ? sprintf(self::VARIATION_PREFIX_REGEXP_SUFFIX, $this->variationPrefix) : '',
            $this->endMatcher
        );
    }

    private function renderWithoutVariations(): string
    {
        return sprintf(
            self::PATTERN_WITHOUT_VARIATIONS,
            preg_quote($this->path),
            $this->endMatcher
        );
    }
}
