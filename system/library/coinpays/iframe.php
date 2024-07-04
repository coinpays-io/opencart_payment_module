<?php


namespace coinpays;

use coinpays\Hash;

class Iframe
{
    public $db;
    public $logger;
    public $config;

    private $hash;
    private $category_installment = array();
    private $category_full;

    public function __construct()
    {
        $this->hash = new Hash();
    }

    public function getToken($params)
    {
        $response = array();

        $coinpays_token = $this->hash->generateHashIframeAPI($params);

        $post_val = array(
            'merchant_id' => $params['merchant_id'],
            'user_ip' => $params['user_ip'],
            'lang' => $params['lang'],
            'currency' => $params['currency'],
            'merchant_oid' => $params['merchant_oid'],
            'email' => $params['email'],
            'payment_amount' => $params['payment_amount'],
            'coinpays_token' => $coinpays_token,
            'user_basket' => $params['user_basket'],
            'user_name' => $params['user_name'],
            'user_address' => $params['user_address'],
            'user_phone' => $params['user_phone'],
            'merchant_pending_url' => $params['merchant_pending_url'],
            'test_mode' => 0
        );

        /*
        * XXX: DİKKAT: lokal makinanızda "SSL certificate problem: unable to get local issuer certificate" uyarısı alırsanız eğer
        * aşağıdaki kodu açıp deneyebilirsiniz. ANCAK, güvenlik nedeniyle sunucunuzda (gerçek ortamınızda) bu kodun kapalı kalması çok önemlidir!
        * curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        * */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.coinpays.io/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_val);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        $result = @curl_exec($ch);

        if (curl_errno($ch)) {

            $response['status'] = 'failed';
            $response['status_message'] = 'COINPAYS IFRAME connection error. err: ' . curl_error($ch);

            curl_close($ch);
        } else {

            $result = json_decode($result, 1);

            if ($result['status'] == 'success') {

                $response['status'] = 'success';
                $response['iframe_token'] = $result['token'];
            } else {

                $response['status'] = 'failed';
                $response['status_message'] = $result['reason'];
            }
        }

        return $response;
    }

    public function getBasketMaxInstallment($products, $installment_number, $config)
    {
        $response = array();
        $user_basket = array();

        if ($installment_number != 13) {

            foreach ($products as $product) {
                $user_basket[] = array($product['name'], $product['price'], $product['quantity']);
            }

            $max_installment = in_array($installment_number, range(0, 12)) ? $installment_number : 0;
        } else {

            $installment = array();

            $this->category_installment = $config->get('payment_coinpays_checkout_category_installment');

            foreach ($products as $product) {
                $user_basket[] = array($product['name'], $product['price'], $product['quantity']);
                $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product['product_id'] . "' ORDER BY category_id ASC");

                foreach ($query->rows as $id => $item) {
                    if (array_key_exists($item['category_id'], $this->category_installment)) {
                        $installment[$item['category_id']] = $this->category_installment[$item['category_id']];
                    } else {
                        $installment[$item['category_id']] = $this->categorySearch($item['category_id']);
                    }
                }
            }

            $installment = count(array_diff($installment, array(0))) > 0 ? min(array_diff($installment, array(0))) : 0;
            $max_installment = $installment ? $installment : 0;
        }

        $response['max_installment'] = $max_installment;
        $response['user_basket'] = base64_encode(json_encode($user_basket));

        return $response;
    }

    protected function categorySearch($category_id = 0)
    {
        if (!empty($this->category_full[$category_id]) and array_key_exists($this->category_full[$category_id], $this->category_installment)) {
            $return = $this->category_installment[$this->category_full[$category_id]];
        } else {
            foreach ($this->category_full as $id => $parent) {
                if ($category_id == $id) {
                    if ($parent == 0) {
                        $return = 0;
                    } elseif (array_key_exists($parent, $this->category_installment)) {
                        $return = $this->category_installment[$parent];
                    } else {
                        $return = $this->categorySearch($parent);
                    }
                } else {
                    $return = 0;
                }
            }
        }
        return $return;
    }
}