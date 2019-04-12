<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class InMemoryInputErrorReporterTest extends TestCase
{
    /** @var InMemoryInputErrorReporter */
    private $errorReporter;

    protected function setUp(): void
    {
        $this->errorReporter = InMemoryInputErrorReporter::create();
    }

    /** @test */
    public function recordsAllReportedErrors()
    {
        $this->errorReporter->reportError('{some data}', new InvalidJsonStructure());
        $this->errorReporter->reportError('{"type":"wrong"}', new InvalidCommandInput());
        $this->assertReportedErrors(
            [
                ['{some data}', new InvalidJsonStructure()],
                ['{"type":"wrong"}', new InvalidCommandInput()]
            ]
        );
    }

    /** @test */
    public function flushesErrorsAfterRetrieval()
    {
        $this->errorReporter->reportError('{some data}', new InvalidJsonStructure());
        $this->errorReporter->reportError('{"type":"wrong"}', new InvalidCommandInput());
        $this->errorReporter->flushErrors();

        $this->assertReportedErrors(
            []
        );
    }

    private function assertReportedErrors(array $expectedPairs)
    {
        $actualErrors = $this->errorReporter->flushErrors();
        $this->assertEquals($expectedPairs, $actualErrors, 'Number of reported errors does not match');
    }
}
