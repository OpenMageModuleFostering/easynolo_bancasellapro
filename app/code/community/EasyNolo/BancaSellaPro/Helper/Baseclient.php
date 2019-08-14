<?php
 /**
 * Class     Baseclient.php
 * @category EasyNolo_BancaSellaPro
 * @package  EasyNolo
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Helper_Baseclient extends Mage_Core_Helper_Abstract{

    protected $_webserviceClassName = null;

    /**
     * Inizializza il client basandosi sul webservice passato in input
     * @param $webService
     *
     * @return bool|Zend_Soap_Client
     */
    protected function _initClient($webService){
        if (!extension_loaded('soap')) {
            Mage::logException(Mage::exception('EasyNolo_BancaSellaPro','PHP SOAP extension is required.'));
            $_helper= Mage::helper('easynolo_bancasellapro');
            $_helper->log('Non è stato possibile creare il client per il webserver');
            return false;
        }

        $url = $webService->getWSUrl();
        $client = new Zend_Soap_Client(
            $url, array(
            'compression' => SOAP_COMPRESSION_ACCEPT,
            'soap_version' => SOAP_1_2,));
        return  $client;
    }

    public function getInitWebservice(){
        if($this->_webserviceClassName == null){
            $exception = Mage::exception('EasyNolo_BancaSellaPro','Helper '.__CLASS__.' didn\'t set webserviceClassName');
            Mage::logException($exception);

            throw $exception;
        }

        $webService = Mage::getModel($this->_webserviceClassName);
        $gestPay=Mage::getModel('easynolo_bancasellapro/gestpay');
        $webService->setBaseUrl($gestPay->getBaseWSDLUrlSella());

        return $webService;
    }
} 