<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

interface Clock
{
    public function now(): int;
}
