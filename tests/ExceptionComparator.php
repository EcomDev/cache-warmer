<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

class ExceptionComparator extends Comparator
{
    public function accepts($expected, $actual)
    {
        return $expected instanceof \Throwable && $actual instanceof \Throwable;
    }

    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false)
    {
        if (is_object($actual) && is_object($expected) && \get_class($actual) !== \get_class($expected)) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $this->exporter->export($expected),
                $this->exporter->export($actual),
                false,
                \sprintf(
                    '%s is not instance of exception "%s".',
                    $this->exporter->export($actual),
                    \get_class($expected)
                )
            );
        }


        if ($actual !== $expected) {
            try {
                $this->factory->getComparatorFor($expected, $actual)->assertEquals(
                    $actual->getMessage(),
                    $expected->getMessage()
                );
            } catch (ComparisonFailure $e) {
                throw new ComparisonFailure(
                    $expected,
                    $actual,
                    \substr_replace($e->getExpectedAsString(), \get_class($expected) . ' Object', 0, 5),
                    \substr_replace($e->getActualAsString(), \get_class($actual) . ' Object', 0, 5),
                    false,
                    'Failed asserting that two exceptions are equal.'
                );
            }
        }
    }
}
