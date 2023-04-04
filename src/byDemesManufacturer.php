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

}