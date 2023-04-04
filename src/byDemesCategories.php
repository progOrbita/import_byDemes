<?php
require_once(_PS_CORE_DIR_ . '/config/config.inc.php');
require_once(_PS_CORE_DIR_ . '/import/files/src/JsonImporter.php');

class ByDemesCategories
{
    private $data = [];
    private $name = _PS_CORE_DIR_ . '/import/byDemes/data/byDemesJsonCategories.json';
    private $lastError = '';
    private $originalData = [];

    public function __construct()
    {
        $json = new JsonImporter($this->name);
        $this->originalData = $json->read();
        $this->data = $this->originalData;
    }

}
