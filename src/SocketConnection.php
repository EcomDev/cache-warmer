<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class SocketConnection
{
    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /** @var string */
    private $input;

    /** @var array */
    private $errors = [];

    public function __construct(CommandFactory $commandFactory)
    {
        $this->commandFactory = $commandFactory;
        $this->input = '';
    }

    public function withInputData(string $data): self
    {
        $connection = clone $this;
        $connection->input .= $data;
        return $connection;
    }

    public function scheduleCommands(CommandQueue $commandQueue): self
    {
        while (($jsonLinePosition = $this->findNextJsonLine()) !== false) {
            $jsonLine = $this->fetchJsonLineFromInput($jsonLinePosition);

            if ($jsonLine === '') {
                continue;
            }


            try {
                $jsonData = $this->parseJsonString($jsonLine);

                $commandQueue->add($this->commandFactory->createFromInput($jsonData));
            } catch (\Throwable $exception) {
                $this->errors[] = [$jsonLine, $exception];
            }
        }

        return $this;
    }

    private function fetchJsonLineFromInput(int $nextJsonLinePosition): string
    {
        $jsonString = \substr($this->input, 0, $nextJsonLinePosition);
        $this->input = \substr($this->input, $nextJsonLinePosition + 1);

        return $jsonString;
    }

    /**
     *
     * @return bool|int
     */
    private function findNextJsonLine()
    {
        return \strpos($this->input, "\n");
    }

    public function reportErrors(InputErrorReporter $errorReporter): self
    {
        foreach ($this->errors as $errorData) {
            $errorReporter->reportError(...$errorData);
        }

        $connection = clone $this;
        $connection->errors = [];

        return $connection;
    }

    private function parseJsonString($jsonLine): array
    {
        $jsonData = \json_decode($jsonLine, true);

        if ($jsonData === null) {
            throw new InvalidJsonStructure();
        }

        return $jsonData;
    }
}
