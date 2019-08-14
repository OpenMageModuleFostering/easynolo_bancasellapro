<?php
/**
 * @author   Easy Nolo <ecommerce@sella.it>
 */

/** @var Mage_Core_Model_Resource_Setup $installer */

$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$tableName = $installer->getTable('easynolo_bancasellapro/token');

if (!$connection->isTableExists($tableName)) {
    $table= $connection->newTable($tableName)
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true,
            ), 'ID')
        ->addColumn('profile_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0',
            ), 'Profile Id')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'unsigned'  => true,
            ), 'Customer ID')
        ->addColumn('token',Varien_Db_Ddl_Table::TYPE_TEXT,16,array(
                'nullable'  => false,
            ),'Token per effettuare i pagamenti con Bancasella')
        ->addColumn('expiry_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
                'nullable'  => false,
            ), 'Data di scadenza del token')
        ->addForeignKey(
            $installer->getFkName(
                'easynolo_bancasellapro/token',
                'profile_id',
                'sales/recurring_profile',
                'profile_id'
            ),
            'profile_id', $installer->getTable('sales/recurring_profile'), 'profile_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey(
            $installer->getFkName(
                'easynolo_bancasellapro/token',
                'customer_id',
                'customer/entity',
                'entity_id'
            ),
            'customer_id', $installer->getTable('customer/entity'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ;

    $connection->createTable($table);
}

$installer->endSetup();