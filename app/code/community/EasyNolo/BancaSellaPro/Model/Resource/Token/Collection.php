<?php
 /**
  * Class     Collection.php
  * @category EasyNolo_BancaSellaPro
  * @package  EasyNolo
  * @author   Easy Nolo <ecommerce@sella.it>
  */

class EasyNolo_BancaSellaPro_Model_Resource_Token_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('easynolo_bancasellapro/token');
    }


    public function addProfileToFilter(Mage_Payment_Model_Recurring_Profile $profile){
        return $this->addFieldToFilter('profile_id',array('eq'=>$profile->getId()));
    }

    public function addValidDateFilter(){
        return $this->addFieldToFilter('expiry_date',array('gteq' => Varien_Date::now(true)));
    }
} 