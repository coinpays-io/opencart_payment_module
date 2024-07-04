<?php

class ModelExtensionPaymentCoinpaysCheckout extends Model
{
    public function install()
    {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "coinpays_iframe_transaction` (
			  `coinpays_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `order_id` INT(11) NOT NULL,
              `merchant_oid` VARCHAR(64) NOT NULL,
			  `total` DECIMAL( 15,4 ) NOT NULL,
			  `total_paid` DECIMAL( 15,4 ) NOT NULL,
			  `status` VARCHAR(255) NULL,
			  `status_message` TEXT NULL,
			  `is_complete` TINYINT(1) NOT NULL,
			  `is_failed` TINYINT(1) NOT NULL,
			  `is_order` TINYINT(1) NOT NULL,
			  `date_added`  DATETIME,
			  `date_updated`  DATETIME,
			  PRIMARY KEY (`coinpays_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "coinpays_iframe_transaction`;");
    }
}