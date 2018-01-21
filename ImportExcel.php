<?php

class ImportExcel extends AjaxImportModel
{
    public $excelColumns = array(
        'omega' => array(
            'line' => 2,
            'fields' => array(
                'name' => 1,
                'reference' => 0,
                'features' => array(2),
                'price' => 4
            )
        ),
        'continental' => array(
            'line' => 9,
            'fields' => array(
                'name' => 4,
                'reference' => 1,
                'price' => 13
            )
        )
    );

    public function __construct()
    {
        global $context;
        $this->context = $context;
    }

    public function parseExcel($params) {
        $result = array();

        foreach ($params['values'] as $reference => $product) {
            $id_product = $this->getProductByReference($reference);
            $p = new Product($id_product, false, $this->context->language->id);

            if(!$id_product) {
                $categories = array();
                $p->name = (string)$product->name;
                $p->link_rewrite = Tools::link_rewrite((string)$product->name);
                $p->reference = (string)$product->reference;
                $categories[]['id'] = $p->id_category_default = Configuration::get('PS_HOME_CATEGORY');
            }
            $p->price = (float)$product->price;

            $message = $p->validateFieldsLang(false, true);
            if(is_string($message)) {
                $result[] = '<p class="c-red">'.$reference.' - '.$message.'</p>';
            }

            if($id_product)
                $p->update();
            else {
                $p->add();
                $p->setWsCategories($categories);
                StockAvailable::setQuantity($p->id, 0, 1000);

                if($product->features) {
                    $features = array();
                    foreach ($product->features as $feature => $value) {
                        $id_feature = $this->getFeatureByName($feature);
                        $f = new Feature($id_feature, $this->context->language->id);
                        if(!$id_feature) {
                            $f->name = $feature;
                            $f->add();
                        }

                        $id_feature_value = $this->getFeatureValueByName($value, $f->id_feature);
                        $fv = new FeatureValue($id_feature_value, $this->context->language->id);
                        if(!$id_feature_value) {
                            $fv->value = $value;
                            $fv->id_feature = $f->id;
                            $fv->add();
                        }

                        $features[] = array(
                            'id' => $f->id,
                            'id_feature_value' => $fv->id
                        );
                    }

                    $p->setWsProductFeatures($features);
                }
            }

            $result[] = '<p class="c-'.($id_product ? 'yellow' : 'green').'">'.$product->name.' - '.
                ($id_product ? 'цена обновлена' : 'товар добавлен')
                .'</p>';
            unset($params['values']->$reference);

            if(++$this->counter >= Configuration::get('AJAXIMPORT_NBR_PRODUCTS'))
                break;
        }

        return $result;
    }
}