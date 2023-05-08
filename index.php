<?php

declare(strict_types=1);

require_once '../../config/config.inc.php';
require_once __DIR__ . '/src/byDemes.php';
require_once __DIR__ . '/src/byDemesManufacturer.php';
require_once __DIR__ . '/src/byDemesCategories.php';
require_once __DIR__ . '/src/Actions.php';
require_once(_PS_CORE_DIR_ . '/import/files/src/Table.php');
require_once(_PS_CORE_DIR_ . '/import/arbol-categorias/src/Categories.php');


$byDemes = new ByDemes(
    __DIR__ . "/data/",
    [
        0 => ["id", "referencia", "Model", "Brand", "Stock", "activo", "PVP", "UserPrice", "Description", "Short description", "Title", "Category", "Family", "SubFamily", "Compatible products", "imageURL", "EAN", "length", "width", "height", "volume", "weight", "Product URL"],
        1 => ["supplier_reference", "reference", "model", "manufacturer_name", "quantity", "active", "wholesale_price", "price", "description", "description_short", "name", "category", "family", "subfamily", "compatible products", "imageURL", "ean13", "depth", "width", "height", "volume", "weight", "productURL"]
    ],
    [1 => "bddProducts_en.csv", 2 => "bddProducts_fr.csv", 3 => "bddProducts_es.csv", 4 => "bddProducts_pt.csv"],
    ['name', 'description', 'description_short', 'category', 'family', 'subfamily']
);

$data = $byDemes->getData((bool)Tools::getValue('reload', false));
if (empty($data)) {
    echo $byDemes->getLastError();
    die;
}
$ps_data = Db::getInstance()->executeS('SELECT `id_product`, `supplier_reference` FROM `ps_product` WHERE `id_supplier` IN (1,2,3)');
if ($ps_data === false) {
    echo 'consulta erronea a la db';
    die;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js">
    </script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js">
    </script>
    <title>byDemes</title>
</head>

<body>
    <?php
    echo Table::makeTable($res, ['Refencia de proveedor', 'Resultado']);
    ?>
</body>

<script>
    $(document).ready(function() {
        $('#data').DataTable({
            paging: false
        });
    });
</script>

</html>