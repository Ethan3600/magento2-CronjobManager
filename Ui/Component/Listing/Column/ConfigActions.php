<?php
namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

use Magento\Framework\Exception\NotFoundException;

class ConfigActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    const JOB_CODE = 'job_code';
    
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                if(!isset($item[self::JOB_CODE]))
                {
                    throw new NotFoundException(__(
                        'Missing Job Code: %1.', 
                        $item[self::JOB_CODE]
                        )
                    );
                }
                $item[$name]["view"] = [
                    "href"=>$this->getContext()->getUrl(
                        "cronjobmanager/config/edit", $item),
                    "label"=>__("Edit")
                ];
            }
        }
        return $dataSource;
    }  
}
