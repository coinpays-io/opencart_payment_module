<?php

class ControllerExtensionPaymentCoinpaysCheckout extends Controller
{
    private $error = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->library('coinpays');
    }

    public function index()
    {
        $this->load->language('extension/payment/coinpays_checkout');
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_coinpays_checkout', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_settings'] = $this->language->get('text_settings');
        $data['text_general'] = $this->language->get('text_general');
        $data['text_order_status'] = $this->language->get('text_order_status');
        $data['text_module_settings'] = $this->language->get('text_module_settings');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_ins_total'] = $this->language->get('text_ins_total');

        $data['callback_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/coinpays_checkout/callback';

        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_merchant_key'] = $this->language->get('entry_merchant_key');
        $data['entry_merchant_salt'] = $this->language->get('entry_merchant_salt');
        $data['entry_language'] = $this->language->get('entry_language');
        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_module_layout'] = $this->language->get('entry_module_layout');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_payment_complete'] = $this->language->get('entry_payment_complete');
        $data['entry_payment_failed'] = $this->language->get('entry_payment_failed');
        $data['entry_notify_status'] = $this->language->get('entry_notify_status');
        $data['entry_ins_total'] = $this->language->get('entry_ins_total');
        $data['entry_order_total'] = $this->language->get('entry_order_total');
        $data['entry_max_installments'] = $this->language->get('entry_max_installments');

        $data['help_coinpays_checkout'] = $this->language->get('help_coinpays_checkout');
        $data['help_total'] = $this->language->get('help_total');
        $data['help_notify'] = $this->language->get('help_notify');
        $data['help_ins_total'] = $this->language->get('help_ins_total');
        $data['help_order_total'] = $this->language->get('help_order_total');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['errors_message'] = array(
            'warning' => $this->language->get('error_permission'),
            'coinpays_checkout_merchant_id' => $this->language->get('error_coinpays_checkout_merchant_id'),
            'coinpays_checkout_merchant_id_val' => $this->language->get('error_coinpays_checkout_merchant_id_val'),
            'coinpays_checkout_merchant_key' => $this->language->get('error_coinpays_checkout_merchant_key'),
            'coinpays_checkout_merchant_key_len' => $this->language->get('error_coinpays_checkout_merchant_key_len'),
            'coinpays_checkout_merchant_salt' => $this->language->get('error_coinpays_checkout_merchant_salt'),
            'coinpays_checkout_merchant_salt_len' => $this->language->get('error_coinpays_checkout_merchant_salt_len'),
            'coinpays_checkout_order_completed_id' => $this->language->get('error_coinpays_checkout_order_completed_id'),
            'coinpays_checkout_order_canceled_id' => $this->language->get('error_coinpays_checkout_order_canceled_id'),
            'coinpays_checkout_merchant_general' => $this->language->get('error_coinpays_checkout_merchant_general'),
            'coinpays_checkout_installment_number' => $this->language->get('error_coinpays_checkout_installment_number'),
        );

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extensions'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/coinpays_checkout', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/coinpays_checkout', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->request->get['user_token'];

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if ($this->language->get('code') == "tr") {
            $data['language_arr'] = array(0 => 'Otomatik', 1 => 'Türkçe', 2 => 'İngilizce');
        } else {
            $data['language_arr'] = array(0 => 'Automatic', 1 => 'Turkish', 2 => 'English');
        }

        $data['module_layout'] = array(
            'standard' => $this->language->get('text_module_layout_standard'),
            'onepage' => $this->language->get('text_module_layout_one')
        );

        // Posts
        if (isset($this->request->post['payment_coinpays_checkout_merchant_id'])) {
            $data['payment_coinpays_checkout_merchant_id'] = trim($this->request->post['payment_coinpays_checkout_merchant_id']);
        } else {
            $data['payment_coinpays_checkout_merchant_id'] = $this->config->get('payment_coinpays_checkout_merchant_id');
        }

        if (isset($this->request->post['payment_coinpays_checkout_merchant_key'])) {
            $data['payment_coinpays_checkout_merchant_key'] = trim($this->request->post['payment_coinpays_checkout_merchant_key']);
        } else {
            $data['payment_coinpays_checkout_merchant_key'] = $this->config->get('payment_coinpays_checkout_merchant_key');
        }

        if (isset($this->request->post['payment_coinpays_checkout_merchant_salt'])) {
            $data['payment_coinpays_checkout_merchant_salt'] = trim($this->request->post['payment_coinpays_checkout_merchant_salt']);
        } else {
            $data['payment_coinpays_checkout_merchant_salt'] = $this->config->get('payment_coinpays_checkout_merchant_salt');
        }

        if (isset($this->request->post['payment_coinpays_checkout_lang'])) {
            $data['payment_coinpays_checkout_lang'] = $this->request->post['payment_coinpays_checkout_lang'];
        } else {
            $data['payment_coinpays_checkout_lang'] = $this->config->get('payment_coinpays_checkout_lang');
        }

        if (isset($this->request->post['payment_coinpays_checkout_total'])) {
            $data['payment_coinpays_checkout_total'] = $this->request->post['payment_coinpays_checkout_total'];
        } else {
            $data['payment_coinpays_checkout_total'] = $this->config->get('payment_coinpays_checkout_total');
        }

        if (isset($this->request->post['payment_coinpays_checkout_module_layout'])) {
            $data['payment_coinpays_checkout_module_layout'] = $this->request->post['payment_coinpays_checkout_module_layout'];
        } else {
            $data['payment_coinpays_checkout_module_layout'] = $this->config->get('payment_coinpays_checkout_module_layout');
        }

        if (isset($this->request->post['payment_coinpays_checkout_status'])) {
            $data['payment_coinpays_checkout_status'] = $this->request->post['payment_coinpays_checkout_status'];
        } else {
            $data['payment_coinpays_checkout_status'] = $this->config->get('payment_coinpays_checkout_status');
        }

        if (isset($this->request->post['payment_coinpays_checkout_sort_order'])) {
            $data['payment_coinpays_checkout_sort_order'] = $this->request->post['payment_coinpays_checkout_sort_order'];
        } else {
            $data['payment_coinpays_checkout_sort_order'] = $this->config->get('payment_coinpays_checkout_sort_order');
        }

        if (isset($this->request->post['payment_coinpays_checkout_order_completed_id'])) {
            $data['payment_coinpays_checkout_order_completed_id'] = $this->request->post['payment_coinpays_checkout_order_completed_id'];
        } else {
            $data['payment_coinpays_checkout_order_completed_id'] = $this->config->get('payment_coinpays_checkout_order_completed_id');
        }

        if (isset($this->request->post['payment_coinpays_checkout_order_canceled_id'])) {
            $data['payment_coinpays_checkout_order_canceled_id'] = $this->request->post['payment_coinpays_checkout_order_canceled_id'];
        } else {
            $data['payment_coinpays_checkout_order_canceled_id'] = $this->config->get('payment_coinpays_checkout_order_canceled_id');
        }

        if (isset($this->request->post['payment_coinpays_checkout_notify'])) {
            $data['payment_coinpays_checkout_notify'] = $this->request->post['payment_coinpays_checkout_notify'];
        } else {
            $data['payment_coinpays_checkout_notify'] = $this->config->get('payment_coinpays_checkout_notify');
        }

        if (isset($this->request->post['payment_coinpays_checkout_ins_total'])) {
            $data['payment_coinpays_checkout_ins_total'] = $this->request->post['payment_coinpays_checkout_ins_total'];
        } else {
            $data['payment_coinpays_checkout_ins_total'] = $this->config->get('payment_coinpays_checkout_ins_total');
        }

        if (isset($this->request->post['payment_coinpays_checkout_order_total'])) {
            $data['payment_coinpays_checkout_order_total'] = $this->request->post['payment_coinpays_checkout_order_total'];
        } else {
            $data['payment_coinpays_checkout_order_total'] = $this->config->get('payment_coinpays_checkout_order_total');
        }

        if (isset($this->request->post['payment_coinpays_checkout_installment_number'])) {
            $data['payment_coinpays_checkout_installment_number'] = $this->request->post['payment_coinpays_checkout_installment_number'];
        } else {
            if (!$this->config->get('payment_coinpays_checkout_installment_number') or $this->config->get('payment_coinpays_checkout_installment_number') == null) {
                $data['payment_coinpays_checkout_installment_number'] = 0;
            } else {
                $data['payment_coinpays_checkout_installment_number'] = $this->config->get('payment_coinpays_checkout_installment_number');
            }
        }

        $data['errors'] = $this->error;

        $data['coinpays_icon_loader'] = 'view/javascript/coinpays/coinpays_loader.gif';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/coinpays_checkout', $data));
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/coinpays_checkout');

        $data['payment_coinpays_checkout_lang'] = '0';
        $data['payment_coinpays_checkout_notify'] = '0';
        $data['payment_coinpays_checkout_ins_total'] = '0';
        $data['payment_coinpays_checkout_order_total'] = '0';
        $data['payment_coinpays_checkout_geo_zone_id'] = '0';
        $data['payment_coinpays_checkout_total'] = '1';
        $data['payment_coinpays_checkout_order_completed_id'] = '1';
        $data['payment_coinpays_checkout_order_canceled_id'] = '10';
        $data['payment_coinpays_checkout_module_layout'] = 'standard';
        $data['payment_coinpays_checkout_sort_order'] = '1';

        $this->model_extension_payment_coinpays_checkout->install();
        $this->model_setting_setting->editSetting('payment_coinpays_checkout', $data);
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/coinpays_checkout');

        $this->model_extension_payment_coinpays_checkout->uninstall();
        $this->model_setting_setting->deleteSetting('payment_coinpays_checkout');
    }

    public function order()
    {
        if ($this->config->get('payment_coinpays_checkout_status')) {

            $this->load->model('sale/order');
            $this->load->model('localisation/currency');
            $this->load->language('extension/payment/coinpays_checkout');

            $order = $this->model_sale_order->getOrder($this->request->get['order_id']);

            if ($order['payment_code'] != 'coinpays_checkout') {
                return false;
            }

            $data['coinpays_icon_loader'] = 'view/javascript/coinpays/coinpays_loader.gif';
            $data['user_token'] = $this->request->get['user_token'];
            $data['order_id'] = $order['order_id'];

            $this->document->addStyle('view/javascript/coinpays/coinpays.css');

            return $this->load->view('extension/payment/coinpays_checkout_order', $data);
        }
    }

    public function ajaxCategoryBased()
    {
        $json = array();

        $tree = $this->coinpays->categoryParser($this->config->get('config_language_id'));
        $finish = array();
        $this->coinpays->categoryParserClear($tree, 0, array(), $finish);

        $options = $data['payment_coinpays_checkout_category_installment'] = $this->config->get('payment_coinpays_checkout_category_installment');;

        $json['categories'] = $finish;
        $json['result'] = $options;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/coinpays_checkout')) {
            $this->error['warning'] = 1;
        }

        if (!$this->request->post['payment_coinpays_checkout_merchant_id']) {
            $this->error['coinpays_checkout_merchant_id'] = 1;
        } else {
            if (!is_numeric($this->request->post['payment_coinpays_checkout_merchant_id'])) {
                $this->error['coinpays_checkout_merchant_id_val'] = 1;
            }
        }

        if (!$this->request->post['payment_coinpays_checkout_merchant_key']) {
            $this->error['coinpays_checkout_merchant_key'] = 1;
        } else {
            if (strlen($this->request->post['payment_coinpays_checkout_merchant_key']) < 16 || strlen($this->request->post['payment_coinpays_checkout_merchant_key']) > 16) {
                $this->error['coinpays_checkout_merchant_key_len'] = 1;
            }
        }

        if (!$this->request->post['payment_coinpays_checkout_merchant_salt']) {
            $this->error['coinpays_checkout_merchant_salt'] = 1;
        } else {
            if (strlen($this->request->post['payment_coinpays_checkout_merchant_salt']) < 16 || strlen($this->request->post['payment_coinpays_checkout_merchant_salt']) > 16) {
                $this->error['coinpays_checkout_merchant_salt_len'] = 1;
            }
        }

        if (!$this->request->post['payment_coinpays_checkout_order_completed_id']) {
            $this->error['coinpays_checkout_order_completed_id'] = 1;
        }

        if (!$this->request->post['payment_coinpays_checkout_order_canceled_id']) {
            $this->error['coinpays_checkout_order_canceled_id'] = 1;
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}