<?php

/**
 * Model factory
 */
namespace EthanYehuda\CronjobManager\Model\Cron;

use Magento\Framework\ObjectManagerInterface;

class InstanceFactory
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
    ) {
    }

    public function create($className)
    {
        $cronInstance = $this->objectManager->create($className);
        if (!is_object($cronInstance)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 doesn\'t exist in the system', $className)
                );
        }

        return $cronInstance;
    }
}
