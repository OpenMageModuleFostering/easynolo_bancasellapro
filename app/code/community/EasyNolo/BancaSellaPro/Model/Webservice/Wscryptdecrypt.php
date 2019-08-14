<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */
class EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt extends EasyNolo_BancaSellaPro_Model_Webservice_Abstract{

    const PATH_WS_CRYPT_DECRIPT = '/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL';

    const TRANSACTION_RESULT_OK = 'OK';
    const TRANSACTION_RESULT_KO = 'KO';
    const TRANSACTION_RESULT_PENDING = 'XX';

    public function getWSUrl(){
        return $this->url_home . self::PATH_WS_CRYPT_DECRIPT;
    }

    /**
     * metodo che imposta i dati dell'ordine all'interno
     * @param Mage_Sales_Model_Order $order
     */
    public function setOrder(Mage_Sales_Model_Order $order){

        /**@var $gestpay EasyNolo_BancaSellaPro_Model_Gestpay */
        $gestpay = $order->getPayment()->getMethodInstance();
        $total = $gestpay->getTotalByOrder($order);

        if($gestpay instanceof EasyNolo_BancaSellaPro_Model_Gestpay){

            $this->setData('shopLogin', $gestpay->getMerchantId() );
            $this->setData('shopTransactionId', $order->getIncrementId() );
            $this->setData('uicCode', $gestpay->getCurrency() );
            $this->setData('languageId', $gestpay->getLanguage() );
            $this->setData('amount', round($total, 2) );
        }

    }

    public function setInfoBeforeOrder(EasyNolo_BancaSellaPro_Model_Gestpay $method){

        $quote = $method->getQuote();
        $total = $method->getTotalByOrder($quote);
        $orderId = $method->getFutureOrderId();

        $this->setData('shopLogin', $method->getMerchantId() );
        //se è un pagamento ricorrente e il metodo puo gestire i profili ricorrenti
        if(
            $method->canManageRecurringProfiles() &&
            Mage::helper('easynolo_bancasellapro/recurringprofile')->isRecurringProfile($quote)
        ){
            $recurringProfile = Mage::helper('easynolo_bancasellapro/recurringprofile')->getRecurringProfileByQuote($quote);
            //il primo pagamento deve essere fatto calcolando l'init amount piu il costo della trial/billing_amount

            if(isset($recurringProfile['trial_billing_amount']) && $recurringProfile['trial_billing_amount'] > 0){
                $amount = $recurringProfile['trial_billing_amount'];
            }else{
                $amount = $recurringProfile['billing_amount'];
            }

            if (isset($recurringProfile['init_amount'])){
                $amount += $recurringProfile['init_amount'];
            }

            if(isset($recurringProfile['shipping_amount'])){
                $amount+= $recurringProfile['shipping_amount'];
            }
            if(isset($recurringProfile['tax_amount'])){
                $amount+=$recurringProfile['tax_amount'];
            }

            $this->setData('amount', $amount);

            $this->setRecurringProfile(true);
        }else{

            $this->setData('amount', round($total, 2) );
        }
        $this->setData('shopTransactionId',$orderId );
        $this->setData('uicCode', $method->getCurrency() );
        $this->setData('languageId', $method->getLanguage() );

    }


    /**
     * metodo che restituisce i parametri per creare la stringa criptata per effettuare una richiesta di pagamento a bancasella
     * @return array
     */
    public function getParamToEncrypt(){
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('Imposto i parametri da inviare all\'encrypt');

        $param = array();
        $param['shopLogin'] =  $this->getData('shopLogin');
        $param['shopTransactionId'] =  $this->getData('shopTransactionId');
        $param['uicCode'] =  $this->getData('uicCode');

        //se il pagamento è ricorrente, deve essere richiesto anche il token
        if($this->hasRecurringProfile() && $this->getRecurringProfile()==true){
            $param['requestToken'] = 'MASKEDPAN';
        }

        if($this->getData('languageId')!=0){
            $param['languageId'] =  $this->getData('languageId');
        }
        $param['amount'] = $this->getData('amount');

        $_helper->log($param);

        return $param;
    }

    /**
     * Metodo che restituisce i dati da inviare per decriptare un pagamento
     * @return array
     */
    public function getParamToDecrypt(){
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('Imposto i parametri da inviare al decrypt');

        $param = array();
        $param['shopLogin'] =  $this->getParamA();
        $param['CryptedString'] =  $this->getParamB();

        $_helper->log($param);

        return $param;
    }

    /**
     * metodo che importa i risultati dell'encrypt
     * @param $result
     */
    public function setResponseEncrypt($result){

        $realResult = simplexml_load_string($result->EncryptResult->any);

        $this->setTransactionType((string)$realResult->TransactionType);
        $this->setTransactionResult((string)$realResult->TransactionResult);
        $this->setErrorCode((string)$realResult->ErrorCode);
        $this->setErrorDescription((string)$realResult->ErrorDescription);

        if($this->getTransactionResult() == 'OK')
        {
            $this->setCryptDecryptString((string)$realResult->CryptDecryptString);
        }
        else
        {
            Mage::throwException($this->getErrorDescription());
        }
    }
    /**
     * metodo che importa i risultati del decrypt
     * @param $result
     */
    public function setResponseDecrypt($result){

        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('Salvo i parametri decriptati');

        $realResult = simplexml_load_string($result->DecryptResult->any);

        $this->setTransactionType((string)$realResult->TransactionType);
        $this->setTransactionResult((string)$realResult->TransactionResult);
        $this->setErrorCode((string)$realResult->ErrorCode);
        $this->setErrorDescription((string)$realResult->ErrorDescription);


        $this->setShopTransactionID((string)$realResult->ShopTransactionID);
        $this->setBankTransactionID((string)$realResult->BankTransactionID);
        $this->setAuthorizationCode((string)$realResult->AuthorizationCode);
        $this->setCurrency((string)$realResult->Currency);
        $this->setAmount((string)$realResult->Amount);
        $this->setCountry((string)$realResult->Country);
        $this->setCustomInfo((string)$realResult->CustomInfo);
        $this->setBuyerName((string)$realResult->Buyer->BuyerName);
        $this->setBuyerEmail((string)$realResult->Buyer->BuyerEmail);
        $this->setTDLevel((string)$realResult->TDLevel);
        $this->setAlertCode((string)$realResult->AlertCode);

        $this->setAlertDescription((string)$realResult->AlertDescription);
        $this->setVbVRisp((string)$realResult->VbVRisp);
        $this->setVbVBuyer((string)$realResult->VbVBuyer);
        $this->setVbVFlag((string)$realResult->VbVFlag);
        $this->setTransactionKey((string)$realResult->TransactionKey);
        $this->setPaymentMethod((string)$realResult->PaymentMethod);

        //token
        $this->setToken((string)$realResult->TOKEN);
        $this->setTokenExpiryMonth((string)$realResult->TokenExpiryMonth);
        $this->setTokenExpiryYear((string)$realResult->TokenExpiryYear);

        $_helper->log($this->getData());

    }

    public function setDecryptParam($a , $b){
        $this->setParamA($a);
        $this->setParamB($b);
    }


    /**
     * Metodo per sapere in modo veloce se il pagamento è stato effettuato
     * @return bool true se lo stato è pagato oppure in attesa di bonifico, false altrimenti
     */
    public function getFastResultPayment(){
        if(!$this->getTransactionResult() || $this->getTransactionResult() == 'KO')
            return false;
        return true;
    }
}