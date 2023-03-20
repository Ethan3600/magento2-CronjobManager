<?php

namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

use Magento\Framework\Exception\NotFoundException;
use Magento\Ui\Component\Listing\Columns\Column;

class ConfigActions extends Column
{
    protected const JOB_CODE = 'job_code';

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                if (!isset($item[self::JOB_CODE])) {
                    throw new NotFoundException(__(
                        'Missing Job Code: %1.',
                        $item[self::JOB_CODE]
                    ));
                }

                $item[$name]["view"] = [
                    "href" => $this->getContext()->getUrl(
                        "cronjobmanager/config/edit",
                        [
                            'job_code' => $item[self::JOB_CODE]
                        ]
                    ),
                    "label" => __("Edit"),
                ];
            }
        }

        return $dataSource;
    }
}
