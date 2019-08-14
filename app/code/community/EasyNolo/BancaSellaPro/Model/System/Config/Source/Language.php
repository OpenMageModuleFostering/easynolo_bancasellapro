<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Model_System_Config_Source_Language
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>'--NOT ENABLED--'),
            array('value' => 1, 'label'=>'Italian'),
            array('value' => 2, 'label'=>'English'),
            array('value' => 3, 'label'=>'Spanish'),
            array('value' => 4, 'label'=>'French'),
            array('value' => 5, 'label'=>'German'),
        );
    }

}