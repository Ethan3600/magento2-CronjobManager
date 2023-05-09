<?php

declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Model;

class ProcessManagement
{
    protected const SIGKILL = 9;

    /**
     * Return true if the given process is running
     *
     * @param int $pid
     *
     * @return bool
     */
    public function isPidAlive(int $pid): bool
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        return \posix_kill($pid, 0);
    }

    /**
     * Send a SIG_KILL signal to the specified process (if it is running on this host)
     *
     * @param int $pid
     * @param string $hostname
     *
     * @return bool
     */
    public function killPid(int $pid, string $hostname): bool
    {
        if ($hostname !== \gethostname()) {
            return false;
        }

        if (!$this->isPidAlive($pid)) {
            return false;
        }

        //TODO first try to send SIGINT, wait up to X seconds, then send SIGKILL if process still running

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $killed = \posix_kill($pid, self::SIGKILL);
        if ($killed && $this->isPidAlive($pid)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            \sleep(5);
            if ($this->isPidAlive($pid)) {
                return false;
            }
        }

        return $killed;
    }
}
