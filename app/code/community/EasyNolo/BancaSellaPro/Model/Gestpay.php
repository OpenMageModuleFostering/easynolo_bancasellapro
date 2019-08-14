<?php

/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */
class EasyNolo_BancaSellaPro_Model_Gestpay extends Mage_Payment_Model_Method_Abstract
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{

    const RELATIVE_IFRAME_URL = '/pagam/JavaScript/js_GestPay.js';

    //url per i pagamenti reali
    const REAL_PAYMENT_URL = 'https://ecomm.sella.it';
    const REAL_PAYMENT_URL_WSDL = 'https://ecomms2s.sella.it';

    const TEST_PAYMENT_URL = 'https://testecomm.sella.it';

    const PAGE_REDIRECT_TO_PAYMENT = '/pagam/pagam.aspx';

    const METHOD_CODE = "gestpaypro";

    //pagamento minimo consentito
    const MINIMUM_AMOUNT = 0.01;

    const PAYMENT_INFO_TOKEN = 'easynolo_bancasellapro_payment_token';


    protected $_code = EasyNolo_BancaSellaPro_Model_Gestpay::METHOD_CODE;
    protected $_formBlockType = 'easynolo_bancasellapro/form';


    protected $_order;

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;

    public function canManageRecurringProfiles()
    {
        //solo se è abilitata la tokenizzazione posso gestire i recurring profiles
        return parent::canManageRecurringProfiles() && $this->getConfigData('tokenization');
    }

    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * Restituisce l'url quando il pagamento va a buon fine
     * @return string
     */
    protected function getSuccessURL()
    {
        return Mage::getUrl('easynolo_bancasellapro/gestpay/success', array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }

    /**
     * Metodo che restituisce l'url dove reindirizzare l'utente dopo la verifica 3dsecure
     * @return string
     */
    public function getPage3d()
    {
        return Mage::getUrl('easynolo_bancasellapro/gestpay/confirm3d', array('_secure' => Mage::app()->getStore()->isCurrentlySecure()));
    }


    /**
     * Restituisce l'url dove effettuare il redirect sul sito di bancasella
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('easynolo_bancasellapro/gestpay/redirect', array('_secure' => true));
    }


    /**
     * metodo che riserva un id ordine e lo restituisce
     * @return mixed identificativo dell'ordine
     */
    public function getFutureOrderId()
    {
        $info = $this->getInfoInstance();
        $orderId = $info->getQuote()->getReservedOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($order->getId()) {
                //esiste gia un ordine con questo increment id, quindi ne creo un altro
                $info->getQuote()->reserveOrderId()->save();
            }
        } else {
            $info->getQuote()->reserveOrderId()->save();
        }
        return $info->getQuote()->getReservedOrderId();
    }

    /**
     * Metodo che restituisce il quote tramite l'istanza info del metodo di pagamento
     * @return mixed
     */
    public function getQuote()
    {
        $info = $this->getInfoInstance();
        return $info->getQuote();
    }

    /**
     * Metodo che restituisce il merchant id impostato nello store
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->getConfigData('merchant_id');
    }

    public function getOrderStatusKoUser()
    {
        return $this->getConfigData('order_status_ko_user');
    }

    public function getOrderStatusOkGestPay()
    {
        return $this->getConfigData('order_status_ok_gestpay');
    }

    public function getOrderStatusKoGestPay()
    {
        return $this->getConfigData('order_status_ko_gestpay');
    }

    /**
     * Metodo che restituisce la moneta impostata nello store
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->getConfigData('currency');
    }

    /**
     * Metodo che restituisce la lingua impostata nello store
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getConfigData('language');
    }

    /**
     * Metodo che restitiusce url per il pagaemento su gestpay
     * @return string
     */
    public function getGestpayUrl()
    {
        $domain = $this->getBaseUrlSella();
        return $domain . "/gestpay/pagam.asp";
    }

    /**
     * Restituisce l'url dove effettuare il redirect per il pagamento sul sito di bancasella
     * @return string
     */
    public function getRedirectPagePaymentUrl()
    {
        $domain = $this->getBaseUrlSella();
        return $domain . self::PAGE_REDIRECT_TO_PAYMENT;
    }

    /**
     * Metodo che restituisce l'url dove effettuare la verifica 3dsecure
     * @return string
     */
    public function getAuthPage()
    {
        $url = $this->getBaseUrlSella();
        return $url . '/pagam/pagam3d.aspx';
    }

    /**
     * Restituisce l'url di bancasella a seconda se la modalità di sviluppo è attiva oppure no
     * @return string
     */
    public function getBaseUrlSella()
    {
        $url = self::REAL_PAYMENT_URL;
        if ($this->getConfigData('debug'))
            $url = self::TEST_PAYMENT_URL;
        return $url;
    }

    /**
     * Restituisce l'url del wsdl di bancasella a seconda se la modalità di sviluppo è attiva oppure no
     * @return string
     */
    public function getBaseWSDLUrlSella()
    {
        $url = self::REAL_PAYMENT_URL_WSDL;
        if ($this->getConfigData('debug'))
            $url = self::TEST_PAYMENT_URL;
        return $url;
    }

    /**
     * Restituisce l'url per l'iframe
     * @return string
     */
    public function getIframeUrl()
    {
        return $this->getBaseUrlSella() . self::RELATIVE_IFRAME_URL;
    }

    /**
     * Metodo che indica se la soluzione con iframe è abilitata
     * @return bool true se abilitato, false altrimenti
     */
    public function isIframeEnable()
    {
        $enableIframe = $this->getConfigData('iframe');
        if ($enableIframe) {
            return true;
        }
        return false;

    }

    /**
     * Metodo che si occupa di associare l'encrypted string al pagamento solo se è prensente la dipendenza da IWD_Opc (one page checkout)
     * @param mixed $data
     * @return Mage_Payment_Model_Info|void
     */
    public function assignData($data)
    {
        if ($this->isIframeEnable()) {
            $children = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$children;
            //controllo se ci sono dipendenze nel modulo bancasella
            if (isset($modulesArray['EasyNolo_BancaSellaPro']->depends)) {
                $depens = $modulesArray['EasyNolo_BancaSellaPro']->depends->children();
                $dependsArray = (array)$depens;
                //se presente la dipendenza da IWD devo aggiornare la finestra per il pagamento con iframe
                if (isset($dependsArray['IWD_Opc'])) {
                    //calcolare l encrypt e lo associa al metodo di pagamento solo in questa richiesta
                    $helper = Mage::helper('easynolo_bancasellapro/crypt');
                    $encryptString = $helper->getEncryptStringBeforeOrder($this);
                    $this->setEncryptString($encryptString);
                    Mage::helper('easynolo_bancasellapro')->log('Salvo l encrypt string per ricreare l\'iframe => ' . $encryptString);
                }
            }
        }
    }

    /**
     * Metodo che restituisce il totale dell'ordine da passare al a bancasella
     * @param $order
     * @return float
     */
    public function getTotalByOrder($order)
    {
        //recupero la currency di default
        $defaultCurrency = $this->getConfigData(
            'currency',
            Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId()
        );
        //e quella dello store attuale
        $storeCurrency = $this->getConfigData('currency');

        /** @var $order Mage_Sales_Model_Order */
        //se sono diverse
        if ($defaultCurrency != $storeCurrency) {
            //si deve utilizzare la valuta dello store
            return $order->getGrandTotal();
        } else {
            //se sono uguali o hanno la stessa configurazione oppure è stato scelto solo di usare il pagamento di default

            //quindi restituisco il base grand total
            return $order->getBaseGrandTotal();
        }

    }

    /**
     * Validate data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @throws Mage_Core_Exception
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        //metodo per validare il profilo ricorrente
        //non è necessario farlo perché bancasella non ha i pagamenti ricorrenti, e i totali vendono controllati nel metodo chiamante
        return true;
    }

    /**
     * Submit to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(
        Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    )
    {

        /** @var EasyNolo_Bancasellapro_Model_Token $token */
        $token = Mage::getModel('easynolo_bancasellapro/token')
            ->getCollection()
            ->addProfileToFilter($profile)
            //questo non serve perché se il token non è valido va sospeso il profilo, mentre se non esistono token (neanche scaduti)
            //è un informazione rilevante perché indica che non è stato fatto il primo pagamento
            //->addValidDateFilter()
            ->getFirstItem();

        if (!$token->getId()) {
            //primo pagamento, non possiamo fare niente, dobbiamo creare l'orine e il token per i futuri pagamenti
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING);

            $productItemInfo = $this->_getProductItemInfoToInitialPayment($profile);

            //creo l'ordine impostando l'increment id riservato
            /** @var Mage_Sales_Model_Order $order */
            $order = $profile->createOrder($productItemInfo);
            $order->setIncrementId($this->getFutureOrderId());

            //imposto lo stato new per sicurezza, in quanto è presente un controllo che indica che la chiamata s2s non
            //è stata fatta se lo stato è new
            $order->setState(
                Mage_Sales_Model_Order::STATE_NEW,
                true,
                Mage::helper('easynolo_bancasellapro')->__('First payment to obtain the credit card token.')
            );

            $order->save();
            //il pagamento dell'ordine avviene dal browser dell'utente

            //aggiungo l'ordine al profile
            $profile->addOrderRelation($order->getId());

            //in questo caso non serve fare il save, perché il metodo è stato chiamato dal submit generale del profile
        } else {
            //se il token esiste allora questo è un pagamento che viene fatto dal server
            //controllo ed eseguo il pagamento
            $this->_checkAndExecutePayment($profile);
        }
    }


    /**
     * Method to check and execute the payment for the profile
     * @param $recurringProfile
     */
    protected function _checkAndExecutePayment($recurringProfile)
    {
        $_helper = Mage::helper('easynolo_bancasellapro');
        $helperRecurringPayment = Mage::helper('easynolo_bancasellapro/recurringprofile');

        /** @var $recurringProfile Mage_Sales_Model_Recurring_Profile */
        $_helper->log('----------------------------------------');
        $_helper->log('controllo profile con id: ' . $recurringProfile->getId());

        $lastUpdated = $recurringProfile->getUpdatedAt();
        $startDate = $recurringProfile->getStartDatetime();
        $periodFrequency = $recurringProfile->getPeriodFrequency();
        $checkDate = null;
        $_helper->log('Ultimo aggiornamento: ' . date(DATE_RFC2822, strtotime($lastUpdated)));

        $productItemInfo = null;
        $now = strtotime(Varien_Date::now(true));

        $_helper->log('Controllo l\'ultima data della trial ');

        //recupero l'ultima data per la trial
        $lastTrialDate = $helperRecurringPayment->getLastDateByUnitAndFrequency($recurringProfile->getTrialPeriodUnit(), $recurringProfile->getTrialPeriodFrequency(), $recurringProfile->getTrialPeriodMaxCycles(), $startDate);

        //se è presente una data di scadenza e non è stata superata oppure non esiste scadenza allora
        if ($lastTrialDate != null && $now <= $lastTrialDate) {
            $_helper->log('Controllo la prossima data per la trial ');

            //recupero la prossima data di ricorrenza della trial
            $trialDate = $helperRecurringPayment->getNexDateByUnitAndFrequency($recurringProfile->getPeriodUnit(), $periodFrequency, $lastUpdated);
            //se la data corrisponde o è inferiore alla data attuale

            if ($now >= $trialDate) {
                $_helper->log('creo l\'item per effettuare il pagamento trial');

                //effettuo il pagamento di una trial
                $productItemInfo = $this->_getProductItemInfoToTrialPayment($recurringProfile);

            }
        } else if ($lastTrialDate == null || $now > $lastTrialDate) {

            //se presente un periodo di prova allora bisogna calcolare la data di scadenza dalla fine del periodo di prova invece di startdate
            if ($lastTrialDate != null) {
                //sostituiamo start date con l'ultima data della trial
                $startDate = $lastTrialDate;
            }
            $_helper->log('Controllo l\'ultima data per il pagamento regolare');

            //recupero la data di scadenza del profilo
            $lastRegularDate = $helperRecurringPayment->getLastDateByUnitAndFrequency($recurringProfile->getPeriodUnit(), $recurringProfile->getPeriodFrequency(), $recurringProfile->getPeriodMaxCycles(), $startDate);

            //se la data è ancora valida oppure non è presente
            if ($lastRegularDate == null || $now <= $lastRegularDate) {
                $_helper->log('Controllo la prossima data per il pagamento regolare');

                //recupero la prossima data di scadenza del profilo
                $checkDate = $helperRecurringPayment->getNexDateByUnitAndFrequency($recurringProfile->getPeriodUnit(), $periodFrequency, $lastUpdated);

                if ($now >= $checkDate) {
                    $_helper->log('creo l\'item per effettuare il pagamento regolare');

                    //creo il prodotto per la trial
                    $productItemInfo = $this->_getProductItemInfoToReguralPayment($recurringProfile);
                } else {
                    // il pagamento non avverra ora non è ancora il momento
                    $_helper->log('Il profilo non deve essere ancora pagato');
                }
            } else if ($now > $lastRegularDate) {
                $_helper->log('Il profilo è scaduto, aggiorno lo stato');

                //imposto lo stato a scaduto
                $recurringProfile->setState(Mage_Sales_Model_Recurring_Profile::STATE_EXPIRED);
                $recurringProfile->save();
            }
        }

        //se è stato creato un prodotto
        if ($productItemInfo != null) {
            try {
                //effettuo la creazione dell'ordine e il pagamento
                $this->createOrderAndExecutePayment($recurringProfile, $productItemInfo);
            } catch (Exception $e) {
                Mage::logException($e);
                $_helper = Mage::helper('easynolo_bancasellapro');
                $_helper->log('Non è stato possibile creare ed eseguire il pagamento per il profilo: ' . $recurringProfile->getId() . '. Controllare exception.log');
            }
        }
    }

    /**
     * Method to execute a payment for a recurring profile using a productInfo
     * @param $recurringProfile
     * @param $productItemInfo
     */
    protected function createOrderAndExecutePayment($recurringProfile, $productItemInfo)
    {
        $order = $recurringProfile->createOrder($productItemInfo);
        $order->save();

        $recurringProfile->addOrderRelation($order->getId());

        //effettuo il pagamento dell'ordine per il profilo
        Mage::helper('easynolo_bancasellapro/recurringprofile')->payOrderOfProfile($order, $recurringProfile);
    }


    /**
     * Check whether can get recurring profile details
     *
     * @return bool
     */
    public function canGetRecurringProfileDetails()
    {
        //bancasella non puo dirci nulla perhcé non gestisce i pagamenti ricorrenti
        return false;
    }

    /**
     * Update data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        //metodo per aggiornare il profilo ricorrente, forse possiamo usarlo per rinnovare il token...
        Mage::log(__METHOD__ . '; Profile #' . $profile->getId(), null, 'Recurring.log', true);
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        //non c'e bisogno di gestire questo perché bancasella non permette di aggiornare gli stati (non ha pagamenti ricorrenti)
        Mage::helper('easynolo_bancasellapro')->log('Richiesto ' . __METHOD__ . ' per il profilo' . $profile->getId());

        if ($profile->hasNewState()) {
            switch ($profile->getNewState()) {
                case Mage_Sales_Model_Recurring_Profile::STATE_CANCELED:
                    Mage::helper('easynolo_bancasellapro')->log('Richiesta cancellazione del profilo: ' . $profile->getId());
                    break;
                case Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED:
                    Mage::helper('easynolo_bancasellapro')->log('Richiesta sospensione del profilo: ' . $profile->getId());
                    break;
                case Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE:
                    Mage::helper('easynolo_bancasellapro')->log('Richiesta attivazione del profilo: ' . $profile->getId());
                    if ($profile->getState() == Mage_Sales_Model_Recurring_Profile::STATE_PENDING) {
                        $helper = Mage::helper('easynolo_bancasellapro');
                        Mage::throwException($helper->__('Unable to update the reccuring profile status for activation.'));
                    }
                    $token = Mage::getModel('easynolo_bancasellapro/token')->getFirstValidTokenForProfile($profile);
                    if (!$token->getId()) {

                        Mage::helper('easynolo_bancasellapro')->log('Creare un nuovo token, il precedente è scaduto: ' . $profile->getId());

                        Mage::app()->getFrontController()->getResponse()->setRedirect(
                            Mage::getUrl('easynolo_bancasellapro/tokenization/newtoken', array('profile' => $profile->getId()))
                        );

                        Mage::app()->getResponse()->sendResponse();
                        exit;

                    }
                    break;
            }
        }
    }

    /**
     * Metodo per creare un itemInfo per il pagamento iniziale del recurring payment
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return Varien_Object
     */
    protected function _getProductItemInfoToInitialPayment(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $productItemInfo = new Varien_Object;
        //il pagamento iniziale non puo essere a zero
        $firstCost = $profile->getTrialBillingAmount() > 0 ? $profile->getTrialBillingAmount() : $profile->getBillingAmount();
        $init = $profile->getInitAmount() + $firstCost;
        $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_INITIAL);
        $productItemInfo->setPrice($init);
        $productItemInfo->setShippingAmount($profile->getShippingAmount());//impostiamo il prezzo della spedizione
        $productItemInfo->setTaxAmount($profile->getTaxAmount());//impostiamo il prezzo delle tasse

        return $productItemInfo;
    }


    protected function _getProductItemInfoToNewTokenPayment(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $productItemInfo = new Varien_Object;
        //impostiamo il prezzo iniziale al minimo pagabile con bancasella.
        $init = self::MINIMUM_AMOUNT;
        $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_INITIAL);
        $productItemInfo->setPrice($init);
        $productItemInfo->setShippingAmount(0);//impostiamo il prezzo della spedizione
        $productItemInfo->setTaxAmount(0);//impostiamo il prezzo delle tasse

        return $productItemInfo;
    }

    /**
     * Method to create ad iteminfo to regular recurring payment
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return Varien_Object
     */
    protected function _getProductItemInfoToReguralPayment(Mage_Payment_Model_Recurring_Profile $profile)
    {

        $productItemInfo = new Varien_Object;

        $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
        $productItemInfo->setPrice($profile->getBillingAmount());
        $productItemInfo->setShippingAmount($profile->getShippingAmount());//impostiamo il prezzo della spedizione
        $productItemInfo->setTaxAmount($profile->getTaxAmount());//impostiamo il prezzo delle tasse

        return $productItemInfo;

    }

    /**
     * Method to create ad iteminfo to regular recurring payment
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @return Varien_Object
     */
    protected function _getProductItemInfoToTrialPayment(Mage_Payment_Model_Recurring_Profile $profile)
    {

        $productItemInfo = new Varien_Object;

        $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_TRIAL);
        $productItemInfo->setPrice($profile->getTrialBillingAmount());
        $productItemInfo->setShippingAmount($profile->getShippingAmount());//impostiamo il prezzo della spedizione
        $productItemInfo->setTaxAmount($profile->getTaxAmount());//impostiamo il prezzo delle tasse

        return $productItemInfo;

    }


    public function createOrderToNewToken($profile)
    {
        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING);

        //creo un ordine per pagare un item regolare e ricreare il token
        $productItemInfo = $this->_getProductItemInfoToReguralPayment($profile);

        //creo l'ordine impostando l'increment id riservato
        /** @var Mage_Sales_Model_Order $order */
        $order = $profile->createOrder($productItemInfo);

        //imposto lo stato new per sicurezza, in quanto è presente un controllo che indica che la chiamata s2s non
        //è stata fatta se lo stato è new
        $order->setState(
            Mage_Sales_Model_Order::STATE_NEW,
            true,
            Mage::helper('easynolo_bancasellapro')->__('First payment to obtain the credit card token.')
        );

        $order->save();
        //il pagamento dell'ordine avviene dal browser dell'utente

        //aggiungo l'ordine al profile
        $profile->addOrderRelation($order->getId());

        $profile->save();

        return $order;
    }

    /**
     * Fetch details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        //metodo chiamato su update del profilo ricorrente
        //l'update è stato disabilitato ma essendo una funziona abstract ha bisogno di essere definita
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $helper = Mage::helper('easynolo_bancasellapro/s2s');
        $helper->capturePayment($payment, $amount);
        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $helper = Mage::helper('easynolo_bancasellapro/s2s');
        $helper->refundPayment($payment, $amount);
        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $helper = Mage::helper('easynolo_bancasellapro/s2s');
        $helper->voidPayment($payment);
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool|void
     */
    public function isAvailable($quote = null)
    {
        $result = parent::isAvailable($quote);
        if ($result && $quote && $quote->getGrandTotal() < self::MINIMUM_AMOUNT) {
            return false;
        }

        if ($result && !extension_loaded('soap')) {
            Mage::logException(Mage::exception('EasyNolo_BancaSellaPro', 'PHP SOAP extension is required.'));
            $_helper = Mage::helper('easynolo_bancasellapro');
            $_helper->log('Non è stato possibile creare il client per il webserver');
            return false;
        }

        return $result;
    }
}