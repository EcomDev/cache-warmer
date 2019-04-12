<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class LimitedCommandQueueTest extends TestCase
{
    /** @var CommandSequenceRecorder */
    private $commandTester;

    protected function setUp(): void
    {
        $this->commandTester = new CommandSequenceRecorder();
    }

    /** @test */
    public function executesAllAddedCommands()
    {
        $queue = LimitedCommandQueue::create(100);
        $queue->add($this->commandTester->createCommand('Command #1'));
        $queue->add($this->commandTester->createCommand('Command #2'));
        $queue->add($this->commandTester->createCommand('Command #3'));

        $queue->execute();

        $this->assertEquals(
            [
                'Command #1',
                'Command #2',
                'Command #3'
            ],
            $this->commandTester->flushCommandSequence()
        );
    }

    /** @test */
    public function executesAddedCommandsOnlyOnce()
    {
        $queue = LimitedCommandQueue::create(100);
        $queue->add($this->commandTester->createCommand('Command #1'));
        $queue->add($this->commandTester->createCommand('Command #2'));
        $queue->add($this->commandTester->createCommand('Command #3'));

        $queue->execute();
        $queue->execute();

        $this->assertEquals(
            [
                'Command #1',
                'Command #2',
                'Command #3'
            ],
            $this->commandTester->flushCommandSequence()
        );
    }

    /** @test */
    public function executesLimitedAmountOfCommandPerExecution()
    {
        $queue = LimitedCommandQueue::create(2);
        $queue->add($this->commandTester->createCommand('Command #1'));
        $queue->add($this->commandTester->createCommand('Command #2'));
        $queue->add($this->commandTester->createCommand('Command #3'));

        $queue->execute();
        $this->commandTester->flushCommandSequence();
        $queue->execute();

        $this->assertEquals(
            ['Command #3'],
            $this->commandTester->flushCommandSequence()
        );
    }

    /** @test */
    public function waitsTillPreviousCommandsAreCompletedBeforeRunningNewOnes()
    {
        $queue = LimitedCommandQueue::create(2);
        $queue->add($this->commandTester->createCommand('Command #1'));
        $queue->add(IdentifiedNullCommandStub::create('Command that never ends #1'));
        $queue->add(IdentifiedNullCommandStub::create('Command that never ends #2'));
        $queue->add($this->commandTester->createCommand('Command #2'));
        $queue->add($this->commandTester->createCommand('Command #3'));


        $queue->execute();
        $queue->execute();

        $this->assertEquals(
            ['Command #1'],
            $this->commandTester->flushCommandSequence()
        );
    }
}
