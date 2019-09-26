<?php

class ModelExtensionPaymentWeepayPayment extends Model {

    public function getMethod($address, $total) {
        $this->load->language('extension/payment/weepay_payment');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('moka_payment_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('weepay_total') > 0 && $this->config->get('weepay_payment_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('weepay_payment_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }
        $status = true;
        $method_data = array();

        if ($status) {
            $method_data = array(
                'code' => 'weepay_payment',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('payment_weepay_payment_sort_order')
            );
        }

        return $method_data;
    }

    public function createRefundItemEntry($data) {
        
    }

    public function createOrderEntry($data) {
        
    }

    public function updateOrderEntry($data, $id) {
        
    }

    public function updateCustomer($customer_id, $card_key, $mokapi) {
        
    }

    public function disableErrorSettings() {
        
    }

}
