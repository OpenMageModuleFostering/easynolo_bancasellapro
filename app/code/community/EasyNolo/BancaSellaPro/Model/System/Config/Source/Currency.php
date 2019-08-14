<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */

class EasyNolo_BancaSellaPro_Model_System_Config_Source_Currency
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>'USD Dollari Usa'),
            array('value' => 2, 'label'=>'GBP Sterlina Gran Bretagna'),
            array('value' => 3, 'label'=>'CHF Franco Svizzero'),
            array('value' => 7, 'label'=>'DKK Corone Danesi'),
            array('value' => 8, 'label'=>'NOK Corona Norvegese'),
            array('value' => 9, 'label'=>'SEK Corona Svedese'),
            array('value' => 12, 'label'=>'CAD Dollari Canadesi'),
            array('value' => 18, 'label'=>'ITL Lira Italiana'),
            array('value' => 71, 'label'=>'JPY Yen Giapponese'),
            array('value' => 103, 'label'=>'HKD Dollaro Hong Kong'),
            array('value' => 234, 'label'=>'BRL Real'),
            array('value' => 242, 'label'=>'EUR Euro'),
        );
    }

}