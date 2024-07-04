<?php


namespace coinpays;


class Hash
{
    public function generateHashIframeAPI($params)
    {
        $hash_str = $params['merchant_id'] . $params['user_ip'] . $params['merchant_oid'] . $params['email'] . $params['payment_amount'] . $params['user_basket'];
        return base64_encode(hash_hmac('sha256', $hash_str . $params['merchant_salt'], $params['merchant_key'], true));
    }
}