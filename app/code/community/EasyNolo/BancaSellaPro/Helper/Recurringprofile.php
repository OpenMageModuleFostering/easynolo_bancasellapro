<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Helper_Recurringprofile extends EasyNolo_BancaSellaPro_Helper_Baseclient {

    protected $_webserviceClassName ='easynolo_bancasellapro/webservice_wss2s';

    const STATUS_REFUND_TOTAL  = 'refund_bancasella';


    /**
     * Method to check if an order is for a recurring payment and if yes, save the token
     * @param Mage_Sales_Model_Order $order
     * @param $webservice
     */
    public function checkAndSaveToken( Mage_Sales_Model_Order $order, $webservice){

        $profileId = $this->getProfileIdByOrder($order);

        if(false !== $profileId){
            //find a recurring profile
            /** @var Mage_Sales_Model_Recurring_Profile $profile */
            $profile = Mage::getModel('sales/recurring_profile')->load($profileId);

            //controllo se il profile è in attesa
            if($profile->getState()==Mage_Sales_Model_Recurring_Profile::STATE_PENDING){

                switch ($webservice->getTransactionResult()){
                    case EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt::TRANSACTION_RESULT_OK :
                        //se imposto il bank transactionId l'utente puo accedere alla pagina per vedere il profilo ricorrente,
                        //ma la richiesta dei dettagli deve essere disabilitata perché bancasella non utilizza il banktransactionId,
                        //in alternativa potremmo impostare l'id del profilo
                        $profile->setReferenceId($webservice->getBankTransactionId());
                        $profile->setReferenceId($profile->getId());
                        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);

                        /** @var EasyNolo_Bancasellapro_Model_Token $tokenInfo */
                        $tokenInfo = Mage::getModel('easynolo_bancasellapro/token');
                        $tokenInfo->setProfile($profile)
                            ->setTokenInfo(
                                $webservice->getToken(),
                                $webservice->getTokenExpiryMonth(),
                                $webservice->getTokenExpiryYear());
                        $tokenInfo->save();
                        break;
                    case EasyNolo_BancaSellaPro_Model_Webservice_Wscryptdecrypt::TRANSACTION_RESULT_KO :
                        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED);
                        break;
                }
                $profile->save();
            }
        }
    }

    /**
     * Method to getting back the profile id by order
     * @param Mage_Sales_Model_Order $order
     * @return string|false id of the profile or false otherwise
     */
    public function getProfileIdByOrder($order){

        $bind    = array(':order_id' => $order->getId());

        $adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableName = Mage::getSingleton('core/resource')->getTableName('sales/recurring_profile_order');

        $select  = $adapter->select()
            ->from(array('main_table' => $tableName),'profile_id')
            ->where('order_id=:order_id');

        return $adapter->fetchOne($select, $bind);
    }

    public function getOrdersIdsByProfile($profile){

        $bind = array('profile_id'=> $profile->getId());

        $adapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableName = Mage::getSingleton('core/resource')->getTableName('sales/recurring_profile_order');

        $select= $adapter->select()
            ->from(array('main_table'=> $tableName),'order_id')
            ->where('profile_id=:profile_id');

        return $adapter->fetchCol($select,$bind);

    }

    /**
     * Method to check if an order has a recurring progile item
     * @param  Mage_Sales_Model_Order|Mage_Sales_Model_Quote $orderOrQuote
     *
     * @return bool
     */
    public function isRecurringProfile($orderOrQuote){
        //recupero tutti gli item visibili è controllo se l'ordine è di un recurring payment
        foreach ($orderOrQuote->getAllVisibleItems()  as $item) {
            if ($item->getProduct()->getIsRecurring())
                return true;
            //è presente una limitazione per i recurring profile, ovvero ci puo essere solo un item nel carrello.
            //Quindi non serve controllarli tutti
            return false;
        }
        return false;
    }

    /**
     * Method to extract the recurring profile array by quote
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function getRecurringProfileByQuote($quote){
        //recupero tutti gli item visibili è controllo se l'ordine è di un recurring payment
        foreach ($quote->getAllVisibleItems()  as $item) {
            if ($item->getProduct()->getIsRecurring()){
                $option = $item->getOptionByCode(Mage_Payment_Model_Recurring_Profile::PRODUCT_OPTIONS_KEY);
                if( $option == null ){
                    //su magento 1.7 si chiama in un altro mdoo
                    $option = $item->getOptionByCode('info_buyRequest');
                }
                //recupero le opzioni del l'item epr il recurring profile
                $recurringItem =$option->getItem();
                $recurringProfile = $item->getProduct()->getRecurringProfile();
                //nel recurring profile non ci sta il prezzo dell'item, lo aggiungiamo noi per un eventuale analisi del profilo
                $recurringProfile['billing_amount']=$recurringItem->getPrice();//$item->getProduct()->getPrice();

                $recurringProfile['shipping_amount'] = $recurringItem->getShippingAmount();//$quote->getShippingAddress()->getShippingAmount();
                $recurringProfile['tax_amount'] = $recurringItem->getTaxAmount();//$quote->getShippingAddress()->getTaxAmount();
                return $recurringProfile;
            }
        }
        return array();
    }

    public function payOrderOfProfile($order, $profile){
        $_helper= Mage::helper('easynolo_bancasellapro');
        $_helper->log('richiesto pagamento con token per profile id: '.$profile->getId().' e id ordine:'. $order->getId());

        $token= Mage::getModel('easynolo_bancasellapro/token')
            ->getCollection()
            ->addProfileToFilter($profile)
            ->addValidDateFilter()
            ->getFirstItem();

        if($token->getId()){
            //inizializza il webservice
            $webservice = $this->getInitWebservice();
            //imposta l'ordine
            $webservice->setOrder($order);
            //imposta il token
            $webservice->setToken($token);
            //effettua la chiamata
            $result = $this->executePaymentS2S($webservice);

            if(strcmp($result->getErrorCode(),'8006')==0){
                $_helper->log('Attenzione: per poter effettuare i pagamenti è necessario che l\'esercente disabiliti "Verify by visa" e "mastercard secure" sull\'account bancasella');
                Mage::logException(Mage::exception('EasyNolo_BancaSellaPro','Account bancasella non correttamente configurato. Le opzioni .'));
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED);
                $profile->save();
            }else{
                $_helper->log('Analizzo il pagamento tramite token');
                //analizza il risultato
                $helperDecript = Mage::helper('easynolo_bancasellapro/crypt');
                $helperDecript->setStatusOrderByS2SRequest($order,$webservice);
                $method= $order->getPayment()->getMethodInstance();
                if( $order->getStatus()==$method->getOrderStatusOkGestPay()){
                    $_helper->log('Invio email di conferma creazione ordine all\'utente');
                    $order->sendNewOrderEmail();
                }else{
                    $_helper->log('Problema con il pagamento, lo stato dell\'ordine non corrisponde a quello configurato per "pagamento eseguito correttamente".');
                }
                $profile->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());

                $profile->save();
                return true;
            }

        }else{

            $_helper->log('Non è stato trovato un token valido, sospendo il profilo ricorrente');
            //imposto lo stato a scaduto
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED);
            $profile->save();
        }
        return false;
    }


    protected function executePaymentS2S($webService){
        $client = $this->_initClient($webService);
        if(!$client){
            return false;
        }

        $param = $webService->getParamToCallPagamS2S();

        $result = $client->callPagamS2S($param);

        $webService->setResponseCallPagamS2S($result);
        return $webService;
    }


    /**
     * Method to calculate next recurring date
     * @param $unit
     * @param $periodFrequency
     * @param $lastUpdated
     * @return int|null
     */
    public function getNexDateByUnitAndFrequency($unit, $periodFrequency, $lastUpdated){
        /** @var EasyNolo_BancaSellaPro_Helper_Data $_helper */
        $_helper = Mage::helper('easynolo_bancasellapro');
        $checkDate = null;

        if(!is_int($lastUpdated)){
            $lastUpdated= strtotime($lastUpdated);
        }
        //per ognuno dei metodi bisogna effettuare il pagamento se scade oggi
        switch ($unit){
            case Mage_Payment_Model_Recurring_Profile::PERIOD_UNIT_DAY :
                $checkDate = strtotime('+'.$periodFrequency.' day', $lastUpdated);
                $_helper->log('Tipo di periodo "giornaliero", prossima data: '. date ( DATE_RFC2822, $checkDate));
                break;
            case Mage_Payment_Model_Recurring_Profile::PERIOD_UNIT_WEEK :
                $checkDate = strtotime('+'.$periodFrequency.' week', $lastUpdated);
                $_helper->log('Tipo di periodo "settimanale", prossima data: '. date ( DATE_RFC2822, $checkDate));
                break;
            case Mage_Payment_Model_Recurring_Profile::PERIOD_UNIT_SEMI_MONTH : //two weeks
                $twoWeek = $periodFrequency*2;
                $checkDate = strtotime('+'.$twoWeek.' week', $lastUpdated);
                $_helper->log('Tipo di periodo "due settimane", prossima data: '. date ( DATE_RFC2822, $checkDate));
                break;
            case Mage_Payment_Model_Recurring_Profile::PERIOD_UNIT_MONTH :
                $checkDate = strtotime('+'.$periodFrequency.' month', $lastUpdated);
                $_helper->log('Tipo di periodo "mensile", prossima data: '. date ( DATE_RFC2822, $checkDate));
                break;
            case Mage_Payment_Model_Recurring_Profile::PERIOD_UNIT_YEAR :
                $checkDate = strtotime('+'.$periodFrequency.' year', $lastUpdated);
                $_helper->log('Tipo di periodo "annuale", prossima data: '. date ( DATE_RFC2822, $checkDate));
                break;
        }
        return $checkDate;
    }

    /**
     * Methot to calculate the last date of the period
     * @param $unit
     * @param $periodFrequency
     * @param $maxcycles
     * @param $lastUpdated
     * @return int|null
     */
    public function getLastDateByUnitAndFrequency($unit, $periodFrequency, $maxcycles, $lastUpdated){
        if($maxcycles){
            return $this->getNexDateByUnitAndFrequency($unit,$periodFrequency*$maxcycles,$lastUpdated);
        }
        return null;
    }


} 