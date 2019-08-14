<?php
/**
 * Class     Tokenization.php
 * @category EasyNolo_BancaSellaPro
 * @package  EasyNolo
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_TokenizationController extends Mage_Core_Controller_Front_Action {

    /**
     *
     * @var Mage_Customer_Model_Session
     */
    protected $_session = null;

    /**
     * Make sure customer is logged in and put it into registry
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        $this->_session = Mage::getSingleton('customer/session');
        if (!$this->_session->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
        Mage::register('current_customer', $this->_session->getCustomer());
    }

    protected function _initProfile()
    {
        /** @var Mage_Sales_Model_Recurring_Profile $profile */
        $profile = Mage::getModel('sales/recurring_profile')->load($this->getRequest()->getParam('profile'));
        //se non esiste il profilo, non è dell'utente corrente oppure non è il metodo gestito dal modulo allora lancio eccezione
        if (!$profile->getId() || $profile->getCustomerId()!= $this->_session->getCustomerId() || $profile->getMethodCode()!= EasyNolo_BancaSellaPro_Model_Gestpay::METHOD_CODE ) {
            Mage::throwException($this->__('Specified profile does not exist.'));
        }

        Mage::register('current_recurring_profile', $profile);
        return $profile;
    }

    public function newTokenAction(){

        $profile = null;
        try {
            $profile = $this->_initProfile();

            if(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED != $profile->getState())
            {
                //se il profilo non è sospeso effettuo il redirect alla pagina dei recurring payment senza dare altri messaggi
                $this->_redirect('sales/recurring_profile');
                return;
            }
            $this->loadLayout();
            $this->renderLayout();

        } catch (Mage_Core_Exception $e) {
            $this->_session->addError($e->getMessage());
            $this->_redirect('sales/recurring_profile');

        } catch (Exception $e) {
            $this->_session->addError($this->__('Failed to update the profile.'));
            Mage::logException($e);
            $this->_redirect('sales/recurring_profile');

        }

    }

    public function disableAction()
    {
        $profile = null;
        try {
            $profile = $this->_initProfile();
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED);
            $profile->save();
            $this->_redirect('sales/recurring_profile/view',
                array(
                    'profile'=> $profile->getId()
                )
            );
        }
        catch(Exception $e){
            $this->_session->addError($this->__('Failed to update the profile.'));
            Mage::logException($e);
            $this->_redirect('sales/recurring_profile');
        }

    }


    public function resultAction(){
        $a = $this->getRequest()->getParam('a',false);
        $b = $this->getRequest()->getParam('b',false);

        $_helper= Mage::helper('easynolo_bancasellapro');


        if(!$a || !$b){
            $_helper->log('Accesso alla pagina per il risultato del pagamento non consentito, mancano i parametri di input');
            $this->norouteAction();
            return;
        }

        Mage::register('bancasella_param_a', $a);
        Mage::register('bancasella_param_b', $b);

        /** @var EasyNolo_BancaSellaPro_Helper_Crypt $helper */
        $helper= Mage::helper('easynolo_bancasellapro/crypt');

        if( $helper->isPaymentOk( $a , $b )){
            $_helper->log('L\'utente ha completato correttamente l\'inserimento dei dati su bancasella');
            $this->_session->addSuccess($this->__('Richiesto aggiornamento del token effettuata con successo'));
            $redirect ='sales/recurring_profile/view';// '*/*/success';
        }
        else{
            $_helper->log('L\'utente ha annullato il pagamento, oppure qualche dato non corrisponde');
            $this->_session->addError($this->__('Richiesto aggiornamento del token non effettuata'));
            $redirect = '*/*/disable';

        }

        $profile = Mage::helper('easynolo_bancasellapro/recurringprofile')->getProfileIdByOrder(Mage::registry('easynolo_bancasellapro_order'));
        $this->_redirect($redirect,array('profile'=>$profile));

        return;

    }

} 