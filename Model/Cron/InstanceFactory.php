<?php

/**
 * Model factory
 */

namespace EthanYehuda\CronjobManager\Model\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class InstanceFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
    ) {
    }

    /**
     * Create a new class
     *
     * This is a proxy for the object manager, and probably should not be used anywhere.
     *
     * @param string $className
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function create($className)
    {
        $cronInstance = $this->objectManager->create($className);
        if (!is_object($cronInstance)) {
            throw new LocalizedException(
                __('%1 doesn\'t exist in the system', $className)
                );
        }

        return $cronInstance;
    }
}
