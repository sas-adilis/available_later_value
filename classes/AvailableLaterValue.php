<?php


class AvailableLaterValue extends ObjectModel
{
	/** @var string Name */
	public $name;
    public $reference;
    public $description_short;
	public $description_long;
	public $delay_in_days = 0;


    private static $cacheAvailabilitiesMessages = null;
    private static $cacheAvailabilitiesDelays = null;
    private static $cacheProducts = null;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'available_later_value',
		'primary' => 'id_available_later_value',
		'multilang' => true,
		'fields' => array(
            'delay_in_days' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 5),
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64],

            // Lang fields
    		'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
			'description_short' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false, 'size' => 255),
			'description' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => false),
		),
	);

	public static function getAvailabilities($id_lang) {
		return Db::getInstance()->executeS('
			SELECT al.*, al_l.name
			FROM '._DB_PREFIX_.'available_later_value al
			LEFT JOIN `'._DB_PREFIX_.'available_later_value_lang` al_l
			ON (al.`id_available_later_value` = al_l.`id_available_later_value` AND `id_lang` = '.(int)$id_lang.')
			ORDER BY `name` ASC
		');
	}

    public static function getAvailabilityMsg($id_available_later, $id_lang) {
        if (self::$cacheAvailabilitiesMessages === null) {
            self::$cacheAvailabilitiesMessages = array();
            foreach(AvailableLaterValue::getAvailabilities($id_lang) as $availablity) {
                self::$cacheAvailabilitiesMessages[$availablity['id_available_later_value']] = $availablity['name'];
            }
        }
        return self::$cacheAvailabilitiesMessages[$id_available_later] ?? '';
    }

    public static function getAvailabilityDelay($id_available_later, $id_lang) {
        if (self::$cacheAvailabilitiesDelays === null) {
            self::$cacheAvailabilitiesDelays = array();
            foreach(AvailableLaterValue::getAvailabilities($id_lang) as $availablity) {
                self::$cacheAvailabilitiesDelays[$availablity['id_available_later_value']] = $availablity['delay_in_days'];
            }
        }
        return self::$cacheAvailabilitiesDelays[$id_available_later] ?? '';
    }

    public static function getAllProducts($id_lang) {
        if (self::$cacheProducts === null) {
            self::$cacheProducts = Db::getInstance()->executeS('
                SELECT p.id_product, p.reference, s.name as supplier, p.id_available_later_value, pl.available_later
                FROM '._DB_PREFIX_.'product p
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang='.(int)$id_lang.'
                LEFT JOIN '._DB_PREFIX_.'supplier s ON p.id_supplier = s.id_supplier
            ');
        }
        return self::$cacheProducts;
    }
}