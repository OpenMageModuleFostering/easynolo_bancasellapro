<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */
class EasyNolo_BancaSellaPro_Block_Utility_Text extends Mage_Core_Block_Abstract{

    protected function _toHtml()
    {
        /** @var EasyNolo_BancaSellaPro_Helper_Data $helper */
        $helper = Mage::helper('easynolo_bancasellapro');
        $text = $helper->getGestPayJs();
        $script = '';
        if($text){
            $script = '<script type="text/javascript" src="'.$text.'"></script>';
        }
        return $script;
    }

}