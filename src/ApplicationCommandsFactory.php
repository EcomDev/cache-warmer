<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class ApplicationCommandsFactory implements CommandFactory
{

    /**
     * @var CompositeCommandFactory
     */
    private $commandFactory;

    public function __construct(CompositeCommandFactory $commandFactory)
    {

        $this->commandFactory = $commandFactory;
    }

    public static function createWithHttpClient(HttpClient $httpClient): self
    {
        return new self(
            (new CompositeCommandFactory())
                ->withFactory('clean_page_cache', CleanPageCacheCommandFactory::create($httpClient))
                ->withFactory('clean_cache_by_header', CleanPageCacheByHeaderCommandFactory::create($httpClient))
        );
    }

    /**
     * Creates a command instance from input array
     *
     * @throws InvalidCommandInput
     */
    public function createFromInput(array $input): Command
    {
        return $this->commandFactory->createFromInput($input);
    }
}
