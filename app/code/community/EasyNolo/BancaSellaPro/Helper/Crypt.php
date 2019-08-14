<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Helper_Crypt extends EasyNolo_BancaSellaPro_Helper_Baseclient{

    protected $_webserviceClassName ='easynolo_bancasellapro/webservice_wscryptdecrypt';
    /**
     * Effettua l'encypt dei dati memorizzati nel webserice
     * @param $webService contiene le info da criptare
     *
     * @return string stringa criptata
     */
    protected function getEncryptString($webService){

        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToEncrypt();

        $result = $client->Encrypt($param);

        $webService->setResponseEncrypt($result);

        $_helper= Mage::helper('easynolo_bancasellapro');

        $_helper->log('Encrypt string: ' .$webService->getCryptDecryptString());

        return $webService->getCryptDecryptString();

    }

    /**
     * Funzione che dall'ordine restituisce la stringa criptata delle sue info
     * @param $order ordine da criptare
     *
     * @return mixed stringa criptate
     */
    public function  getEncryptStringByOrder ($order){
        $method = $order->getPayment()->getMethodInstance();
        /** @var $webService EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt  */
        $webService = Mage::getModel('easynolo_bancasellapro/webservice_wscryptdecrypt');
        $webService->setOrder($order);
        $webService->setBaseUrl($method->getBaseWSDLUrlSella());

        return $this->getEncryptString($webService);

    }

    public function  getEncryptStringByOrderWitTokenRequest ($order){
        $method = $order->getPayment()->getMethodInstance();
        /** @var $webService EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt  */
        $webService = Mage::getModel('easynolo_bancasellapro/webservice_wscryptdecrypt');
        $webService->setOrder($order);
        $webService->setRecurringProfile(true);
        $webService->setBaseUrl($method->getBaseWSDLUrlSella());

        return $this->getEncryptString($webService);

    }

    /**
     * @param $method EasyNolo_BancaSellaPro_Model_Gestpay
     *
     * @return string
     */
    public function  getEncryptStringBeforeOrder ($method){
        /** @var EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt $webService */
        $webService = Mage::getModel('easynolo_bancasellapro/webservice_wscryptdecrypt');

        $webService->setInfoBeforeOrder($method);
        $webService->setBaseUrl($method->getBaseWSDLUrlSella());

        return $this->getEncryptString($webService);

    }


    /**
     * funzione che si occupa di decriptare i dati ricevuti da gestpay
     * @param $webService
     *
     * @return mixed
     */
    public function decriptPaymentRequest($webService){

        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToDecrypt();

        $result = $client->Decrypt($param);

        $webService->setResponseDecrypt($result);

        return $webService;

    }

    public function isPaymentOk($a , $b ){
        $_helper= Mage::helper('easynolo_bancasellapro');

        $webService =$this->getInitWebservice();

        $webService->setDecryptParam($a , $b);

        $result = $this->decriptPaymentRequest ($webService);

        if(!$result){
            return false;
        }

        $orderId = $webService->getShopTransactionID();
        /** @var Mage_Sales_Model_Order $order */

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        //salvo lo store per effettuare il redirect a completamento della verifica
        Mage::register('easynolo_bancasellapro_store_maked_order',$order->getStore());
        Mage::register('easynolo_bancasellapro_order',$order);

        if($order->getId()){
            if($webService->getFastResultPayment()){
                $_helper->log('Il web service ha dato esito positivo al pagamento');

                //controllo se la richiesta s2s è già stata elaborata
                if(!$_helper->isElaborateS2S($order)){
                    $_helper->log('La transazione non è ancora stata inviata sul s2s');

                    //in questo punto l'utente ha completato l'ordine ma aspettiamo la chiamata s2s per confermare lo stato
                    if($order->getId()){
                    	$message = $this->__("Authorizing amount of %s is pending approval on gateway.", $order->getBaseCurrency()->formatTxt($order->getBaseGrandTotal()));
                        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $message);
                        $order->save();
                    }
                }else{
                    $_helper->log('La tranzazione è gia stata salvata, non cambio lo stato');
                }

                if( $order->getStatus() != Mage_Sales_Model_Order::STATUS_FRAUD){
                    $_helper->log('Invio email di conferma creazione ordine all\'utente');
                    $order->sendNewOrderEmail();
                }
                return true;
            }else{
                $_helper->log('Il web service ha restituito KO');
                $message = $this->__('Payment transaction not authorized: %s.', $result->getErrorDescription());
                //in questo punto l'ordine è stato annullato dall'utente
                if($order->getId()){
                    $method= $order->getPayment()->getMethodInstance();
                    $order->cancel();
                    $order->setState($method->getOrderStatusKoUser(), true, $message);
                    $order->save();
                }

                return $message;
            }
        }else{
            $message = $this->__("There was an error processing your order. Please contact us or try again later.");
            $_helper->log('L\'ordine restituito da bancasella non esiste. Increment id= '.$orderId);
            return $message;
        }
    }

    public function getInitWebservice(){
        $webService = Mage::getModel('easynolo_bancasellapro/webservice_wscryptdecrypt');
        $gestPay=Mage::getModel('easynolo_bancasellapro/gestpay');
        $webService->setBaseUrl($gestPay->getBaseWSDLUrlSella());

        return $webService;
    }

    public function setStatusOrderByS2SRequest($order, $webservice){
        $order = Mage::getModel('sales/order')->load($order->getId());
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('Controllo l\'ordine in base alle risposte della S2S');
        if($method->getConfigData('order_status_fraud_gestpay')){
            $_helper->log('Controllo frode');

            $message=false;
            $total= $method->getTotalByOrder($order);
            $_helper->log('controllo il totale dell\'ordine : ' .$webservice->getAmount(). ' = '.round($total, 2));
            if (round($webservice->getAmount(), 2) != round($total, 2)){
                // il totatle dell'ordine non corrisponde al totale della transazione
                $message = $this->__('Transaction amount differs from order grand total.');
            }

            if ($webservice->getAlertCode()!=''){
                $_helper->log('controllo alert della transazione : ' .$webservice->getAlertCode());
                $message = $webservice->getAlertDescription();
            }

            if($message){
                $_helper->log('rilevata possibile frode: '.$message);
                $payment->setTransactionAdditionalInfo(array( Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS=>$webservice->getData()),"");
                $payment->setTransactionId($webservice->getShopTransactionId())
                    ->setCurrencyCode($order->getBaseCurrencyCode())
                    ->setIsTransactionClosed(0)
                    ->setPreparedMessage($message)
                    ->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_UPDATE, false);
                $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage_Sales_Model_Order::STATUS_FRAUD, $message);
                $order->save();
                return false;
            }
        }

        switch ($webservice->getTransactionResult()){
            case EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt::TRANSACTION_RESULT_PENDING :
            	$message = $this->__("Authorizing amount of %s is pending approval on gateway.", $order->getBaseCurrency()->formatTxt($order->getBaseGrandTotal()));
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $message);
                $_helper->log('Pagamento effettuato con bonifico bancario, verificare a mano la transazione');
                $order->addStatusHistoryComment($this->__('Payment was using bank transfer. Please verify the order status on GestPay.'));
                break;
            case EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt::TRANSACTION_RESULT_OK :
                $_helper->log('Pagamento effettuato correttamente. Cambio stato all\'ordine e salvo l\'id della transazione');
                $message = $this->__("Authorization approved on gateway. GestPay Transaction ID: %s", $webservice->getBankTransactionID());
                if($paymentMethod = $webservice->getPaymentMethod()){
                	$message .= " (".$paymentMethod.")";
                }
                
                $order->addStatusHistoryComment($message);

                // create the authorization transaction
                $payment->setAdditionalData(serialize($webservice->getData()));
                $payment->setTransactionAdditionalInfo(array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $webservice->getData()), "");
                $payment->setTransactionId($webservice->getShopTransactionId());
                $payment->setCurrencyCode($order->getBaseCurrencyCode());
                $payment->setIsTransactionClosed(0);
                $payment->registerAuthorizationNotification($webservice->getAmount());
                $order = $payment->getOrder();
                $order->save();

                // reload the order and the related payment entities
                $order = Mage::getModel('sales/order')->load($order->getId());
                $payment = $order->getPayment();

                // perform the capture
                $setOrderAsPaid = true;
                if($method->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
                    if($method->getConfigData('use_s2s_api')){
                        try{
                            $payment->capture();
                            $order->save();
                        }catch(Exception $e){
                            $setOrderAsPaid = false;
                            $message = $this->__("Failed capture online: %", $e->getMessage());
                            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, $message);
                        }
                    }

                    if($setOrderAsPaid == true){
                        $payment->registerCaptureNotification($order->getBaseGrandTotal());
                    }
                }

                if($setOrderAsPaid == true){
                    $order->setState($method->getOrderStatusOkGestPay(), true);
                }

                break;
            case EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt::TRANSACTION_RESULT_KO :
                $_helper->log('Pagamento non andato a buon fine. Cambio stato all\'ordine e salvo l\'id della transazione');
                $message = $this->__("Authorizing amount of %s is pending approval on gateway.", $order->getBaseCurrency()->formatTxt($order->getBaseGrandTotal()));
                $order->cancel();
                $order->setState($method->getOrderStatusKoGestPay(), true, $message);
                $message = $this->__("Payment attempt has been declined. GestPay Transaction ID: %s", $webservice->getBankTransactionID());
                if($paymentMethod = $webservice->getPaymentMethod()){
                	$message .= " (".$paymentMethod.")";
                }
                
                $order->addStatusHistoryComment($message);
                break;
        }
        
        $order->save();
        $_helper->log('Dopo l\'elaborazione della s2s l\'ordine con id: '.$order->getId().' ha state: '.$order->getState().' e status: '.$order->getStatus());
    }
}