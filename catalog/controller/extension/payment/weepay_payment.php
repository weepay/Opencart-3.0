<?php
error_reporting(0);

class ControllerExtensionPaymentWeepayPayment extends Controller
{

    private $valid_currency = array("TRY", "GBP", "USD", "EUR", "TL");

    public function index()
    {
        //
        $this->load->language('extension/payment/weepay_payment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $cart_total_amount = round($order_info['total'] * $order_info['currency_value'], 2);
        $data['cart_total'] = $cart_total_amount;
        $data['code'] = $this->language->get('code');
        $data['text_credit_card'] = $this->language->get('text_credit_card');
        $data['text_wait'] = $this->language->get('text_wait');
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['continue'] = $this->url->link('checkout/success');
        $data['error_page'] = $this->url->link('checkout/error');
        $data['form_class'] = $this->config->get('payment_weepay_payment_form_type');
        $data['form_type'] = $this->config->get('payment_weepay_payment_checkout_type');
        if (VERSION >= '2.2.0.0') {
            $template_url = 'extension/payment/weepay_payment';
        } else {
            $template_url = 'default/template/extension/payment/weepay_payment';
        }
        return $this->load->view($template_url, $data);

    }
    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
    {

        if (PHP_VERSION_ID < 70300) {

            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        } else {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'samesite' => 'None',
                'secure' => $secure,
                'httponly' => $httponly,
            ]);

        }
    }

    public function checkoutform()
    {
        try {
            $setCookie = $this->setcookieSameSite("PHPSESSID", $_COOKIE['PHPSESSID'], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);
            $data['checkout_form_content'] = '';
            $data['error'] = '';
            $data['form_class'] = $this->config->get('payment_weepay_payment_form_type');
            $data['form_type'] = $this->config->get('payment_weepay_payment_checkout_type');
            $data['continue'] = $this->url->link('checkout/success');
            $data['error_page'] = $this->url->link('checkout/error');
            $data['display_direct_confirm'] = 'no';
            $route_url = 'extension/payment/weepay_payment/callback';
            $callback_url = $this->getSiteUrl() . 'index.php?route=' . $route_url;
            $order_id = $this->session->data['order_id'];
            $unique_conversation_id = (string) $order_id;
            $this->load->language('extension/payment/weepay_payment');
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/weepay_payment')) {
                $this->template = $this->config->get('config_template') . '/template/extension/payment/weepay_payment';
            } else {
                $this->template = 'default/template/extension/payment/weepay_payment';
            }
            $this->load->model('checkout/order');
            $this->load->model('checkout/order');
            $this->load->model('setting/setting');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            if (!in_array($order_info['currency_code'], $this->valid_currency)) {
                throw new \Exception($this->language->get('error_invalid_currency'));
            }
            $cart_total_amount = round($order_info['total'] * $order_info['currency_value'], 2);
            if ($cart_total_amount == 0) {
                $data['display_direct_confirm'] = 'yes';
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($data));
                return true;
            }
            $locale = 'en';
            $siteLang = explode('-', $order_info['language_code']);
            if ($siteLang[0] == 'tr') {
                $locale = 'tr';
            }
            $order_info_firstname = !empty($order_info['firstname']) ? $order_info['firstname'] : "NOT PROVIDED";
            $order_info_lastname = !empty($order_info['lastname']) ? $order_info['lastname'] : "NOT PROVIDED";
            $order_info_telephone = !empty($order_info['telephone']) ? $order_info['telephone'] : "NOT PROVIDED";
            $order_info_email = !empty($order_info['email']) ? $order_info['email'] : "NOT PROVIDED";
            $order_info_ip = !empty($order_info['ip']) ? $order_info['ip'] : "NOT PROVIDED";
            if (function_exists('curl_version')) {

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
                    'CallBackUrl' => $callback_url,
                    'Price' => $cart_total_amount,
                    'Locale' => $locale,
                    'IpAddress' => $order_info_ip,
                    'CustomerNameSurname' => $order_info_firstname . ' ' . $order_info_lastname,
                    'CustomerPhone' => $order_info_telephone,
                    'CustomerEmail' => $order_info_email,
                    'OutSourceID' => $unique_conversation_id,
                    'Description' => !empty($order_info['payment_zone']) ? $order_info['payment_zone'] : "NOT",
                    'Currency' => $this->getCurrencyConstant($order_info['currency_code']),
                    'Channel' => 'Module',
                );
                $endPointUrl = "https://api.weepay.co/Payment/PaymentCheckoutFormCreate/";
                $result = json_decode($this->curlPostExt(json_encode($weepayArray), $endPointUrl, true));
                if ($result->status == "failure") {

                    throw new \Exception($result->message);
                }
            } else {
                $data['error'] = $this->language->get("Error_message_curl");
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($result = array('status' => 'failure', 'message' => 'Curl Connected Failure')));
            }
        } catch (\Exception $exc) {
            $return = $exc->getMessage();
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($result));
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result, true));
    }

    public function callback()
    {
        $server_conn_slug = $this->getServerConnectionSlug();
        $this->load->language('extension/payment/weepay_payment');
        $this->load->model('extension/payment/weepay_payment');
        $this->load->model('checkout/order');
        $postData = $this->request->post;
        try {
            if (empty($postData['isSuccessful'])) {
                throw new \Exception($this->language->get('invalid_request'));
            }
            $order_id = $this->session->data['order_id'];
            if ($postData['isSuccessful'] == 'False') {
                throw new \Exception($postData['resultMessage']);
            } else if ($postData['isSuccessful'] == 'True') {
                $message .= 'Payment ID: ' . $order_id . "\n";
                $Result = $this->GetOrderData($order_id);
                $installment = $Result->Data->PaymentDetail->InstallmentNumber;
                $order_info = $this->model_checkout_order->getOrder($order_id);
                if ($installment > 1) {
                    $this->load->model('checkout/order');
                    $order_total = (array) $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order_id . "' AND code = 'total' ");
                    $last_sort_value = $order_total['row']['sort_order'] - 1;
                    $exchange_rate = $this->currency->getValue($order_info['currency_code']);
                    $new_amount = str_replace(',', '', $Result->Data->PaymentDetail->Amount);
                    $old_amount = str_replace(',', '', $order_info['total'] * $order_info['currency_value']);
                    $installment_fee_variation = ($new_amount - $old_amount) / $exchange_rate;
                    $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" .
                        (int) $order_id . "',code = '" . $this->db->escape('weepay_installement_fee') .
                        "',  title = '" . $this->db->escape('Taksit Komisyonu') . "' , `value` = '" .
                        (float) $installment_fee_variation . "', sort_order = '" . (int) $last_sort_value . "'");
                    $order_total_data = (array) $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order_id . "' AND code != 'total' ");
                    $calculate_total = 0;
                    foreach ($order_total_data['rows'] as $row) {
                        $calculate_total += $row['value'];
                    }
                    $this->db->query("UPDATE " . DB_PREFIX . "order_total SET  `value` = '" . (float) $calculate_total . "' WHERE order_id = '$order_id' AND code = 'total' ");
                    $this->db->query("UPDATE `" . DB_PREFIX . "order` SET total = '" . $calculate_total . "' WHERE order_id = '" . (int) $order_id . "'");
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_weepay_payment_order_status_id'), $message, false);
                    $comment = ' - ' . $Result->Data->PaymentDetail->InstallmentNumber . '  Taksit';
                    $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int) $order_id . "', order_status_id = '" .
                        $this->config->get('payment_weepay_payment_order_status_id') . "', notify = '0', comment = '" .
                        $this->db->escape($comment) . "', date_added = NOW()");
                } else {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_weepay_payment_order_status_id'), $message, false);
                }
                $this->response->redirect($this->url->link('checkout/success', '', $server_conn_slug));
            }
        } catch (\Exception $ex) {
            $resp_msg = $ex->getMessage();
            $resp_msg = !empty($resp_msg) ? $resp_msg : $this->language->get('invalid_request');
            $this->session->data['error'] = $resp_msg;
            $this->response->redirect($this->url->link('checkout/checkout', '', $server_conn_slug));
        }
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

    public function getSiteUrl()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $site_url = is_null($this->config->get('config_ssl')) ? HTTPS_SERVER : $this->config->get('config_ssl');
        } else {
            $site_url = is_null($this->config->get('config_url')) ? HTTP_SERVER : $this->config->get('config_url');
        }
        return $site_url;
    }

    public function getServerConnectionSlug()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $connection = 'SSL';
        } else {
            $connection = 'NONSSL';
        }
        return $connection;
    }

    private function _getCurrencySymbol($currencyCode)
    {
        $currencySymbol = $this->currency->getSymbolLeft($currencyCode);
        if ($currencySymbol == '') {
            $currencySymbol = $this->currency->getSymbolRight($currencyCode);
        } else if ($currencySymbol == '') {
            $currencySymbol = $currencyCode;
        }
        return $currencySymbol;
    }

    public function GetOrderData($id_order)
    {
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
            'OrderID' => $id_order,
        );
        $weepayEndPoint = "https://api.weepay.co/Payment/GetPaymentDetail";
        return json_decode($this->curlPostExt(json_encode($weepayArray), $weepayEndPoint, true));
    }

    public function replaceSpace($veri)
    {
        $veri = str_replace("/s+/", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace("/s/g", "", $veri);
        $veri = str_replace("/s+/g", "", $veri);
        $veri = trim($veri);
        return $veri;
    }

    private function getCurrencyConstant($currencyCode)
    {
        $currency = 'TL';
        switch ($currencyCode) {
            case "TRY":
                $currency = 'TL';
                break;
            case "USD":
                $currency = 'USD';
                break;
            case "GBP":
                $currency = 'GBP';
                break;
            case "EUR":
                $currency = 'EUR';
                break;
        }
        return $currency;
    }

}
