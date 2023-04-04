<?php
require_once(_PS_CORE_DIR_ . '/config/config.inc.php');

class ByDemesManufacturer
{
    /**
     * get manufacturer id if brand doesnt exist we create it 
     * 
     * @param string $name 
     * 
     * @return int
     */
    public static function get(string $name): int
    {
        if (empty($name)) {
            return 0;
        }

        $name = self::formatName($name);
        $id = Manufacturer::getIdByName($name);
        if ($id) {
            return $id;
        }

        return self::createManufacturer($name);
    }

    /**
     * format name example 'a Di DA s'-> 'Adidas'
     * 
     * @param string $name
     * 
     * @return string 
     */
    public static function formatName(string $name): string
    {
        return ucfirst(strtolower(trim($name)));
    }

    /**
     * create new manufacturer 
     * 
     * @param string $name 
     * 
     * @return int id_manufacturer
     */
    private static function createManufacturer($name): int
    {
        $manufacturer = new Manufacturer();
        $manufacturer->name = $name;
        if ($manufacturer->save()) {
            return $manufacturer->id;
        }

        return 0;
    }
}
