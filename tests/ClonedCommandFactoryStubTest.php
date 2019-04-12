<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class ClonedCommandFactoryStubTest extends TestCase
{
    /** @var ClonedCommandFactoryStub */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = ClonedCommandFactoryStub::create(
            IdentifiedNullCommandStub::create('one')
        );
    }

    /** @test */
    public function createsInstanceThatEqualsToPrototype()
    {
        $this->assertEquals(
            IdentifiedNullCommandStub::create('one'),
            $this->factory->createFromInput([])
        );
    }

    /** @test */
    public function createdInstanceDoesNotMatchWrongIdentity()
    {
        $this->assertNotEquals(
            IdentifiedNullCommandStub::create('two'),
            $this->factory->createFromInput([])
        );
    }

    /** @test */
    public function createdInstancesAreNotTheSame()
    {
        $originalCommand = IdentifiedNullCommandStub::create('one');

        $this->assertNotSame(
            $originalCommand,
            ClonedCommandFactoryStub::create($originalCommand)->createFromInput([])
        );
    }
}
