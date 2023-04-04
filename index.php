<?php

require_once '../../config/config.inc.php';
require_once __DIR__ . '/src/byDemes.php';
require_once __DIR__ . '/src/byDemesManufacturer.php';
require_once __DIR__ . '/src/byDemesCategories.php';
require_once __DIR__ . '/src/actions.php';
require_once(_PS_CORE_DIR_ . '/import/files/src/Table.php');


$byDemes = new ByDemes(
    __DIR__ . "/data/",
    [
        0 => ["id", "referencia", "Model", "Brand", "Stock", "activo", "PVP", "UserPrice", "Description", "Short description", "Title", "Category", "Family", "SubFamily", "Compatible products", "imageURL", "EAN", "length", "width", "height", "volume", "weight", "Product URL"],
        1 => ["supplier_reference", "reference", "model", "manufacturer_name", "quantity", "active", "wholesale_price", "price", "description", "description_short", "name", "category", "family", "subfamily", "compatible products", "imageURL", "ean13", "depth", "width", "height", "volume", "weight", "productURL"]
    ],
    [1 => "bddProducts_en.csv", 2 => "bddProducts_fr.csv", 3 => "bddProducts_es.csv", 4 => "bddProducts_pt.csv"],
    ['name', 'description', 'description_short', 'category', 'family', 'subfamily']
);

?>

