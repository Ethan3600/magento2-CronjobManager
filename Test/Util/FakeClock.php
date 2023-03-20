<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Test\Util;

use EthanYehuda\CronjobManager\Model\ClockInterface;

class FakeClock implements ClockInterface
{
    public function __construct(
        private int $timestamp = 0
    ) {
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function advance(string $expression): void
    {
        $this->timestamp = strtotime("+$expression", $this->timestamp);
    }

    public function now(): int
    {
        return $this->timestamp;
    }
}
