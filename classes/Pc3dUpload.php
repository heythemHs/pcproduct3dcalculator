<?php
/**
 * Upload Model Class
 * Handles file upload tracking and management
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Pc3dUpload extends ObjectModel
{
    public $id_pc3d_upload;
    public $id_cart;
    public $id_order;
    public $id_customer;
    public $filename;
    public $original_name;
    public $file_size;
    public $volume_cm3;
    public $weight_grams;
    public $material_id;
    public $infill_percent;
    public $estimated_price;
    public $status;
    public $notes;
    public $date_add;

    const STATUS_PENDING = 'pending';
    const STATUS_CALCULATED = 'calculated';
    const STATUS_IN_CART = 'in_cart';
    const STATUS_ORDERED = 'ordered';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static $definition = [
        'table' => 'pc3d_upload',
        'primary' => 'id_pc3d_upload',
        'fields' => [
            'id_cart' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'id_order' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'id_customer' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'filename' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255,
            ],
            'original_name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255,
            ],
            'file_size' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
            ],
            'volume_cm3' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isUnsignedFloat',
            ],
            'weight_grams' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isUnsignedFloat',
            ],
            'material_id' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'infill_percent' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPercentage',
            ],
            'estimated_price' => [
                'type' => self::TYPE_FLOAT,
                'validate' => 'isPrice',
            ],
            'status' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 32,
            ],
            'notes' => [
                'type' => self::TYPE_HTML,
                'validate' => 'isCleanHtml',
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
            ],
        ],
    ];

    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);
    }

    /**
     * Get uploads by cart ID
     *
     * @param int $cartId
     * @return array
     */
    public static function getByCartId($cartId)
    {
        $sql = new DbQuery();
        $sql->select('u.*, m.name as material_name, m.color as material_color');
        $sql->from('pc3d_upload', 'u');
        $sql->leftJoin('pc3d_material', 'm', 'u.material_id = m.id_pc3d_material');
        $sql->where('u.id_cart = ' . (int) $cartId);
        $sql->orderBy('u.date_add DESC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get uploads by order ID
     *
     * @param int $orderId
     * @return array
     */
    public static function getByOrderId($orderId)
    {
        $sql = new DbQuery();
        $sql->select('u.*, m.name as material_name, m.color as material_color');
        $sql->from('pc3d_upload', 'u');
        $sql->leftJoin('pc3d_material', 'm', 'u.material_id = m.id_pc3d_material');
        $sql->where('u.id_order = ' . (int) $orderId);
        $sql->orderBy('u.date_add DESC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get uploads by customer ID
     *
     * @param int $customerId
     * @return array
     */
    public static function getByCustomerId($customerId)
    {
        $sql = new DbQuery();
        $sql->select('u.*, m.name as material_name, m.color as material_color');
        $sql->from('pc3d_upload', 'u');
        $sql->leftJoin('pc3d_material', 'm', 'u.material_id = m.id_pc3d_material');
        $sql->where('u.id_customer = ' . (int) $customerId);
        $sql->orderBy('u.date_add DESC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get all uploads for admin
     *
     * @param int $limit
     * @param int $offset
     * @param string $orderBy
     * @param string $orderWay
     * @return array
     */
    public static function getAllUploads($limit = 50, $offset = 0, $orderBy = 'date_add', $orderWay = 'DESC')
    {
        $sql = new DbQuery();
        $sql->select('u.*, m.name as material_name, m.color as material_color');
        $sql->from('pc3d_upload', 'u');
        $sql->leftJoin('pc3d_material', 'm', 'u.material_id = m.id_pc3d_material');
        $sql->orderBy(pSQL($orderBy) . ' ' . pSQL($orderWay));
        $sql->limit((int) $limit, (int) $offset);

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Count all uploads
     *
     * @return int
     */
    public static function countAllUploads()
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'pc3d_upload`';

        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * Link upload to order when cart is converted
     *
     * @param int $cartId
     * @param int $orderId
     * @return bool
     */
    public static function linkToOrder($cartId, $orderId)
    {
        return Db::getInstance()->update(
            'pc3d_upload',
            [
                'id_order' => (int) $orderId,
                'status' => self::STATUS_ORDERED,
            ],
            'id_cart = ' . (int) $cartId
        );
    }

    /**
     * Get upload directory path
     *
     * @return string
     */
    public static function getUploadDir()
    {
        return _PS_MODULE_DIR_ . 'pcproduct3dcalculator/upload/';
    }

    /**
     * Get full file path
     *
     * @return string
     */
    public function getFilePath()
    {
        return self::getUploadDir() . $this->filename;
    }

    /**
     * Check if file exists
     *
     * @return bool
     */
    public function fileExists()
    {
        return file_exists($this->getFilePath());
    }

    /**
     * Delete file from disk
     *
     * @return bool
     */
    public function deleteFile()
    {
        $filePath = $this->getFilePath();

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Override delete to also remove file
     *
     * @return bool
     */
    public function delete()
    {
        $this->deleteFile();

        return parent::delete();
    }

    /**
     * Clean up old pending uploads
     *
     * @param int $daysOld
     * @return int Number of deleted records
     */
    public static function cleanupOldUploads($daysOld = 7)
    {
        $uploadDir = self::getUploadDir();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        // Get old uploads
        $sql = new DbQuery();
        $sql->select('id_pc3d_upload, filename');
        $sql->from('pc3d_upload');
        $sql->where("status = '" . pSQL(self::STATUS_PENDING) . "'");
        $sql->where("date_add < '" . pSQL($cutoffDate) . "'");

        $uploads = Db::getInstance()->executeS($sql);
        $count = 0;

        foreach ($uploads as $upload) {
            // Delete file
            $filePath = $uploadDir . $upload['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete record
            Db::getInstance()->delete('pc3d_upload', 'id_pc3d_upload = ' . (int) $upload['id_pc3d_upload']);
            $count++;
        }

        return $count;
    }

    /**
     * Calculate and update price
     *
     * @param Pc3dMaterial $material
     * @param float $infillPercent
     * @return float
     */
    public function calculateAndSave($material, $infillPercent)
    {
        $quote = $material->getQuote($this->volume_cm3, $infillPercent);

        $this->weight_grams = $quote['weight_grams'];
        $this->material_id = $material->id;
        $this->infill_percent = $infillPercent;

        // Get additional pricing configuration
        $minimumPrice = (float) Configuration::get('PC3D_MINIMUM_PRICE') ?: 0;
        $setupFee = (float) Configuration::get('PC3D_SETUP_FEE') ?: 0;
        $infillModifier = $this->getInfillModifier($infillPercent);

        $totalPrice = $quote['material_price'] + $setupFee + $infillModifier;
        $totalPrice = max($totalPrice, $minimumPrice);

        $this->estimated_price = round($totalPrice, 2);
        $this->status = self::STATUS_CALCULATED;

        $this->update();

        return $this->estimated_price;
    }

    /**
     * Get infill price modifier
     *
     * @param float $infillPercent
     * @return float
     */
    private function getInfillModifier($infillPercent)
    {
        // Higher infill = more material usage = optional surcharge
        $infillSurchargeEnabled = (bool) Configuration::get('PC3D_INFILL_SURCHARGE_ENABLED');

        if (!$infillSurchargeEnabled) {
            return 0;
        }

        $surchargePerPercent = (float) Configuration::get('PC3D_INFILL_SURCHARGE_RATE') ?: 0;

        // Only apply surcharge above 20% infill
        $baseInfill = 20;
        if ($infillPercent <= $baseInfill) {
            return 0;
        }

        return ($infillPercent - $baseInfill) * $surchargePerPercent;
    }

    /**
     * Get formatted file size
     *
     * @return string
     */
    public function getFormattedFileSize()
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
