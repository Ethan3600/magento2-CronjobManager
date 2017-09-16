<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Escaper;

class CronActions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'conjobmanager/manage/edit';
    const URL_PATH_DELETE = 'conjobmanager/manage_job/delete';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Escaper.
     *
     * @var Escaper
     */
    protected $escaper;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
    	Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['schedule_id'])) {
                	$jobCode = $this->escaper->escapeHtml($item['job_code']);
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['schedule_id']
                                ]
                            ),
                            'label' => __('Edit')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'schedule_id' => $item['schedule_id']
                                ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'job_code' => __('Delete %1', $jobCode),
                                'message' => __('Are you sure you wan\'t to delete a %1 record?', $jobCode),
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
