<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class FakeCommandQueueTest extends TestCase
{
    /** @test */
    public function recordsAddedCommands()
    {
        $queue = FakeCommandQueue::create();
        $queue->add(IdentifiedNullCommandStub::create('one'));
        $queue->add(IdentifiedNullCommandStub::create('two'));
        $queue->add(IdentifiedNullCommandStub::create('three'));
        $this->assertEquals(
            [
                IdentifiedNullCommandStub::create('one'),
                IdentifiedNullCommandStub::create('two'),
                IdentifiedNullCommandStub::create('three'),
            ],
            $queue->flushAddedCommands()
        );
    }

    /** @test */
    public function flushesAddedCommands()
    {
        $queue = FakeCommandQueue::create();
        $queue->add(IdentifiedNullCommandStub::create('one'));
        $queue->add(IdentifiedNullCommandStub::create('two'));
        $queue->flushAddedCommands();

        $queue->add(IdentifiedNullCommandStub::create('three'));
        $this->assertEquals(
            [IdentifiedNullCommandStub::create('three')],
            $queue->flushAddedCommands()
        );
    }

    /** @test */
    public function recordsExecutedCommands()
    {
        $queue = FakeCommandQueue::create();
        $queue->add(IdentifiedNullCommandStub::create('one'));
        $queue->add(IdentifiedNullCommandStub::create('two'));
        $queue->execute();

        $queue->add(IdentifiedNullCommandStub::create('three'));

        $this->assertEquals(
            [
                IdentifiedNullCommandStub::create('one'),
                IdentifiedNullCommandStub::create('two')
            ],
            $queue->flushExecutedCommands()
        );
    }

    /** @test */
    public function flushesExecutedCommands()
    {
        $queue = FakeCommandQueue::create();
        $queue->add(IdentifiedNullCommandStub::create('one'));
        $queue->add(IdentifiedNullCommandStub::create('two'));
        $queue->execute();
        $queue->flushExecutedCommands();

        $queue->add(IdentifiedNullCommandStub::create('three'));
        $queue->execute();

        $this->assertEquals(
            [IdentifiedNullCommandStub::create('three')],
            $queue->flushExecutedCommands()
        );
    }
}
