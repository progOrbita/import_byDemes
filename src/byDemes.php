<?php
require_once(_PS_CORE_DIR_ . '/import/files/src/CsvImporter.php');
require_once(_PS_CORE_DIR_ . '/import/files/src/JsonImporter.php');
require_once(_PS_CORE_DIR_ . '/import/byDemes/src/byDemesManufacturer.php');
require_once(_PS_CORE_DIR_ . '/import/byDemes/src/byDemesCategories.php');
require_once(_PS_CORE_DIR_ . '/config/config.inc.php');

class ByDemes
{
    private $lang = [];
    private $csvRoute = "";
    private $data = [];
    private $header = [];
    private $nameCSVs = [];
    private $key = "";
    private $fieldsMultilingual = [];
    private $lastError = "";
    private $id_supp = 1;
    private $supp_name = 'ByDemes';
    private $stocks = [
        'low' => 10,
        'medium' => 50,
        'high' => 100
    ];
    private $suppliers = [
        'airspace' => 'processAirspace',
        'crow' => 'processCrow',
    ];



    public function __construct(string $csvRoute, array $header, array $nameCSVs, array $fieldsMultilingual, string $key = "supplier_reference")
    {
        $this->csvRoute = $csvRoute;
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
     * 
     * @return array
     */
    private function readCSVs(string $nameCsv): array
    {
        $csv = new CsvImporter(
            $this->header,
            $this->csvRoute . $nameCsv,
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
    public function getMultiLanguageData(): array
    {
        if (empty($this->nameCSVs)) {
            $this->lastError = 'No existen datos a procesar';
            return [];
        }

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

        return $res;
    }

    /**
     * check if field has the language value that we want if it doesnt have value we set it 
     * 
     * @param array $arr
     * @param int $idLang
     * @param mixed $default
     * 
     * @return mixed
     */
    private function checkLangugeField(array $arr, int $idLang = 3, $default = '')
    {
        if (!isset($arr[$idLang])) {
            $arr[$idLang] = $default;
        }

        return $arr[$idLang];
    }

    /**
     * proccess data for Airspace supplier
     * 
     * @param array $arr
     * 
     * @return void
     */
    public function processAirspace(array &$arr)
    {
        $arr['id_supplier'] = 2;
        $arr['supplier_name'] = 'byDemesAirspace';
        $arr['price'] = round((float)$arr['wholesale_price'] * 2.3, 2);
    }

    /**
     * proccess data for Crow supplier
     * 
     * @param array $arr
     * 
     * @return void
     */
    public function processCrow(array &$arr)
    {
        $arr['id_supplier'] = 3;
        $arr['supplier_name'] = 'byDemesCrow';
        $arr['price'] = round((float) $arr['price'] + 25, 2);
    }

    /**
     * lastError getter 
     * 
     * @return string
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * parse float 
     * 
     * @param string $number
     * @param string $thousandSeparator
     * @param string $decimalSparator
     * 
     * @return float
     */
    public static function parseFloat(string $number, string $thousandSeparator = '.', string $decimalSparator = ','): float
    {
        return (float) str_replace([$thousandSeparator, $decimalSparator], ['', '.'],  trim($number));
    }

    /**
     * generate csv cache if json doesnt exist and return csv data 
     * @param bool $reload
     * 
     * @return array
     */
    public function getData(bool $reload = false): array
    {
        $fileName = _PS_CORE_DIR_ . '/import/byDemes/data/byDemes_' . Date('d-m-Y') . '.json';
        $json = new JsonImporter($fileName);
        if ($json->validateFile() && !$reload) {
            $data = $json->read();
            if (empty($data)) {
                echo $json->getLastError();
            }

            return $data;
        }

        $data = $this->getMultiLanguageData();
        if (empty($data)) {
            echo $this->lastError;
            return $data;
        }

        if (!$json->save($data, $fileName)) {
            echo $json->getLastError();
        }

        return $data;
    }
}
