<?php

class WeepaypaymentResultModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $module_action = Tools::getValue('module_action');
        $action_list = array('result' => 'initResult', 'payment' => 'initPayment');

        if (isset($action_list[$module_action])) {
            $this->{$action_list[$module_action]}();
        }
    }

    public function initResult()
    {


        $weepay = new Weepaypayment();
        $context = Context::getContext();
        $language_iso_code = $context->language->iso_code;
        $locale = ($language_iso_code == "tr") ? "tr" : "en";
        $cart = $context->cart;
        $error_msg = '';

        try {

            $paymentStatus = Tools::getValue('paymentStatus');
            $paymentId = Tools::getValue('paymentId');
            $message = Tools::getValue('message');




            if ($paymentStatus == "false") {
                throw new \Exception($message);
            }


            $cart_total = 0;
            $weepayArray = array();
            $weepayArray['Auth'] = array(
                'bayiId' =>   Configuration::get('WEEPAY_FORM_LIVE_BAYI_ID'),
                'apiKey' =>   Configuration::get('WEEPAY_FORM_LIVE_API_ID'),
                'secretKey' =>   Configuration::get('WEEPAY_FORM_LIVE_SECRET'),
            );

            $weepayArray['Data'] = array(
                'orderId' =>    (int)$cart->id,
            );




            $liveUrl = "https://api.weepay.co/GetPayment/Detail";

            $response =  json_decode($this->curlPostExt(json_encode($weepayArray), $liveUrl, true), true);

            if ($response['paymentStatus'] == "SUCCESS") {

                $cart_total = (float) $cart->getOrderTotal(true, Cart::BOTH);
                $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
                $currency = new Currency((int) ($cart->id_currency));
                $iso_code = ($currency->iso_code) ? $currency->iso_code : '';
                $weepay->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $cart_total, $weepay->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);
                $this->context->smarty->assign(array(
                    'error' => $error_msg,
                    'total' => $total,
                    'currency' => $iso_code,
                    'locale' => $locale,
                ));
                $this->setTemplate('order_result.tpl');
            } else {
                throw new \Exception("Payment Failure");
            }
        } catch (\Exception $ex) {
            $error_msg = $ex->getMessage();

            if (empty($error_msg)) {
                if ($language_iso_code == 'tr') {
                    $error_msg = "Bir hata oluştu, lütfen tekrar deneyin.";
                } else {
                    $error_msg = "Unknown Error, please try again";
                }
            }
            $this->context->smarty->assign(array(
                'error' => $error_msg,
            ));
            $this->setTemplate('order_result.tpl');
        }
    }

    public function initPayment()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        $zero_total = $context->cookie->zero_total;

        $weepay = new Weepaypayment();
        $currency = new Currency((int) ($cart->id_currency));
        $iso_code = ($currency->iso_code) ? $currency->iso_code : '';
        $cart_total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $shipping_toal = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $language_iso_code = $context->language->iso_code;
        if ($cart_total == $shipping_toal && $zero_total) {
            $total = 0;
            $cart_total = 0;
            $error_msg = ($language_iso_code == "tr") ? 'Alışveriş tutarı indirim tutarına eşit olamaz.' : 'Cart total cannot be equal to discount amount.';
            $this->context->smarty->assign(array(
                'error' => $error_msg,
                'total' => $total,
                'currency' => $iso_code,
            ));
            $weepay->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $cart_total, $weepay->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);
            $this->setTemplate('order_result.tpl');
        }
    }
    private function curlPostExt($data, $url, $json = false)
    {
        $ch = curl_init(); // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        if ($json)
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // times out after 4s
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
        if ($result = curl_exec($ch)) { // run the whole process
            curl_close($ch);
            return $result;
        }
        return false;
    }
}
