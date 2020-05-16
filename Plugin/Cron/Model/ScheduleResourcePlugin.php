<?php
declare(strict_types=1);

namespace EthanYehuda\CronjobManager\Plugin\Cron\Model;

use EthanYehuda\CronjobManager\Model\ErrorNotification;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ResourceModel;

class ScheduleResourcePlugin
{
    /**
     * @var ErrorNotification
     */
    private $errorNotification;

    public function __construct(ErrorNotification $errorNotification)
    {
        $this->errorNotification = $errorNotification;
    }

    /**
     * Email notification if status has been set to ERROR
     */
    public function afterSave(
        ResourceModel\Schedule $subject,
        ResourceModel\Schedule $result,
        Schedule $object
    ) {
        if ($object->getOrigData('status') !== $object->getStatus()
            && $object->getStatus() === Schedule::STATUS_ERROR
        ) {
            $this->errorNotification->sendFor($object);
        }
        return $result;
    }

    /**
     * Replace method to update pid and hostname columns together with status column
     *
     * @param \Magento\Cron\Model\ResourceModel\Schedule $subject
     * @param callable $proceed
     * @param $scheduleId
     * @param $newStatus
     * @param $currentStatus
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    public function aroundTrySetJobUniqueStatusAtomic(
        \Magento\Cron\Model\ResourceModel\Schedule $subject,
        callable $proceed,
        $scheduleId,
        $newStatus,
        $currentStatus
    ) {
        $connection = $subject->getConnection();

        // this condition added to avoid cron jobs locking after incorrect termination of running job
        $match = $connection->quoteInto(
            'existing.job_code = current.job_code ' .
            'AND (existing.executed_at > UTC_TIMESTAMP() - INTERVAL 1 DAY OR existing.executed_at IS NULL) ' .
            'AND existing.status = ?',
            $newStatus
        );

        $selectIfUnlocked = $connection->select()
            ->joinLeft(
                ['existing' => $subject->getTable('cron_schedule')],
                $match,
                [
                    'hostname' => new \Zend_Db_Expr($connection->quote(\gethostname())),
                    'status' => new \Zend_Db_Expr($connection->quote($newStatus)),
                    'pid' => new \Zend_Db_Expr($connection->quote(\getmypid()))
                ]
            )
            ->where('current.schedule_id = ?', $scheduleId)
            ->where('current.status = ?', $currentStatus)
            ->where('existing.schedule_id IS NULL');

        $update = $connection->updateFromSelect($selectIfUnlocked, ['current' => $subject->getTable('cron_schedule')]);
        $result = $connection->query($update)->rowCount();

        if ($result == 1) {
            return true;
        }
        return false;
    }
}
