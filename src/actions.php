<?php

require_once(_PS_CORE_DIR_ . '/import/arbol-categorias/src/Categories.php');
require_once(_PS_CORE_DIR_ . '/config/config.inc.php');

class Actions
{
    /**
     * make some product discontinued
     * 
     * @param int $idProduct
     * 
     * @return string
     */
    public static function discontinue(int $idProduct, bool $write = false): string
    {
        $product = new Product($idProduct);
        if ($product->id_category_default <= 0) {
            return 'El producto no existe';
        }
        if ($product->id_category_default == 10) {
            return 'El producto ya estaba descatalogado';
        }

        if (!$write) {
            return 'Producto a descatalogar';
        }

        $product->available_for_order = false;
        $product->visibility = 'none';
        $product->quantity = 0;
        StockAvailable::setQuantity($idProduct, 0, 0);
        $product->id_category_default = 10;
        $product->updateCategories([2, 10]);
        if (!$product->save()) {
            return 'Error al descatalogar';
        }

        return 'Descatalogado correctamente';
    }


    /**
     * compare if 2 float are really differents
     * 
     * @param float $n1
     * @param float $n2
     * @param float $diff
     * 
     * @return bool
     */
    public static function isFloatEqual(float $n1, float $n2, float $diff = 0.01): bool
    {
        return abs($n1 - $n2) < $diff;
    }
}
