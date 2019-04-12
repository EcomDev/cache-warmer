<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class SocketConnectionTest extends TestCase
{
    /** @var SocketConnectionFactory */
    private $factory;

    /** @var FakeCommandQueue */
    private $commandQueue;

    protected function setUp(): void
    {
        $this->factory = new SocketConnectionFactory(
            $this->createCompositeFactoryWithStubbedCommands('command1', 'command2', 'command3')
        );

        $this->commandQueue = new FakeCommandQueue();
    }

    /**
     * @test
     * @testWith ["command1"]
     *           ["command2"]
     *           ["command3"]
     */
    public function createsCommandFromSingleJSONLine(string $type)
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData(sprintf('{"type": "%s"}%s', $type, "\n"))
            ->scheduleCommands($this->commandQueue)
            ;

        $this->assertEquals(
            [IdentifiedNullCommandStub::create($type)],
            $this->commandQueue->flushAddedCommands()
        );
    }


    /**
     * @test
     */
    public function createsCommandFromSeparatedJSONParts()
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData('{"type": ')
            ->withInputData('"command1"}' . "\n")
            ->scheduleCommands($this->commandQueue)
        ;

        $this->assertEquals(
            [IdentifiedNullCommandStub::create('command1')],
            $this->commandQueue->flushAddedCommands()
        );
    }

    /** @test */
    public function createsMultipleCommandsFromSeparatedJSONParts()
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData('{"type": "command1"}' . "\n")
            ->withInputData('{"type": "command2"}' . "\n")
            ->withInputData('{"type": "command3"}' . "\n")
            ->scheduleCommands($this->commandQueue)
        ;

        $this->assertEquals(
            [
                IdentifiedNullCommandStub::create('command1'),
                IdentifiedNullCommandStub::create('command2'),
                IdentifiedNullCommandStub::create('command3'),
            ],
            $this->commandQueue->flushAddedCommands()
        );
    }

    /** @test */
    public function ignoresIncompleteLines()
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData('{"type": "command1"}')
            ->scheduleCommands($this->commandQueue)
        ;

        $this->assertEquals(
            [],
            $this->commandQueue->flushAddedCommands()
        );
    }

    /** @test */
    public function ignoresWrongJsonLines()
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData(sprintf('%1$ssomewrongtext%1$s{"type":"command1"}%1$s', "\n"))
            ->scheduleCommands($this->commandQueue)
        ;

        $this->assertEquals(
            [
                IdentifiedNullCommandStub::create('command1')
            ],
            $this->commandQueue->flushAddedCommands()
        );
    }
    
    /** @test */
    public function reportsErrorsForInvalidJsonStructure()
    {
        $errorReporter = new InMemoryInputErrorReporter();
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData(sprintf('%1$ssomewrongtext%1$s{"type":"command1"}%1$s', "\n"))
            ->scheduleCommands($this->commandQueue)
            ->reportErrors($errorReporter);

        $this->assertEquals(
            [
                ['somewrongtext', new InvalidJsonStructure()]
            ],
            $errorReporter->flushErrors()
        );
    }

    /** @test */
    public function ignoresInvalidCommandTypes()
    {
        $socketConnection = $this->factory->create();

        $socketConnection
            ->withInputData('{"type": "command1"}' . "\n")
            ->withInputData('{"type": "invalid"}' . "\n")
            ->scheduleCommands($this->commandQueue)
        ;

        $this->assertEquals(
            [
                IdentifiedNullCommandStub::create('command1'),
            ],
            $this->commandQueue->flushAddedCommands()
        );
    }

    /** @test */
    public function reportsErrorsForInvalidCommandTypes()
    {
        $socketConnection = $this->factory->create();

        $errorReporter = new InMemoryInputErrorReporter();

        $socketConnection
            ->withInputData('{"type": "command1"}' . "\n")
            ->withInputData('{"type": "invalid"}' . "\n")
            ->scheduleCommands($this->commandQueue)
            ->reportErrors($errorReporter)
        ;

        $this->assertEquals(
            [
                ['{"type": "invalid"}', new InvalidCommandInput()]
            ],
            $errorReporter->flushErrors()
        );
    }

    /** @test */
    public function errorsAreFlushedAfterReportedOnce()
    {
        $socketConnection = $this->factory->create();

        $errorReporter = new InMemoryInputErrorReporter();

        $socketConnection
            ->withInputData('{"type": "command1"}' . "\n")
            ->withInputData('{"type": "invalid"}' . "\n")
            ->scheduleCommands($this->commandQueue)
            ->reportErrors(new InMemoryInputErrorReporter())
            ->withInputData('{"type": "invalid2"}' . "\n")
            ->scheduleCommands($this->commandQueue)
            ->reportErrors($errorReporter)
        ;

        $this->assertEquals(
            [
                ['{"type": "invalid2"}', new InvalidCommandInput()]
            ],
            $errorReporter->flushErrors()
        );
    }

    private function createCompositeFactoryWithStubbedCommands(string ...$types): CompositeCommandFactory
    {
        $factory = new CompositeCommandFactory();

        foreach ($types as $type) {
            $factory = $factory->withFactory(
                $type,
                ClonedCommandFactoryStub::create(
                    IdentifiedNullCommandStub::create($type)
                )
            );
        }


        return $factory;
    }
}
