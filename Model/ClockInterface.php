<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

interface ClockInterface
{
    /**
     * Return the current time as unix timestamp
     *
     * @return int
     */
    public function now(): int;
}
