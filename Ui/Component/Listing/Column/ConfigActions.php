<?php
namespace EthanYehuda\CronjobManager\Ui\Component\Listing\Column;

class ConfigActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    const JOB_CODE = 'job_code';
    
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as & $item) {
                $name = $this->getData("name");
                $code = "";
                if(isset($item[self::JOB_CODE]))
                {
                    $code = $item[self::JOB_CODE];
                }
                $item[$name]["view"] = [
                    "href"=>$this->getContext()->getUrl(
                        "cronjobmanager/config_job/edit",["code"=>$code]),
                    "label"=>__("Edit")
                ];
            }
        }

        return $dataSource;
    }    
    
}
