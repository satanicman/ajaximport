<?php

class AjaxImportModel extends ObjectModel
{
    protected static $tables_list = array('product');
    public $counter = 0;
    protected $context;

    /**
     * Создаем полей, идентификаторов 1с, для дальнейшего обновления полей
     * @return bool
     */
    public static function addFields() {
        foreach(self::$tables_list as $table) {
            $fields = Db::getInstance()->executeS('SHOW FIELDS FROM `'._DB_PREFIX_.$table.'`');
            $exist = false;
            foreach ($fields as $field) {
                if($field['Field'] === 'id_oneC') {
                    $exist = true;
                }
            }

            if(!$exist) {
                Db::getInstance()->execute("ALTER TABLE  `"._DB_PREFIX_.$table."` ADD  `id_oneC` VARCHAR( 128 ) NULL;");
            }
        }

        return true;
    }

    /**
     * Удаление полей идентификаторов 1с
     * @return bool
     */
    public static function rmFields() {
        foreach(self::$tables_list as $table) {
            $fields = Db::getInstance()->executeS("SHOW FIELDS FROM `"._DB_PREFIX_.$table."`");
            $exist = false;
            foreach ($fields as $field) {
                if($field['Field'] === 'id_oneC') {
                    $exist = true;
                }
            }

            if($exist) {
                Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_.$table."` DROP `id_oneC`;");
            }
        }

        return true;
    }

    public function setImages($link, $product)
    {
        if (!empty($link) && $product->id) {
            $photo_isset = str_replace("\\", "/", _PS_ROOT_DIR_ . '/img/' . trim($link));
            if (file_exists($photo_isset)) {
                $image = new Image();
                $image->id_product = $product->id;
                if(!Image::getCover($product->id))
                    $image->cover = 1;
                $image->position = 0;
                $image->legend = array_fill_keys(Language::getIDs(), (string)$product->name);
                $image->save();
                $name = $image->getPathForCreation();
                copy($photo_isset, $name . '.' . $image->image_format);
                $types = ImageType::getImagesTypes('products');
                foreach ($types as $type)
                    ImageManager::resize($photo_isset, $name . '-' . $type['name'] . '.' . $image->image_format, $type['width'], $type['height'], $image->image_format);
            }
        }
        return true;
    }


    /**
     * Получить идентификатор значения свойства по названию
     * @param string $name
     * @param int $id_feature
     * @return bool|int
     */
    public function getFeatureValueByName($name, $id_feature) {
        if(!$name)
            return false;

        $sql = "SELECT MAX(fv.id_feature_value) 
                  FROM `"._DB_PREFIX_."feature_value_lang` fvl
                  INNER JOIN `"._DB_PREFIX_."feature_value` fv ON fv.`id_feature_value` = fvl.`id_feature_value` 
                  AND `custom` LIKE 0
                  AND fv.`id_feature` LIKE ".(int)$id_feature."
                  WHERE fvl.`value` LIKE '".(string)$name."' 
                  AND fvl.`id_lang` = ".$this->context->language->id;

        return (int)Db::getInstance()->getValue($sql);
    }


    /**
     * Получить идентификатор свойства по названию
     * @param string $name
     * @return bool|int
     */
    public function getFeatureByName($name) {
        if(!$name)
            return false;

        $sql = "SELECT MAX(id_feature) 
                  FROM `"._DB_PREFIX_."feature_lang` 
                  WHERE `name` LIKE '".(string)$name."' 
                  AND `id_lang` = ".(int)$this->context->language->id;

        return (int)Db::getInstance()->getValue($sql);
    }


    /**
     * Получить идентификатор товара по артикулу
     * @param string $reference
     * @return bool|int
     */
    public function getProductByReference($reference) {
        if(!$reference)
            return false;

        $sql = "SELECT id_product 
                  FROM `"._DB_PREFIX_."product` 
                  WHERE `reference` LIKE '".(string)$reference."'";

        return (int)Db::getInstance()->getValue($sql);
    }
}