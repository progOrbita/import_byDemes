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
        $categories = new ByDemesCategories();
        foreach ($this->nameCSVs as $key => $value) {
            $this->data[$key] = $this->readCSVs($value);
            if (empty($this->data[$key])) {
                $this->lastError = "Error con la lectura del csv " . $value . ":" . $this->lastError;
                return [];
            }

            foreach ($this->data[$key] as $id_supp => $element) {
                $this->data[$key][$id_supp]['id_supplier'] = $this->id_supp;
                $this->data[$key][$id_supp]['supplier_name'] = $this->supp_name;
                $name = trim($this->data[$key][$id_supp]['name']);
                $short_description = trim($this->data[$key][$id_supp]['description_short']);
                $description = trim($this->data[$key][$id_supp]['description']);
                $nameLink = strip_tags($this->data[$key][$id_supp]['manufacturer_name'] . ' ' . $this->data[$key][$id_supp]['reference'] . ' ' . $name);
                $this->data[$key][$id_supp]['link_rewrite'] = [$key => $this->buildLinkRewrite($nameLink)];
                $this->data[$key][$id_supp]['name'] = [$key => $this->addaptMaxLength(strip_tags($this->data[$key][$id_supp]['manufacturer_name'] . ' ' . $this->data[$key][$id_supp]['reference'] . ' ' . $name), 128)];
                $this->data[$key][$id_supp]['description_short'] = [$key => $this->addaptMaxLength($this->data[$key][$id_supp]['name'][$key] . ' ' . strip_tags($short_description), 800)];
                $this->data[$key][$id_supp]['description'] = [$key => '<p>' . $this->data[$key][$id_supp]['manufacturer_name'] . ' ' . $this->data[$key][$id_supp]['reference'] . ' ' . $name . '</p><div>' . $short_description . '</div><br /><div>' . $this->clearDescription($description) . '</div>'];
                $this->data[$key][$id_supp]['description_short'][$key] = ObjectModel::formatValue($this->data[$key][$id_supp]['description_short'][$key], 6, false, true, false);
                if (!empty($this->data[$key][$id_supp]['model']) && $this->data[$key][$id_supp]['model'] != $this->data[$key][$id_supp]['reference']) {
                    $this->data[$key][$id_supp]['description'][$key] .= '<p>' . $this->data[$key][$id_supp]['model'] . '</p>';
                }

                $this->data[$key][$id_supp]['description'][$key] = ObjectModel::formatValue($this->data[$key][$id_supp]['description'][$key], 6, false, true, false);
                foreach ($this->fieldsMultilingual as $field) {
                    if ($this->data[$key][$id_supp][$field] && !is_array($this->data[$key][$id_supp][$field])) {
                        $this->data[$key][$id_supp][$field] = [$key => $this->data[$key][$id_supp][(string)$field]];
                    }
                }
            }

            $res = array_replace_recursive($res, $this->data[$key]);
        }

        foreach ($res as $key => $field) {
            $res[$key]['wholesale_price'] = round((float) $this->parseFloat($res[$key]['wholesale_price']), 2);
            $res[$key]['price'] = round((float) $this->parseFloat($res[$key]['price']), 2);
            $res[$key]['quantity'] = $this->stocks[strtolower(trim($res[$key]['quantity']))] ?? 0;
            $res[$key]['active'] = ($res[$key]['active'] == 'True');
            $res[$key]['on_sale'] = true;
            $res[$key]['show_price'] = true;
            $res[$key]['visibility'] = 'both';
            $res[$key]['depth'] = round((float) $this->parseFloat(floatval($res[$key]['depth'])), 2);
            $res[$key]['width'] = round((float) $this->parseFloat(floatval($res[$key]['width'])), 2);
            $res[$key]['height'] = round((float) $this->parseFloat(floatval($res[$key]['height'])), 2);
            $res[$key]['volume'] = round((float) $this->parseFloat(floatval($res[$key]['volume'])), 2);
            $res[$key]['weight'] = round((float) $this->parseFloat(floatval($res[$key]['weight'])), 2);
            $res[$key]['id_manufacturer'] = ByDemesManufacturer::get($res[$key]['manufacturer_name']);
            $res[$key]['category'] = $this->checkLangugeField($res[$key]['category']);
            $res[$key]['family'] = $this->checkLangugeField($res[$key]['family']);
            $res[$key]['subfamily'] = $this->checkLangugeField($res[$key]['subfamily']);
            $res[$key]['breadcrumb'] = $categories->getBreadCrums([$res[$key]['category'], $res[$key]['family'], $res[$key]['subfamily']]);
            $res[$key]['id_category_default'] = $categories->get($res[$key]['breadcrumb']);
            if (!isset($this->suppliers[strtolower(trim($res[$key]['manufacturer_name']))])) {
                continue;
            }

            call_user_func_array([$this, $this->suppliers[strtolower(trim($res[$key]['manufacturer_name']))]], [&$res[$key]]);
        }

        $categories->save();
        return $res;
    }

    /**
     * function to addapt text to max lenght of field
     * 
     * @param string $text
     * @param int $maxLength
     * 
     * @return string
     */
    private function addaptMaxLength(string $text, int $maxLength): string
    {
        if ($text < $maxLength) {
            return $text;
        }

        $text = substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * function to build product link rewrite
     * 
     * @param string $name
     * 
     * @return string
     */
    private function buildLinkRewrite(string $name): string
    {
        $text = preg_replace('/\W/U', '-', $name);
        $text = preg_replace('/-{2,}/U', '-', $text);
        return trim(strtolower($text), '-');
    }

    /**
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
