<?php

require_once(_PS_CORE_DIR_ . '/import/arbol-categorias/src/Categories.php');
require_once(_PS_CORE_DIR_ . '/config/config.inc.php');

class Actions extends AdminImportControllerCore
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
        $product->show_price = false;
        if (!$product->save()) {
            return 'Error al descatalogar';
        }

        return 'Descatalogado correctamente';
    }

    /**
     * update ps product data 
     * 
     * @param int $idProduct
     * @param array $productData
     * 
     * @return string
     */
    public static function update(int $idProduct, array $productData, bool $write = false): string
    {
        global $cat;
        $changes = false;
        $product = new Product($idProduct);
        $product->loadStockData();
        if ($productData['quantity'] != $product->quantity) {
            $changes[] = 'quantity (' . $product->quantity . '->' . $productData['quantity'] . ')';
            $write && StockAvailable::setQuantity($idProduct, 0, $productData['quantity']);
            $product->quantity = $productData['quantity'];
        }


        if ($productData['price'] < 0.01 || self::isFloatEqual($productData['price'], $product->price)) {
            $productData['price'] = $product->price;
        }

        if ($productData['wholesale_price'] < 0.01 || self::isFloatEqual($productData['wholesale_price'], $product->wholesale_price)) {
            $productData['wholesale_price'] = $product->wholesale_price;
        }

        if ($productData['id_supplier'] > 0 && $productData['supplier_reference']) {
            $product->addSupplierReference($productData['id_supplier'], 0, $productData['supplier_reference']);
        }

        foreach ($productData as $key => $value) {
            if (!isset($product->$key) || $key == 'category') {
                continue;
            }

            if (!empty($productData[$key]) && $productData[$key] != $product->{$key}) {
                if (is_array($productData[$key])) {
                    $changes[] = $key . ' (array , array)';
                    $product->{$key} = $productData[$key];
                    continue;
                }

                $changes[] = $key . ' (' . $product->{$key} . '->' . $productData[$key] . ')';
                $product->{$key} = $productData[$key];
            }
        }

        if ($product->id_category_default == 10) {
            if ($productData['id_category_default'] < 1) {
                return 'No se ha podido recatalogar por problemas en la relacion de las categorias';
            }

            $product->available_for_order = true;
            $product->visibility = 'both';
            $product->id_category_default = $productData['id_category_default'];
            $product->show_price = true;
            $product->updateCategories($cat->getCacheCategories($productData['id_category_default']));
        }

        if (!$changes) {
            return 'Sin cambios';
        }

        if (!$write) {
            return 'Datos a actualizar: ' . implode(',', $changes);
        }

        if (!$product->update()) {
            return 'Error con la actualizacion';
        }

        return 'Actualizado: ' . implode(',', $changes);
    }

    /**
     * function to create product
     * 
     * @param array $productData
     * @param bool $write
     * 
     * @return string
     */
    public static function create(array $productData, bool $write = false): string
    {
        global $cat;
        if ($productData['id_category_default'] < 1) {
            return 'Error en la creación, categoria no relacionada: ' . $productData['breadcrumb'];
        }

        if ($productData['price'] < 0.01) {
            return 'Error en la creación, precio inválido';
        }

        if ($productData['wholesale_price'] < 0.01) {
            return 'Error en la creación, precio de compra inválido';
        }

        if ($productData['quantity'] < 1) {
            return 'Error en la creación, cantidad de producto inválida';
        }

        if (!$write) {
            return 'Producto a crear';
        }

        $product = new Product();
        foreach ($productData as $key => $value) {
            if (!property_exists(new Product, $key) || $key == 'category') {
                continue;
            }

            if (!empty($productData[$key])) {
                if (is_array($productData[$key])) {
                    $product->{$key} = $productData[$key];
                    continue;
                }

                $product->{$key} = $productData[$key];
            }
        }

        // accesorios ps_accesory id_product->id_product_accesory Product::changeAccessoriesForProduct()
        $product->id_tax_rules_group = 1;
        if (!$product->save()) {
            return 'Error creacion del producto';
        }

        StockAvailable::setQuantity($product->id, 0, $productData['quantity']);
        $product->updateCategories($cat->getCacheCategories($productData['id_category_default']));
        $product->addSupplierReference($productData['id_supplier'], 0, $productData['supplier_reference']);
        if (!empty($productData['imageURL'])) {
            if (!self::createImg($product->id, $productData['imageURL'], $productData['name'])) {
                return 'Producto creado, pero error con el creado de imagenes';
            }
        }

        return 'Producto creado correctamente';
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

    /**
     * create img product
     * 
     * @param int $idProduct
     * @param string $url
     * @param array $legend
     * 
     * @return bool
     */
    public static function createImg(int $idProduct, string $url, array $legend)
    {
        $image = new Image();
        $image->id_product = (int) $idProduct;
        $image->position = Image::getHighestPosition($idProduct) + 1;
        $image->legend = $legend;
        if (!Image::getCover($image->id_product)) {
            $image->cover = 1;
        } else {
            $image->cover = 0;
        }

        if (($image->validateFieldsLang(false, true)) !== true) {
            return false;
        }

        if (!$image->add()) {
            return false;
        }

        return (parent::copyImg($idProduct, $image->id, $url));
    }
}
