<?php

/**
 * Model factory
 */
namespace EthanYehuda\CronjobManager\Model\Cron;

use Magento\Framework\ObjectManagerInterface;

class InstanceFactory
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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
