<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

class ProcessManagement
{
    public function isPidAlive(int $pid): bool
    {
        if (file_exists("/proc/" . intval($pid))) {
            return true;
        }

        return false;
    }
}
