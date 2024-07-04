<?php

namespace coinpays;

class Transaction
{
    public $db;
    public $logger;

    public function addTransaction($array, $api_name)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction SET order_id = '" . (int)$array['order_id'] . "', merchant_oid = '" . $this->db->escape($array['merchant_oid']) . "', total = '" . (float)$array['total'] . "', is_order = 1, is_complete = '" . (int)$array['is_complete'] . "', is_failed = '" . (int)$array['is_failed'] . "', date_added = NOW()");

        return $this->db->getLastId();
    }

    public function addTransactionForCallback($array, $api_name)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction SET order_id = '" . (int)$array['order_id'] . "', merchant_oid = '" . $this->db->escape($array['merchant_oid']) . "', total = '" . (float)$array['total'] . "', status = '" . $this->db->escape($array['status']) . "', status_message = '" . $this->db->escape($array['status_message']) . "', is_order = 1, is_complete = '" . (int)$array['is_complete'] . "', is_failed = '" . (int)$array['is_failed'] . "', date_added = NOW(), date_updated = NOW()");

        return $this->db->getLastId();
    }

    public function updateTransactionForCallback($array, $api_name)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction SET total_paid = '" . (float)$array['total_paid'] . "', status = '" . $this->db->escape($array['status']) . "', status_message = '" . $this->db->escape($array['status_message']) . "', is_complete = 1, date_updated = NOW() WHERE merchant_oid = '" . $this->db->escape($array['merchant_oid']) . "'");
    }

    public function getTransactionByMerchantOID($merchant_oid, $api_name, $fetch_all = false)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction WHERE merchant_oid = '" . $this->db->escape($merchant_oid) . "'");

        if ($fetch_all) {
            $query->rows;
        }

        return $query->row;
    }

    public function getTransactionByMerchantOIDByFailed($merchant_oid, $api_name, $fetch_all = false)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction WHERE merchant_oid = '" . $this->db->escape($merchant_oid) . "' AND is_failed = 0");

        if ($fetch_all) {
            $query->rows;
        }

        return $query->row;
    }

    public function getTransactionByOrderId($order_id, $api_name, $fetch_all = false)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "coinpays_" . $this->db->escape($api_name) . "_transaction WHERE order_id = '" . (int)$order_id . "'");

        if ($fetch_all) {
            return $query->rows;
        }

        return $query->row;
    }
}