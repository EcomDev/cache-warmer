<?php
/**
 * Created by PhpStorm.
 * User: ivanc
 * Date: 13/09/2018
 * Time: 14:26
 */

class BackgroundJob
{
    private $processId;

    public function __construct(int $processId)
    {
        $this->processId = $processId;
    }

    public static function create(callable $job): BackgroundJob
    {
        $forkedProcessId = \pcntl_fork();

        if ($forkedProcessId === 0) {
            $job();
            exit(0);
        }

        return new self($forkedProcessId);
    }

    public function sendSignal(int $signal)
    {
        \posix_kill($this->processId, $signal);
    }

    public function wait(): int
    {
        $exitStatus = 0;
        \pcntl_waitpid($this->processId, $exitStatus);
        return $exitStatus;
    }
}