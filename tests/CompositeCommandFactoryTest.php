<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class CompositeCommandFactoryTest extends TestCase
{
    /** @test */
    public function doesNotProduceCommandsWhenConfigurationIsEmpty()
    {
        $this->expectException(InvalidCommandInput::class);

        $this->createFactory()
            ->createFromInput(['type' => 'purge']);
    }

    /** @test */
    public function producesCommandFromSimpleFactorySetup()
    {
        $factory = $this->createFactory()
            ->withFactory(
                'purge',
                ClonedCommandFactoryStub::create(
                    IdentifiedNullCommandStub::create('purge')
                )
            );

        $this->assertEquals(
            IdentifiedNullCommandStub::create('purge'),
            $factory->createFromInput(
                ['type' => 'purge']
            )
        );
    }

    /** @test */
    public function doesNotProduceCommandsWhenTypeIsInvalid()
    {
        $factory = $this->createFactory()
            ->withFactory(
                'purge',
                ClonedCommandFactoryStub::create(
                    IdentifiedNullCommandStub::create('purge')
                )
            );

        $this->expectException(InvalidCommandInput::class);

        $factory->createFromInput(['type' => 'prewarm']);
    }

    /**
     * @test
     * @testWith ["purge"]
     *           ["prewarm"]
     */
    public function choosesRightFactoryForType(string $type)
    {
        $factory =  $this->createFactory()
            ->withFactory(
                'purge',
                ClonedCommandFactoryStub::create(
                    IdentifiedNullCommandStub::create('purge')
                )
            )
            ->withFactory(
                'prewarm',
                ClonedCommandFactoryStub::create(
                    IdentifiedNullCommandStub::create('prewarm')
                )
            );

        $this->assertEquals(
            IdentifiedNullCommandStub::create($type),
            $factory->createFromInput(
                ['type' => $type]
            )
        );
    }

    private function createFactory(): CompositeCommandFactory
    {
        return new CompositeCommandFactory();
    }
}
