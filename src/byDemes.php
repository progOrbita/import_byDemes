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

    /**
     * read csv´s data and save on $this->data
     * 
     * @param string $nameCsv
     * @param int $lang
     */
    private function readCSVs(string $nameCsv)
    {
        $csv = new CsvImporter(
            $this->header,
            $this->csvLink . $nameCsv,
            ',',
            '"'
        );

        $data = $csv->read($this->key);
        $this->lastError = $csv->getLastError();
        return $data;
    }

    /**
     * get multi language data 
     * 
     * @return array
     */
    public function getMultiLanguageData()
    {
        $res = [];
        foreach ($this->nameCSVs as $key => $value) {
            $this->data[$key] = $this->readCSVs($value);
            if (empty($this->data[$key])) {
                //todo check errors
                $this->lastError = "Error con la lectura del csv " . $value . ":" . $this->lastError;
                return [];
            }

            foreach ($this->data[$key] as $id_supp => $element) {
                $this->data[$key][$id_supp]['wholesale_price'] = round((float) $this->parseFloat($this->data[$key][$id_supp]['wholesale_price']), 2);
                $this->data[$key][$id_supp]['quantity'] = (int) $this->data[$key][$id_supp]['quantity'];
                $this->data[$key][$id_supp]['active'] =  ($this->data[$key][$id_supp]['active'] == 'True');
                $this->data[$key][$id_supp]['depth'] = round((float) $this->parseFloat(floatval($this->data[$key][$id_supp]['depth'])), 2);
                $this->data[$key][$id_supp]['width'] = round((float) $this->parseFloat(floatval($this->data[$key][$id_supp]['width'])), 2);
                $this->data[$key][$id_supp]['height'] = round((float) $this->parseFloat(floatval($this->data[$key][$id_supp]['height'])), 2);
                $this->data[$key][$id_supp]['volume'] = round((float) $this->parseFloat(floatval($this->data[$key][$id_supp]['volume'])), 2);
                $this->data[$key][$id_supp]['weight'] = round((float) $this->parseFloat(floatval($this->data[$key][$id_supp]['weight'])), 2);
                foreach ($this->fieldsMultilingual as $field) {
                    if ($this->data[$key][$id_supp][$field]) {
                        $this->data[$key][$id_supp][$field] = [$key => $this->data[$key][$id_supp][(string)$field]];
                    }
                }
            }

            $res = array_replace_recursive($res, $this->data[$key]);
        }
        dump($res);
        return $res;
    }

}
