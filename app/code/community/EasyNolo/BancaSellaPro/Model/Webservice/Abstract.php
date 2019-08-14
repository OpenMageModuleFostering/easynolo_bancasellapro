<?php
/**
 * @category EasyNolo
 * @package  EasyNolo_BancaSellaPro
 * @author   Easy Nolo <ecommerce@sella.it>
 */
abstract class EasyNolo_BancaSellaPro_Model_Webservice_Abstract extends Mage_Core_Model_Abstract{

    protected $url_home;

    /**
     * metodo che imposta l'url dell'webservice
     * @param $url
     */
    public function setBaseUrl($url){
        $this->url_home = $url;
    }

    abstract public function getWSUrl();
}