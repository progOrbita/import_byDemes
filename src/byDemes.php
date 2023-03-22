<?php
require_once(_PS_CORE_DIR_ . '/import/files/src/CsvImporter.php');

class ByDemes
{
    private $lang = [];
    private $csvLink = "";
    private $data = [];
    private $header = [];
    private $nameCSVs = [];
    private $key = "";
    private $fieldsMultilingual = [];
    private $lastError = "";

    public function __construct(string $csvLink, array $header, array $nameCSVs, array $fieldsMultilingual, string $key = "supplier_reference")
    {
        $this->csvLink = $csvLink;
        $this->header = $header;
        $this->nameCSVs = $nameCSVs;
        $this->lang = array_keys($nameCSVs);
        $this->key = $key;
        $this->fieldsMultilingual = $fieldsMultilingual;
    }

}
