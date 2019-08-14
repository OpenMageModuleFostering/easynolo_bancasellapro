<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Block_Form extends Mage_Payment_Block_Form
{
    protected $_isRecurringProfile = null;

    protected function _construct()
    {
        parent::_construct();

        if(Mage::app()->getRequest()->getRouteName()=='opc'){
            $this->setTemplate('easynolo/bancasellapro/gestpay/form_onepagecheckout.phtml');
        }else{
            $this->setTemplate('easynolo/bancasellapro/gestpay/form.phtml');
        }
    }

    /**
     * Metodo che verifica se la richiesta è di tipo ajax
     * @return bool
     */
    public function isAjaxRequest(){
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * Metodo che restituisce l'url dove reindirizzare l'utente dopo la verifica 3dsecure
     * @return string
     */
    public function getPage3d(){
        return Mage::getUrl('bancasellapro/gestpay/confirm3d',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }

    public function getSuccessRedirect(){
        return Mage::getUrl('bancasellapro/gestpay/result',array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }

    /**
     * metodo che restituisce l'url dove effettuare la verifica 3dsecure
     * @return string
     */
    public function getAuthPage(){
        $helper = Mage::helper('easynolo_bancasellapro');
        return $helper->getAuthPage();
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = Mage::helper('easynolo_bancasellapro')->getYears();
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {

            $months = Mage::helper('easynolo_bancasellapro')->getMonths();
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Check if iframe is enable on backend
     * @return boolean
     */
    public function isIframeEnable(){
        return $this->getMethod()->isIframeEnable();
    }

    public function isRecurringProfile()
    {
        if($this->_isRecurringProfile ==null){

            $quote= Mage::getModel('checkout/cart')->getQuote();

            $helper = Mage::helper('easynolo_bancasellapro/recurringprofile');
            $this->_isRecurringProfile = $helper->isRecurringProfile($quote);
        }
        return $this->_isRecurringProfile;
    }

}