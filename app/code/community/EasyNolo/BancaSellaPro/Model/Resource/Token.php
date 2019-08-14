<?php
 /**
 * Class     Token.php
 * @category EasyNolo_BancaSellaPro
 * @package  EasyNolo
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Model_Resource_Token extends Mage_Core_Model_Resource_Db_Abstract {

    protected function _construct()
    {
        $this->_init('easynolo_bancasellapro/token', 'entity_id');
        $this->_idFieldName='entity_id';
    }

} 