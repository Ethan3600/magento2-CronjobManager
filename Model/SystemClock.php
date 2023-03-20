<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

class SystemClock implements ClockInterface
{
    /**
     * @inheritDoc
     */
    public function now(): int
    {
        return \time();
    }
}
