<?php

class ControllerExtensionPaymentCoinpaysCheckout extends Controller
{
    private $error = array();
    private $oc_version = 'COINPAYSOC3';

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->library('coinpays');
    }

    public function index()
    {
        $data['page_layout'] = $this->config->get('payment_coinpays_checkout_module_layout');

        if ($data['page_layout'] != 'onepage') {
            $data = $this->getToken();
        }

        return $this->load->view('extension/payment/coinpays_checkout', $data);
    }

    public function onepage()
    {
        $json = array();

        if (!isset($this->session->data['order_id'])) {
            return $this->response->redirect($this->url->link('common/home'));
        }

        if (!isset($this->session->data['payment_method'])) {
            return $this->response->redirect($this->url->link('common/home'));
        }

        if ($this->session->data['payment_method']['code'] != 'coinpays_checkout') {
            return $this->response->redirect($this->url->link('common/home'));
        }

        $json['status'] = 'success';

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function form()
    {
        if (!isset($this->session->data['order_id'])) {
            return $this->response->redirect($this->url->link('common/home'));
        }

        if (!isset($this->session->data['payment_method'])) {
            return $this->response->redirect($this->url->link('common/home'));
        }

        if ($this->session->data['payment_method']['code'] != 'coinpays_checkout') {
            return $this->response->redirect($this->url->link('common/home'));
        }

        if ($this->config->get('payment_coinpays_checkout_module_layout') != 'onepage') {
            return $this->response->redirect($this->url->link('common/home'));
        }

        $this->document->setTitle($this->config->get('config_meta_title'));
        $this->document->setDescription($this->config->get('config_meta_description'));
        $this->document->setKeywords($this->config->get('config_meta_keyword'));

        $data = $this->getToken();

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/payment/coinpays_checkout_onepage', $data));
    }

    public function callback()
    {
        if (!isset($_POST) || empty($_POST)) {
            echo 'no post data';
            exit;
        }

        $this->load->model('checkout/order');

        $this->coinpays->chkHash($_POST, 'iframe');

        $this->load->language('extension/payment/coinpays_checkout');

        $this->coinpays->iframeCallback($_POST, $this->oc_version);
    }

    protected function getToken()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/coinpays_checkout');

        if (!isset($this->session->data['order_id'])) {
            return $this->response->redirect($this->url->link('common/home'));
        }

        $coinpays_params = array();
        $data = array();

        // Get Order
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Get Products
        $products = $this->cart->getProducts();

        // Credentials
        $coinpays_params['merchant_id'] = $this->config->get('payment_coinpays_checkout_merchant_id');
        $coinpays_params['merchant_key'] = $this->config->get('payment_coinpays_checkout_merchant_key');
        $coinpays_params['merchant_salt'] = $this->config->get('payment_coinpays_checkout_merchant_salt');

        // User
        $coinpays_params['user_ip'] = $this->getIp() == '::1' || $this->getIp() == '127.0.0.1' ? '85.105.186.196' : $this->getIp();
        $coinpays_params['email'] = $order_info['email'];

        // Basket && Installments
        $basket_installment = $this->coinpays->iframe->getBasketMaxInstallment($products, $this->config->get('payment_coinpays_checkout_installment_number'), $this->config);
        $coinpays_params['user_basket'] = $basket_installment['user_basket'];

        // User Info
        $coinpays_params['user_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
        $coinpays_params['user_address'] = $order_info['payment_address_1'] . ' ' . $order_info['payment_address_2'] . ' ' . $order_info['payment_postcode'] . ' ' . $order_info['payment_city'] . ' ' . $order_info['payment_zone'] . ' ' . $order_info['payment_iso_code_3'];
        $coinpays_params['user_phone'] = $order_info['telephone'];

        // Order
        $coinpays_params['merchant_oid'] = uniqid() . $this->oc_version . $order_info['order_id'];
        $coinpays_params['currency'] = strtoupper($order_info['currency_code']);
        $coinpays_params['payment_amount'] = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100);

        // URLs
        $coinpays_params['merchant_pending_url'] = $this->url->link('checkout/success', '', true);

        // Language
        if ($this->config->get('payment_coinpays_checkout_lang') == 0) {
            $lang_arr = array('tr', 'tr-tr', 'tr_tr', 'turkish', 'turk', 'türkçe', 'turkce', 'try', 'tl');
            $coinpays_params['lang'] = (in_array(strtolower($this->session->data['language']), $lang_arr) == 1 ? 'tr' : 'en');
        } else {
            $coinpays_params['lang'] = ($this->config->get('payment_coinpays_checkout_lang') == 2 ? 'en' : 'tr');
        }

        if (function_exists('curl_version')) {

            $getToken = $this->coinpays->iframe->getToken($coinpays_params);

            if ($getToken['status'] == 'success') {

                // Save Transaction
                $transaction['order_id'] = $order_info['order_id'];
                $transaction['merchant_oid'] = $coinpays_params['merchant_oid'];
                $transaction['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
                $transaction['is_failed'] = 0;
                $transaction['is_complete'] = 0;

                try {

                    if ($this->coinpays->transaction->addTransaction($transaction, 'iframe')) {

                        $data['iframe_token'] = $getToken['iframe_token'];
                    } else {

                        $this->error['error_coinpays_checkout_transaction_save'] = $this->language->get('error_coinpays_checkout_transaction_save');
                    }

                } catch (Exception $exception) {
                    $this->error['error_coinpays_checkout_transaction_install'] = $this->language->get('error_coinpays_checkout_transaction_install');
                }
            } else {
                $this->error['error_coinpays_iframe_failed'] = $this->language->get('error_coinpays_iframe_failed') . $getToken['status_message'];
            }
        } else {
            $this->error['error_coinpays_checkout_curl'] = $this->language->get('error_coinpays_checkout_curl');
        }

        if ($this->error) {
            $data['errors'] = $this->error;
        }

        return $data;
    }

    protected function getIp()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        return $ip;
    }
}