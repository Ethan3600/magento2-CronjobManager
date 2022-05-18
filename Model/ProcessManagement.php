<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

class ProcessManagement
{
    protected const SIGKILL = 9;

    public function isPidAlive(int $pid): bool
    {
        return \posix_kill($pid, 0);
    }

    public function killPid(int $pid, string $hostname): bool
    {
        if ($hostname !== \gethostname()) {
            return false;
        }
        if (!$this->isPidAlive($pid)) {
            return false;
        }
        //TODO first try to send SIGINT, wait up to X seconds, then send SIGKILL if process still running
        $killed = \posix_kill($pid, self::SIGKILL);
        if ($killed && !$this->isPidAlive($pid)) {
            \sleep(5);
            if ($this->isPidAlive($pid)) {
                return false;
            }
        }
        return $killed;
    }
}
