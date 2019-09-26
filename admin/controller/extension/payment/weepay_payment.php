<?php

error_reporting(0);

class ControllerExtensionPaymentWeepayPayment extends Controller
{

    private $error = array();
    private $base_url = "";
    private $order_prefix = "opencart20X_";
    private $module_version = "2.2.0.0";

    public function index()
    {
        $this->language->load('extension/payment/weepay_payment');
        $this->load->model('extension/payment/weepay_payment');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_weepay_payment', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('heading_title');
        $data['link_title'] = $this->language->get('text_link');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_bayiid'] = $this->language->get('entry_bayiid');
        $data['entry_api'] = $this->language->get('entry_api');
        $data['entry_secret'] = $this->language->get('entry_secret');

        $data['entry_installement'] = $this->language->get('entry_installement');

        $data['text_tabapi'] = $this->language->get('text_tabapi');
        $data['text_tababout'] = $this->language->get('text_tababout');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_threed'] = $this->language->get('entry_threed');
        $data['entry_class_responsive'] = $this->language->get('entry_class_responsive');
        $data['entry_class_popup'] = $this->language->get('entry_class_popup');
        $data['entry_form_type'] = $this->language->get('entry_form_type');
        $data['entry_installment_options'] = $this->language->get('entry_installment_options');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_test_mode'] = $this->language->get('entry_test_mode');

        $data['entry_class_normal'] = $this->language->get('entry_class_normal');
        $data['entry_class_onepage'] = $this->language->get('entry_class_onepage');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['order_status_after_payment_tooltip'] = $this->language->get('order_status_after_payment_tooltip');
        $data['order_status_after_cancel_tooltip'] = $this->language->get('order_status_after_cancel_tooltip');
        $data['entry_test_tooltip'] = $this->language->get('entry_test_tooltip');
        $data['entry_cancel_order_status'] = $this->language->get('entry_cancel_order_status');

        $data['message'] = '';
        $data['error_warning'] = '';
        $data['error_version'] = '';

        $error_data_array_key = array(
            'bayiid',
            'api',
            'secret',
        );

        if (isset($this->request->get['update_error'])) {
            $data['error_version'] = $this->language->get('entry_error_version_updated');
        } else {
            $this->load->model('extension/payment/weepay_payment');
            $versionCheck = $this->model_extension_payment_weepay_payment->versionCheck(VERSION, $this->module_version);

            if (!empty($versionCheck['version_status']) and $versionCheck['version_status'] == '1') {
                $data['error_version'] = $this->language->get('entry_error_version');
                $data['weepay_or_text'] = $this->language->get('entry_weepay_or_text');
                $data['weepay_update_button'] = $this->language->get('entry_weepay_update_button');
                $version_updatable = $versionCheck['new_version_id'];
                $data['version_update_link'] = $this->url->link('extension/payment/weepay_payment/update', 'user_token=' . $this->session->data['user_token'] . "&version=$version_updatable", true);
            }
        }

        foreach ($error_data_array_key as $key) {
            $data["error_{$key}"] = isset($this->error[$key]) ? $this->error[$key] : '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false,
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
            'separator' => ' :: ',
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL'),

            'separator' => ' :: ',
        );

        $data['action'] = $this->url->link('extension/payment/weepay_payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $merchant_keys_name_array = array(
            'payment_weepay_payment_bayiid',
            'payment_weepay_payment_api',
            'payment_weepay_payment_secret',
            'payment_weepay_payment_status',
            'payment_weepay_payment_form_type',
            'payment_weepay_payment_order_status_id',
            'payment_weepay_payment_sort_order',
            'payment_weepay_payment_installement',
            'payment_weepay_payment_test_mode',
            'payment_weepay_payment_cancel_order_status_id',
        );

        foreach ($merchant_keys_name_array as $key) {
            $data[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : $this->config->get($key);
        }

        $this->load->model('localisation/order_status');
        if ($data['payment_weepay_payment_order_status_id'] == '') {
            $data['payment_weepay_payment_order_status_id'] = $this->config->get('config_order_status_id');
        }
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/payment/weepay_payment', $data));
    }

    public function update()
    {

        $this->load->model('extension/payment/weepay_payment');
        $this->load->language('extension/payment/weepay_payment');
        $version_updatable = $this->request->get['version'];
        $updated = $this->model_extension_payment_weepay_payment->update($version_updatable);
        if ($updated == 1) {
            $this->load->model('setting/setting');
            $payment_weepay_payment_bayiid = $this->config->get('payment_weepay_payment_bayiid');
            $payment_weepay_payment_api = $this->config->get('payment_weepay_payment_api');
            $payment_weepay_payment_secret = $this->config->get('payment_weepay_payment_secret');
            $payment_weepay_payment_status = $this->config->get('payment_weepay_payment_status');
            $payment_weepay_payment_form_type = $this->config->get('payment_weepay_payment_form_type');
            $payment_weepay_payment_cancel_order_status_id = $this->config->get('payment_weepay_payment_cancel_order_status_id');

            $payment_weepay_payment_order_status_id = $this->config->get('payment_weepay_payment_order_status_id');
            $payment_weepay_payment_test_mode = $this->config->get('payment_weepay_payment_test_mode');
            $payment_weepay_payment_sort_order = $this->config->get('payment_weepay_payment_sort_order');
            $payment_weepay_payment_installement = $this->config->get('payment_weepay_payment_installement');

            $this->session->data['weepay_update'] = 1;
            $this->load->controller('extension/payment/' . 'weepay_payment' . '/uninstall');
            $this->load->controller('extension/payment/' . 'weepay_payment' . '/install');

            $this->config->set('payment_weepay_payment_bayiid', $payment_weepay_payment_bayiid);
            $this->config->set('payment_weepay_payment_api', $payment_weepay_payment_api);
            $this->config->set('payment_weepay_payment_secret', $payment_weepay_payment_secret);
            $this->config->set('payment_weepay_payment_status', $payment_weepay_payment_status);
            $this->config->set('payment_weepay_payment_test_mode', $payment_weepay_payment_test_mode);
            $this->config->set('payment_weepay_payment_form_type', $payment_weepay_payment_form_type);
            $this->config->set('payment_weepay_payment_order_status_id', $payment_weepay_payment_order_status_id);
            $this->config->set('payment_weepay_payment_sort_order', $payment_weepay_payment_sort_order);
            $this->config->set('payment_weepay_payment_installement', $payment_weepay_payment_installement);
            $this->config->set('payment_weepay_payment_cancel_order_status_id', $payment_weepay_payment_cancel_order_status_id);

            unset($this->session->data['weepay_update']);
            $this->load->controller('extension/modification/refresh');
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL'));
        } else {
            $this->response->redirect($this->url->link('extension/payment/weepay_payment', 'user_token=' . $this->session->data['user_token'] . "&update_error=$updated", true));
        }
    }

    public function install()
    {
        $this->load->model('extension/payment/weepay_payment');
        $this->model_extension_payment_weepay_payment->install();
        if (!isset($this->session->data['weepay_update'])) {
            $this->load->controller('extension/modification/refresh');
        }
    }

    public function uninstall()
    {
        $this->load->model('extension/payment/weepay_payment');
        $this->model_extension_payment_weepay_payment->uninstall();
        if (!isset($this->session->data['weepay_update'])) {
            $this->load->controller('extension/modification/refresh');
        }
    }

    public function order()
    {
        $this->language->load('extension/payment/weepay_payment');
        $language_id = (int) $this->config->get('config_language_id');
        $this->data = array();
        $order_id = (int) $this->request->get['order_id'];
        $data['user_token'] = $this->request->get['user_token'];
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_DealerPaymentId'] = $this->language->get('text_DealerPaymentId');
        $data['text_sepet_total'] = $this->language->get('text_sepet_total');
        $data['text_odenen'] = $this->language->get('text_odenen');
        $data['text_komisyon'] = $this->language->get('text_komisyon');
        $data['text_taksit_sayi'] = $this->language->get("text_taksit_sayi");
        $data['text_creditcart'] = $this->language->get('text_creditcart');
        $data['text_rescode'] = $this->language->get('text_rescode');

        $bayiid = $this->config->get('payment_weepay_payment_bayiid');
        $api = $this->config->get('payment_weepay_payment_api');
        $secret = $this->config->get('payment_weepay_payment_secret');

        $weepayArray = array();

        $weepayArray['Aut'] = array(
            'bayi-id' => $bayiid,
            'api-key' => $api,
            'secret-key' => $secret,
        );
        $weepayArray['Data'] = array(
            'OrderID' => $order_id,
        );

        $weepayEndPoint = "https://api.weepay.co/Payment/GetPaymentDetail";
        $result = json_decode($this->curlPostExt(json_encode($weepayArray), $weepayEndPoint, true));

        $data['DealerPaymentId'] = $result->Data->PaymentDetail->DealerPaymentId;
        $data['sepet_total'] = $result->Data->PaymentDetail->Amount;

        $data['komisyon'] = $result->Data->PaymentDetail->DealerCommissionAmount;
        $data['taksit_sayi'] = $result->Data->PaymentDetail->InstallmentNumber;
        $data['creditcart'] = $result->Data->PaymentDetail->CardNumberFirstSix . 'XXX' . $result->Data->PaymentDetail->CardNumberLastFour . ' - ' . $result->Data->PaymentDetail->CardHolderFullName;
        $data['rescode'] = $result->Data->ResultCode . " - " . $result->Data->PaymentTrxDetailList[0]->ResultMessage;

        return $this->load->view('extension/payment/weepay_order', $data);
    }

    private function curlPostExt($data, $url, $json = false)
    {
        $ch = curl_init(); // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        if ($json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // times out after 4s
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
        if ($result = curl_exec($ch)) { // run the whole process
            curl_close($ch);

            return $result;
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/moka_payment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $validation_array = array(
            'bayiid',
            'api',
            'secret',
        );

        foreach ($validation_array as $key) {
            if (empty($this->request->post["payment_weepay_payment_{$key}"])) {
                $this->error[$key] = $this->language->get("error_$key");
            }
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

    private function _addhistory($order_id, $order_status_id, $comment)
    {

        $this->load->model('sale/order');
        $this->model_sale_order->addOrderHistory($order_id, array(
            'order_status_id' => $order_status_id,
            'notify' => 1,
            'comment' => $comment,
        ));

        return true;
    }

}
