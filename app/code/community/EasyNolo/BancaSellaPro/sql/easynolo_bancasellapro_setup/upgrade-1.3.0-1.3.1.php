<?php
 /**
 * @author   Easy Nolo <ecommerce@sella.it>
 */

$installer = $this;
$installer->startSetup();
$_helper=Mage::helper('easynolo_bancasellapro/recurringprofile');

$status = Mage::getModel('sales/order_status');
$status->setStatus($_helper::STATUS_REFUND_TOTAL);
$status->setLabel('Rimborso pagamento iniziale');
$status->save();

$installer->endSetup();