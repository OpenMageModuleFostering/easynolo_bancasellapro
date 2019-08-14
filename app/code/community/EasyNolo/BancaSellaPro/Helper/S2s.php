<?php
/**
 * @category Bitbull
 * @package  Bitbull_BancaSellaPro
 * @author   Mirko Cesaro <mirko.cesaro@bitbull.it>
 */

class EasyNolo_BancaSellaPro_Helper_S2s extends EasyNolo_BancaSellaPro_Helper_Baseclient {

    protected $_webserviceClassName ='easynolo_bancasellapro/webservice_wss2s';

    const STATUS_REFUND_TOTAL  = 'refund_bancasella';


    public function capturePayment($payment,$amount){
        $order = $payment->getOrder();
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('richiesto capture per ordine '. $order->getIncrementId());
        //inizializza il webservice
        $webservice = $this->getInitWebservice();
        //imposta l'ordine
        $webservice->setOrder($order);
        $result = $this->executeCaptureS2S($webservice,$amount);
        if ($result->getTransactionResult() == "KO") {
            $payment->setIsTransactionPending(true);
            $message = $this->__('Capture amount of %s online failed: %s', $order->getBaseCurrency()->formatTxt($amount), $result->getErrorDescription());
            $order->addStatusHistoryComment($message, false);
            Mage::throwException($message);
        }

        return $this;
    }

    public function refundPayment($payment,$amount){
        $order = $payment->getOrder();
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('richiesto refund per ordine '. $order->getIncrementId());
        //inizializza il webservice
        $webservice = $this->getInitWebservice();
        //imposta l'ordine
        $webservice->setOrder($order);
        $result = $this->executeRefundS2S($webservice,$amount);
        if($result->getTransactionResult() == "KO") {
        	$payment->setIsTransactionPending(true);
			$message = $this->__('Refund amount of %s online failed: %s', $order->getBaseCurrency()->formatTxt($amount), $result->getErrorDescription());
            $order->addStatusHistoryComment($message, false);
            Mage::throwException($message);
        }
        
        return $this;
    }

    public function voidPayment($payment){
        $order = $payment->getOrder();
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('richiesto annullamento per ordine '. $order->getIncrementId());
        //inizializza il webservice
        $webservice = $this->getInitWebservice();
        //imposta l'ordine
        $webservice->setOrder($order);
        $result = $this->executeVoidS2S($webservice);
        if($result->getTransactionResult() == "KO"){
            $payment->setIsTransactionPending(true);
			$message = $this->__('Void amount of %s online failed: %s', $order->getBaseCurrency()->formatTxt($amount), $result->getErrorDescription());
            $order->addStatusHistoryComment($message, false);
            Mage::throwException($message);
        }
        
        return $this;
    }

    protected function executeCaptureS2S($webService,$amount){
        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToCallCaptureS2S($amount);

        $result = $client->callSettleS2S($param);

        $webService->setResponseCallSettleS2S($result);
        return $webService;
    }

    protected function executeRefundS2S($webService,$amount){
        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToCallPagamS2S($amount);

        $result = $client->callRefundS2S($param);

        $webService->setResponseCallRefundS2S($result);
        return $webService;
    }

    protected function executeVoidS2S($webService){
        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToCallPagamS2S(0);

        $result = $client->callDeleteS2S($param);

        $webService->setResponseCallDeleteS2S($result);
        return $webService;
    }

    public function acceptPayment(Mage_Payment_Model_Info $payment){

        /* implementato per gestire accept su payment review a backend, in caso di capture non andato a buon fine*/
        return true;

    }
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        return true;
    }




} 