<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */
class EasyNolo_BancaSellaPro_Block_Confirm3d extends Mage_Core_Block_Template {

    public function getPARes(){
        return $this->getRequest()->get('PaRes');
    }

    public function getCartUrl(){
        return Mage::getUrl('checkout/cart',array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }

}