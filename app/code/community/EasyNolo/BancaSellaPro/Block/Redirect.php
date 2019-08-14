<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Block_Redirect  extends Mage_Page_Block_Redirect
{

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     *  Get target URL
     *
     *  @return string
     */
    public function getTargetURL ()
    {
        if(!$this->getCalculateTargetUrl()){
            $helper = Mage::helper('easynolo_bancasellapro');
            $this->setCalculateTargetUrl( $helper->getRedirectUrlToPayment($this->getOrder()));
        }
        return $this->getCalculateTargetUrl();

    }


    public function getMethod ()
    {
        return 'GET';
    }

    public function getMessage ()
    {
        return $this->__('You will be redirected to Banca Sella in a few seconds.');
    }

}
