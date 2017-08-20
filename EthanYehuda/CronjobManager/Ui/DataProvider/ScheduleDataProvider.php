<?php

namespace EthanYehuda\CronjobManager\Ui\DataProvider;

use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * DataProvider for cron job edit form
 */
class ScheduleDataProvider extends AbstractDataProvider
{	
	/**
	 * @param string $name
	 * @param string $primaryFieldName
	 * @param string $requestFieldName
	 * @param CollectionFactory $collectionFactory
	 * @param array $meta
	 * @param array $data
	 */
	public function __construct(
			$name,
			$primaryFieldName,
			$requestFieldName,
			CollectionFactory $collectionFactory,
			array $meta = [],
			array $data = []
			) {
				parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
				$this->collection = $collectionFactory->create();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getData()
	{	
		return $this->data;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getMeta()
	{
		$meta = parent::getMeta();
		return $meta;
	}
}
