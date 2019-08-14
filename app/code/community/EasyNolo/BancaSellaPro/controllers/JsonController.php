<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

require_once (Mage::getModuleDir('controllers','IWD_Opc').DS.'JsonController.php');

class EasyNolo_BancaSellaPro_JsonController extends IWD_Opc_JsonController {

    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        try {

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {

                $this->loadLayout('checkout_onepage_review');

                $result['review'] = $this->_getReviewHtml();

            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }

            // Modifiche per funzionamento con bancasella pro
            $result = new Varien_Object(array(
                'json'      => $result,
            ));

            Mage::dispatchEvent('iwd_opc_before_send_result_save_payment', array(
                'method' => $this->getOnepage()->getQuote()->getPayment()->getMethodInstance(),
                'result'  => $result
            ));

            $result = $result->getJson();
            //fine modifche funzionamento con bancasellapro

        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        $this->getResponse()->setHeader('Content-type','application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
