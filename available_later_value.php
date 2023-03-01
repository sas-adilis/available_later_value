<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/classes/AvailableLaterValue.php';

class available_later_value extends Module
{
    function __construct()
    {
        $this->name = 'available_later_value';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->displayName = $this->l('Gestion des valeurs de délai hors stock');

        parent::__construct();
    }

    public function install() {

        if (file_exists($this->getLocalPath().'sql/install.php')) {
            require_once($this->getLocalPath().'sql/install.php');
        }

        $id_tab = (int)Tab::getIdFromClassName('AdminAvailableLaterValue');
        $tab = new Tab($id_tab);
        $tab->class_name = 'AdminAvailableLaterValue';
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        foreach(\Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Délais hors stocks';
        }
        if (!$tab->save()) {
            return false;
        }

        return
            parent::install() &&
            $this->registerHook('displayAdminProductsQuantitiesStepBottom') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('displayAdminOrderMainBottom') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionObjectAvailableLaterUpdateAfter') &&
            $this->registerHook('actionProductUpdate');
    }

    public function getContent() {
        if (\Tools::isSubmit('export'.$this->name.'Module')) {
            $file = 'messages-'.date('Ymd').'-'.date('His').'.csv';
            $id_lang = (int)Tools::getValue('id_lang_export', $this->context->language->id);

            ob_clean();
            header('Content-Type: text/csv');
            header('Cache-Control: no-store, no-cache');
            header('Content-Disposition: attachment; filename="'.$file.'"');

            $handle = fopen('php://output', 'w+');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, array(
                'Id produit',
                'Référence',
                'Fournisseur',
                'Message hors-stock',
                'ID message hors-stock',
            ), ';');

            foreach(AvailableLaterValue::getAllProducts($id_lang) as $product) {
                fputcsv($handle, array(
                    (int)$product['id_product'],
                    $product['reference'],
                    $product['supplier'],
                    AvailableLaterValue::getAvailabilityMsg($product['id_available_later_value'], $id_lang),
                    $product['id_available_later_value'],
                ), ';');
            }

            die('passe');
            exit;
        }

        if (\Tools::isSubmit('import'.$this->name.'Module')) {
            $file = $_FILES['file']['tmp_name'];
            $handle = false;
            $id_lang = (int)Tools::getValue('id_lang_import', $this->context->language->id);

            if (is_file($file) && is_readable($file)) {
                $handle = fopen($file, 'r');
            }

            if (!$handle) {
                $this->context->controller->errors[] = $this->l('Impossible de lire le fichier CSV');
            }

            for ($current_line = 0; $line = fgetcsv($handle, 0, ';'); $current_line++) {
                $id_product = (int)$line[0];
                if (!$id_product) {
                    continue;
                }

                $line = array_map('utf8_encode', $line);
                foreach (self::getAllProducts() as $product) {
                    if ($id_product === (int)$product['id_product']) {

                        $id_available_later = (int)$line[4];
                        $message = self::getAvailabilityMsg($id_available_later, $id_lang);

                        if (
                            (string)$line[1] !== (string)$product['reference'] ||
                            (string)$line[2] !== (string)$product['supplier'] ||
                            (string)$line[3] !== (string)$message
                        ) {
                            $this->context->controller->errors[] = sprintf(
                                'Une erreur est survenue lors de la verification référence/fournisseur/message concernant le produit #%d',
                                (int)$id_product
                            );
                        } else {
                            if ($id_available_later !== (int)$product['id_available_later']) {
                                if (!Db::getInstance()->update(
                                    'product',
                                    array('id_available_later' => $id_available_later),
                                    'id_product = '. $id_product,
                                    1
                                )) {
                                    $this->context->controller->errors[] = sprintf(
                                        'Une erreur est survenue lors de la requête SQL concernant le produit #%d',
                                        $id_product
                                    );
                                }
                            }
                            if ($message !== (string)$product['available_later']) {
                                if (!Db::getInstance()->update(
                                    'product_lang',
                                    array('available_later' => pSQL($message)),
                                    'id_product = '. $id_product .' AND id_lang = '. $id_lang,
                                    1
                                )) {
                                    $this->context->controller->errors[] = sprintf(
                                        'Une erreur est survenue lors de la requête SQL concernant le produit #%d',
                                        $id_product
                                    );
                                }
                            }
                        }
                        break;
                    }
                }
            }

            if (!count($this->context->controller->errors)) {
                $redirect_after = $this->context->link->getAdminLink('AdminModules').'&conf=4&configure='.$this->name.'&module_name='.$this->name;
                Tools::redirectAdmin($redirect_after);
            }
        }

        return $this->renderForm();
    }

    private function renderForm() {
        $helper = new \HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name.'Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex .= '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'fields_value' => [
                'id_lang_export' => $this->context->language->id,
                'id_lang_import' => $this->context->language->id,
            ]
        ];

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Exporter les messages hors-stock'),
                        'icon' => 'icon-cogs'
                    ],
                    'input' => [
                        'id_lang' => [
                            'type' => 'select',
                            'label' => $this->l('Langue'),
                            'name' => 'id_lang_export',
                            'options' => [
                                'query' => Language::getLanguages(false),
                                'id' => 'id_lang',
                                'name' => 'name'
                            ]
                        ]
                    ],
                    'submit' => [
                        'title' => $this->l('Exporter'),
                        'name' => 'export'.$this->name.'Module',
                    ]
                ]
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Importer un fichier de messages hors-stock'),
                        'icon' => 'icon-cloud'
                    ],
                    'description' =>
                        $this->l('Permet d\'importer au format CSV les messages hors-stock - Pour "Mars 2021" choisir format cellule TEXTE'),
                    'input' => [
                        [
                            'type' => 'file',
                            'label' => $this->l('Fichier à importer'),
                            'name' => 'file',
                            'desc' => 'Format attendu (Id produit;Référence;Fournisseur;Message hors-stock;Id Message hors-stock). Avec en-tête.',
                            'required' => true
                        ],
                        'id_lang' => [
                            'type' => 'select',
                            'label' => $this->l('Langue'),
                            'name' => 'id_lang_export',
                            'options' => [
                                'query' => Language::getLanguages(false),
                                'id' => 'id_lang',
                                'name' => 'name'
                            ]
                        ]
                    ],
                    'submit' => [
                        'title' => $this->l('Importer'),
                        'name' => 'import'.$this->name.'Module',
                    ]
                ],
            ]
        ]);
    }


    public function hookActionObjectAvailableLaterUpdateAfter($params) {
        $availability = $params['object'];
        if (!Validate::isLoadedObject($availability) || !is_array($availability->name)) {
            return;
        }

        foreach ($availability->name as $id_lang => $value) {
            Db::getInstance()->execute('
                UPDATE '._DB_PREFIX_.'product_lang
                SET available_later = "'.pSQL($value).'"
                WHERE id_lang='.$id_lang.' AND id_product IN(
                    SELECT DISTINCT id_product FROM '._DB_PREFIX_.'product
                    WHERE id_available_later = '.(int)$availability->id.'
                )
            ');
        }
    }

    public function hookDisplayAdminProductsQuantitiesStepBottom($params) {
        $id_available_later_value = (int)Db::getInstance()->getValue('
				SELECT id_available_later_value FROM '._DB_PREFIX_.'product
				WHERE id_product = '.(int)$params['id_product']
        );

        $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $this->context->smarty->assign([
            'availabilities' => AvailableLaterValue::getAvailabilities($id_lang),
            'id_available_later_value' => $id_available_later_value,
            'id_lang_default' => $id_lang
        ]);
        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/admin_products_quantities.tpl');
    }

    public function hookDisplayAdminOrderMainBottom($params) {
        $date_shipping_estimated = Db::getInstance()->getValue('
            SELECT date_shipping_estimated FROM '._DB_PREFIX_.'orders
            WHERE id_order='.(int)$params['id_order']
        );
        $this->context->smarty->assign(array(
            'date_shipping_estimated' => $date_shipping_estimated,
            'form_action' => self::getCurrentUrl(),
            'id_order' => (int)$params['id_order'],
        ));

        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/admin_order_main_bottom.tpl');
    }

    private static function getCurrentUrl() {
        return $_SERVER['REQUEST_URI'];
    }

    public function hookBackOfficeHeader() {

        if (Tools::isSubmit('submitDateShippingEstimated') && ($id_order = (int)Tools::getValue('id_order'))) {
            $flash_bag = $this->context->controller->get('session')->getFlashBag();
            $date_shipping_estimated = Tools::getValue('date_shipping_estimated');
            if (!Validate::isDate($date_shipping_estimated)) {
                $this->context->controller->errors[] = $this->l('Date incorrecte');
            } else {

                Db::getInstance()->update(
                    'orders',
                    ['date_shipping_estimated' => pSQL($date_shipping_estimated)],
                    'id_order=' . $id_order,
                    1
                );
                $flash_bag->add('success', $this->l('Date d\'expédition estimée mise à jour'));
            }
            Tools::redirectAdmin(self::getCurrentUrl());
        }
    }

    public function hookActionValidateOrder($params) {
        if(!Validate::isLoadedObject($params['order'])) {
            return;
        }

        $max_announced_delay = null;
        foreach ($params['order']->getProducts() as $product) {
            if ($product['product_quantity_in_stock'] - $product['product_quantity'] < 0 && (int)$product['id_available_later']) {
                $delay = AvailableLaterValue::getAvailabilityDelay($product['id_available_later'], $params['order']->id_lang);
                if ((int)$delay === 0) {
                    return;
                }
                if ($max_announced_delay === null || $delay > $max_announced_delay) {
                    $max_announced_delay = $delay;
                }

                $available_later = Db::getInstance()->getValue('
                    SELECT available_later FROM '._DB_PREFIX_.'product_lang
                    WHERE id_product='.(int)$product['product_id'].' AND id_lang='.(int)$params['order']->id_lang
                );

                if ($available_later !='') {
                    Db::getInstance()->update(
                        'order_detail',
                        ['available_later' => $available_later],
                        'id_order_detail='.(int)$product['id_order_detail'],
                        1
                    );
                }
            }
        }
        if ($max_announced_delay !== null) {
            $date_shipping_estimated = date('Y-m-d', strtotime ( $max_announced_delay.' weekdays' ) );
            Db::getInstance()->update(
                'orders',
                ['date_shipping_estimated' => pSQL($date_shipping_estimated)],
                'id_order='.(int)$params['order']->id,
                1
            );
        }
    }

    public function hookActionProductUpdate($params) {
        if (Tools::getIsset('id_available_later_value')) {
            $product = $params['product'];
            if (Validate::isLoadedObject($product)) {
                $id_available_later_value = (int)Tools::getValue('id_available_later_value');
                Db::getInstance()->update(
                    'product',
                    ['id_available_later_value' => $id_available_later_value],
                    'id_product='.(int)$product->id,
                    1
                );
                if ($id_available_later_value) {
                    $available_later_value = new AvailableLaterValue($id_available_later_value);
                    foreach (Language::getLanguages(false) as $lang) {
                        Db::getInstance()->update(
                            'product_lang',
                            ['available_later' => pSQL($available_later_value->name[$lang['id_lang']])],
                            'id_product='.(int)$product->id.' AND id_lang='.(int)$lang['id_lang']
                        );
                    }
                } else {
                    Db::getInstance()->update(
                        'product_lang',
                        ['available_later' => ''],
                        'id_product='.(int)$product->id
                    );
                }
            }
        }
    }
}