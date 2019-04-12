<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

use EcomDev\CacheWarmer\ExceptionComparator;

SebastianBergmann\Comparator\Factory::getInstance()
    ->register(new ExceptionComparator());
