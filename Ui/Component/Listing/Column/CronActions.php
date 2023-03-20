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
    protected const URL_PATH_EDIT = 'cronjobmanager/manage/edit';
    protected const URL_PATH_DELETE = 'cronjobmanager/manage_job/delete';
    protected const URL_PATH_DISPATCH = 'cronjobmanager/manage_job/dispatch';
    protected const URL_PATH_KILL = 'cronjobmanager/manage_job/kill';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
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
                                    'id' => $item['schedule_id'],
                                ]
                            ),
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'id' => $item['schedule_id'],
                                    'job_code' => $item['job_code'],
                                ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'job_code' => __('Delete %1', $jobCode),
                                'message' => __('Are you sure you want to delete <b>%1</b>?', $jobCode),
                            ],
                        ],
                        'dispatch' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DISPATCH,
                                [
                                    'id' => $item['schedule_id'],
                                    'job_code' => $item['job_code'],
                                ]
                            ),
                            'label' => __('Schedule duplicate job'),
                            'confirm' => [
                                'job_code' => __('Schedule %1', $jobCode),
                                'message' => __(
                                    'Are you sure you want to <b>schedule %1</b> to run (again) now?'
                                    . ' This may be time consuming and resource intensive.',
                                    $jobCode
                                ),
                            ],
                        ],
                        'kill' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_KILL,
                                [
                                    'id' => $item['schedule_id'],
                                    'job_code' => $item['job_code'],
                                ]
                            ),
                            'label' => __('Kill'),
                            'confirm' => [
                                'job_code' => __('Kill'),
                                'message' => __(
                                    'Are you sure you want to <b>kill the process</b>?'
                                ),
                            ],
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
