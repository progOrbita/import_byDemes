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

    /**
     * get $lastError
     * 
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * function to build breadcrums
     * 
     * @param array $steps
     * 
     * @return string
     */
    public function getBreadCrums(array $steps): string
    {
        return implode('->', array_filter(array_map('trim', $steps)));
    }

    /**
     * function to get id_category_default or create it
     * 
     * @param string $breadcrum
     * 
     * @return int 
     */
    public function get(string $breadcrum): int
    {
        if (empty($breadcrum)) {
            return 0;
        }

        if (!isset($this->data[$breadcrum])) {
            $this->data[$breadcrum] = 0;
        }

        return (int) $this->data[$breadcrum];
    }

    /**
     * function to update json
     * 
     * @return bool
     */
    public function save(): bool
    {
        if ($this->originalData == $this->data) {
            return true;
        }

        $json = new JsonImporter($this->name);
        if (!$json->save($this->data, $this->name)) {
            $this->lastError = $json->getLastError();
            return false;
        }

        return true;
    }
}
