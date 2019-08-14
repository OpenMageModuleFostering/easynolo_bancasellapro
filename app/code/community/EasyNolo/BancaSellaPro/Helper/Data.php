<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * metodo per effettuare il log del modulo
     * @param $msg
     */
    public function log($msg) {
        Mage::log($msg, null, 'EasyNolo_BancaSellaPro.log', Mage::getStoreConfig('payment/gestpaypro/log'));
    }

    /**
     * Metodo che restituisce l'url sul quale effettuare il pagamento
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool|string
     */
    function getRedirectUrlToPayment(Mage_Sales_Model_Order $order){

        //recupero la stringa criptata in base ai dati dell'ordine
        $crypt = Mage::helper('easynolo_bancasellapro/crypt');
        $stringEncrypt = $crypt->getEncryptStringByOrder($order);

        $method = $order->getPayment()->getMethodInstance();

        if($method instanceof EasyNolo_BancaSellaPro_Model_Gestpay){
            $url = $method->getRedirectPagePaymentUrl();
            return $this->createUrl($url , array('a'=>$method->getMerchantId(), 'b'=> $stringEncrypt));
        }
        return false;
    }

    function createUrl($url, $param){
        $paramether= '';
        if(count($param)){
            $paramether= '?';
            foreach($param as $name => $value)
            {
                $paramether.=$name.'='.$value.'&';
            }
        }
        $this->log('Url per il pagamento: '.$url.$paramether);
        return $url.$paramether;
    }


    /**
     * Recupera l'url del javascript per usare l'iframe
     * @return string
     */
    function getGestPayJs(){
        $method = Mage::getModel('easynolo_bancasellapro/gestpay');
        $url = null;
        if($method->isIframeEnable()){
            $url = $method->getIframeUrl();
        }
        return $url;
    }


    /**
     * Recupera l'url dove effettuare la verifica del 3ds
     * @return string
     */
    function getAuthPage(){
        /** @var EasyNolo_BancaSellaPro_Model_Gestpay $method */
        $method = Mage::getModel('easynolo_bancasellapro/gestpay');
        $url = $method->getAuthPage();
        return $url;
    }

    /**
     * Metodo che verifica se la transazione è stata elaborata dal una richiesta server to server
     * @param $order
     *
     * @return bool
     */
    public function isElaborateS2S($order){
        $state = $order->getState();
        if ($state == Mage_Sales_Model_Order::STATE_NEW)
            return false;
        return true;

    }

    /**
     * Metodo che restituisce un array associativo con anno in due cifre e anno in 4 cifre
     * @return array
     */
    public function getYears(){
        $years = array();
        $firstTwo = date("y");
        $firstFour = date("Y");
        $years[0]=$this->__('Year');
        for ($index=0; $index <= 10; $index++) {
            $years[$firstTwo + $index] = $firstFour+ $index;
        }
        return $years;
    }

    /**
     * Metodo che restituisce un array associativo con mese in due cifre e descrizione mese (numero - nome mese)
     * @return array
     */
    public function getMonths()
    {
        $data = Mage::app()->getLocale()->getTranslationList('month');
        $return = array();
        $return['0'] =   $this->__('Month');
        foreach ($data as $key => $value) {
            $monthNum = ($key < 10) ? '0'.$key : ''.$key;
            $return[$monthNum] = $monthNum . ' - ' . $value;
        }
        return $return;
    }

    public function isIWDOpcEnable(){
        $children = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$children;
        //controllo se ci sono dipendenze nel modulo bancasella
        if(isset($modulesArray['EasyNolo_BancaSellaPro']->depends)){
            $depens = $modulesArray['EasyNolo_BancaSellaPro']->depends->children();
            $dependsArray = (array)$depens;
            //se presente la dipendenza da IWD devo aggiornare la finestra per il pagamento con iframe
            if(isset($dependsArray['IWD_Opc'])){
                return true;
            }
        }
        return false;
    }

    /**
     * Metodo per aggiungere l'ordine alla sessione del checkout
     * @param Mage_Sales_Model_Order $order
     */
    public function addOrderToSession(Mage_Sales_Model_Order $order){

        if($order->getId()){
            $session = Mage::getSingleton('checkout/session');
            $session->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId());
        }

    }
}