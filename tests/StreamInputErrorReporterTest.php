<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactiveSocket\FakeStreamClient;
use PHPUnit\Framework\TestCase;

class StreamInputErrorReporterTest extends TestCase
{
    const BAD_DATA_INPUT_JSON = '{some:bad:data}';

    const BAD_DATA_ERROR_JSON = '{"type":"error",'
                              . '"error":"EcomDev\\\\CacheWarmer\\\\InvalidJsonStructure",'
                              . '"data":"{some:bad:data}"}' . "\n";

    const BAD_COMMAND_ERROR_JSON = '{"type":"error",'
                                 . '"error":"EcomDev\\\\CacheWarmer\\\\InvalidCommandInput",'
                                 . '"data":"{\\"type\\":\\"bad\\"}"}' . "\n";
    const BAD_COMMAND_INPUT_JSON = '{"type":"bad"}';


    /** @var StreamInputErrorReporter */
    private $errorReporter;

    protected function setUp(): void
    {
        $this->errorReporter = new StreamInputErrorReporter();
    }

    /** @test */
    public function reportedErrorIsWrittenIntoSocketClientAsJson()
    {
        $this->errorReporter->reportError(self::BAD_DATA_INPUT_JSON, new InvalidJsonStructure());

        $streamClient = FakeStreamClient::create(100);
        $this->errorReporter->flushErrorsIntoStream($streamClient);

        $this->assertEquals(
            self::BAD_DATA_ERROR_JSON,
            $streamClient->read()
        );
    }

    /** @test */
    public function reportsMultipleErrorsAsSeparatePackets()
    {
        $this->errorReporter->reportError(self::BAD_DATA_INPUT_JSON, new InvalidJsonStructure());
        $this->errorReporter->reportError(self::BAD_COMMAND_INPUT_JSON, new InvalidCommandInput());

        $streamClient = FakeStreamClient::create(200);
        $this->errorReporter->flushErrorsIntoStream($streamClient);

        $dataInStream = [];
        $dataInStream[] = $streamClient->read();
        $dataInStream[] = $streamClient->read();

        $expectedErrors = [
            self::BAD_DATA_ERROR_JSON,
            self::BAD_COMMAND_ERROR_JSON
        ];

        $this->assertEquals(
            $expectedErrors,
            $dataInStream
        );
    }

    /** @test */
    public function stopsWritingToStreamAfterWriteLimitIsReached()
    {
        $this->errorReporter->reportError(self::BAD_DATA_INPUT_JSON, new InvalidJsonStructure());
        $this->errorReporter->reportError(self::BAD_COMMAND_INPUT_JSON, new InvalidCommandInput());

        $streamClient = FakeStreamClient::create(100);
        $this->errorReporter->flushErrorsIntoStream($streamClient);

        $dataInStream = [];
        $dataInStream[] = $streamClient->read();
        $dataInStream[] = $streamClient->read();

        $expectedErrors = [
            self::BAD_DATA_ERROR_JSON,
            ''
        ];

        $this->assertEquals(
            $expectedErrors,
            $dataInStream
        );
    }

    /** @test */
    public function errorsAreWrittenIntoStreamOnlyOnce()
    {
        $this->errorReporter->reportError(self::BAD_DATA_INPUT_JSON, new InvalidJsonStructure());
        $this->errorReporter->reportError(self::BAD_COMMAND_INPUT_JSON, new InvalidCommandInput());

        $streamClient = FakeStreamClient::create(200);

        $this->errorReporter->flushErrorsIntoStream($streamClient);

        $streamClient->read();
        $streamClient->read();

        $this->errorReporter->flushErrorsIntoStream($streamClient);

        $this->assertEquals(
            '',
            $streamClient->read()
        );
    }

    /** @test */
    public function resumesWritingDataIntoStreamAfterItAllowsIt()
    {
        $this->errorReporter->reportError(self::BAD_DATA_INPUT_JSON, new InvalidJsonStructure());
        $this->errorReporter->reportError(self::BAD_COMMAND_INPUT_JSON, new InvalidCommandInput());

        $streamClient = FakeStreamClient::create(100);
        $this->errorReporter->flushErrorsIntoStream($streamClient);
        $streamClient->read();
        $this->errorReporter->flushErrorsIntoStream($streamClient);


        $this->assertEquals(
            self::BAD_COMMAND_ERROR_JSON,
            $streamClient->read()
        );
    }
}
