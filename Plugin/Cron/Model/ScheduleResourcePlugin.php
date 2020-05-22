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
}
