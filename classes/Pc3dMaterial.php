<?php
/**
 * Material Model Class
 * Handles CRUD operations for 3D printing materials
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pc3dMaterial extends ObjectModel
{
    public $id_pc3d_material;
    public $name;
    public $density;
    public $price_per_gram;
    public $color;
    public $description;
    public $active;
    public $position;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'pc3d_material',
        'primary' => 'id_pc3d_material',
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 64,
            ],
            'density' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isUnsignedFloat',
                'required' => true,
            ],
            'price_per_gram' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
                'required' => true,
            ],
            'color' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isColor',
                'size' => 32,
            ],
            'description' => [
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
            ],
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
        ],
    ];

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
    }

    public function add($autoDate = true, $nullValues = false)
    {
        if (!$this->position) {
            $this->position = self::getHighestPosition() + 1;
        }

        return parent::add($autoDate, $nullValues);
    }

    /**
     * Get all active materials
     *
     * @return array
     */
    public static function getActiveMaterials()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('pc3d_material');
        $sql->where('active = 1');
        $sql->orderBy('position ASC, name ASC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get all materials (for admin)
     *
     * @return array
     */
    public static function getAllMaterials()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('pc3d_material');
        $sql->orderBy('position ASC, name ASC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get material by ID
     *
     * @param int $id
     * @return array|false
     */
    public static function getMaterialById($id)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('pc3d_material');
        $sql->where('id_pc3d_material = ' . (int) $id);

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Get highest position value
     *
     * @return int
     */
    public static function getHighestPosition()
    {
        $sql = 'SELECT MAX(position) FROM `' . _DB_PREFIX_ . 'pc3d_material`';

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Calculate weight based on volume and infill
     *
     * @param float $volumeCm3 Volume in cubic centimeters
     * @param float $infillPercent Infill percentage (0-100)
     * @return float Weight in grams
     */
    public function calculateWeight($volumeCm3, $infillPercent = 100)
    {
        // Weight = Volume × Density × (Infill% / 100)
        // We also add shell weight (assume 10% shell thickness contributes to weight)
        $shellFactor = 0.10;
        $infillFactor = ($infillPercent / 100) * (1 - $shellFactor);
        $totalFactor = $shellFactor + $infillFactor;

        return $volumeCm3 * $this->density * $totalFactor;
    }

    /**
     * Calculate price based on weight
     *
     * @param float $weightGrams Weight in grams
     * @return float Price
     */
    public function calculatePrice($weightGrams)
    {
        return $weightGrams * $this->price_per_gram;
    }

    /**
     * Full price calculation from volume
     *
     * @param float $volumeCm3 Volume in cubic centimeters
     * @param float $infillPercent Infill percentage (0-100)
     * @return array
     */
    public function getQuote($volumeCm3, $infillPercent = 100)
    {
        $weight = $this->calculateWeight($volumeCm3, $infillPercent);
        $materialPrice = $this->calculatePrice($weight);

        return [
            'volume_cm3' => round($volumeCm3, 4),
            'weight_grams' => round($weight, 2),
            'material_price' => round($materialPrice, 2),
            'material_name' => $this->name,
            'density' => $this->density,
            'price_per_gram' => $this->price_per_gram,
            'infill_percent' => $infillPercent,
        ];
    }

    /**
     * Install default materials
     *
     * @return bool
     */
    public static function installDefaults()
    {
        $defaults = [
            [
                'name' => 'PLA',
                'density' => 1.24,
                'price_per_gram' => 0.05,
                'color' => '#4CAF50',
                'description' => 'Polylactic Acid - Easy to print, biodegradable',
                'active' => 1,
                'position' => 1,
            ],
            [
                'name' => 'ABS',
                'density' => 1.04,
                'price_per_gram' => 0.06,
                'color' => '#2196F3',
                'description' => 'Acrylonitrile Butadiene Styrene - Strong and durable',
                'active' => 1,
                'position' => 2,
            ],
            [
                'name' => 'PETG',
                'density' => 1.27,
                'price_per_gram' => 0.07,
                'color' => '#FF9800',
                'description' => 'Polyethylene Terephthalate Glycol - Chemical resistant',
                'active' => 1,
                'position' => 3,
            ],
            [
                'name' => 'TPU',
                'density' => 1.21,
                'price_per_gram' => 0.12,
                'color' => '#9C27B0',
                'description' => 'Thermoplastic Polyurethane - Flexible material',
                'active' => 1,
                'position' => 4,
            ],
            [
                'name' => 'Nylon',
                'density' => 1.14,
                'price_per_gram' => 0.15,
                'color' => '#607D8B',
                'description' => 'Polyamide - Strong and wear-resistant',
                'active' => 1,
                'position' => 5,
            ],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($defaults as $data) {
            $material = new self();
            $material->name = $data['name'];
            $material->density = $data['density'];
            $material->price_per_gram = $data['price_per_gram'];
            $material->color = $data['color'];
            $material->description = $data['description'];
            $material->active = $data['active'];
            $material->position = $data['position'];
            $material->date_add = $now;
            $material->date_upd = $now;

            if (!$material->add(false)) {
                return false;
            }
        }

        return true;
    }
}
