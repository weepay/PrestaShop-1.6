<?php
/*
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class WeepaypaymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */

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
    public function initContent()
    {
        parent::initContent();

        $weepay = new Weepaypayment();
        $cart = $this->context->cart;
        $params = $this->context;

        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        try {
            $context = Context::getContext();
            $cart = $this->context;
            foreach ($_COOKIE as $name => $value) {
                $setCookie = $this->setcookieSameSite($name, $_COOKIE[$name], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);
            }
            $currency_query = 'SELECT * FROM `' . _DB_PREFIX_ . 'currency` WHERE `id_currency`= "' . $params->cookie->id_currency . '"';
            $currency = Db::getInstance()->ExecuteS($currency_query);
            $cart_id = $this->context->cookie->id_cart;
            $product_ids_discount = array();
            $productsIds = array();
            $product_id_contain_discount = array();
            $iso_code = $this->context->language->iso_code;
            $erorr_msg = ($iso_code == "tr") ? 'Girdiğiniz kur değeri sistem tarafından desteklenmemektedir. Lütfen kur değerinin TL, USD, EUR, GBP veya IRR olduğundan emin olunuz.' : 'The current exchange rate you entered is not supported by the system. Please use TRY, USD, EUR, GBP, IRR exchange rate.';
            $locale = ($iso_code == "tr") ? "tr" : "en";

            $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_customer`= "' . $params->cookie->id_customer . '"';
            $guest_user_detail = Db::getInstance()->ExecuteS($query);

            $country_query = 'SELECT * FROM `' . _DB_PREFIX_ . 'country_lang` WHERE `id_country`= "' . $guest_user_detail[0]['id_country'] . '"';
            $guest_country = Db::getInstance()->ExecuteS($country_query);

            $products = $params->cart->getProducts();
            $billing_detail = new Address((int) ($params->cart->id_address_invoice));
            $shipping_detail = new Address((int) ($params->cart->id_address_delivery));
            $order_amount = (float) number_format($params->cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
            $product_sub_total = number_format($params->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS), 2, '.', '');
            $shipping_price = number_format($params->cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');

            $first_name = !empty($params->cookie->customer_firstname) ? $params->cookie->customer_firstname : 'NOT PROVIDED';
            $last_name = !empty($params->cookie->customer_lastname) ? $params->cookie->customer_lastname : 'NOT PROVIDED';
            $email = !empty($params->cookie->email) ? $params->cookie->email : 'NOT PROVIDED';
            $last_login = !empty($guest_user_detail[0]['date_add']) ? $guest_user_detail[0]['date_add'] : 'NOT PROVIDED';
            $registration_date = !empty($guest_user_detail[0]['date_upd']) ? $guest_user_detail[0]['date_upd'] : 'NOT PROVIDED';
            $phone_mobile = !empty($guest_user_detail[0]['phone_mobile']) ? $guest_user_detail[0]['phone_mobile'] : 'NOT PROVIDED';
            $city = !empty($guest_user_detail[0]['city']) ? $guest_user_detail[0]['city'] : 'NOT PROVIDED';
            $country = !empty($$guest_country[0]['name']) ? $guest_country[0]['name'] : 'NOT PROVIDED';
            $postcode = !empty($guest_user_detail[0]['postcode']) ? $guest_user_detail[0]['postcode'] : 'NOT PROVIDED';

            $billing_date_add = !empty($billing_detail->date_add) ? $billing_detail->date_add : 'NOT PROVIDED';
            $billing_date_upd = !empty($billing_detail->date_upd) ? $billing_detail->date_upd : 'NOT PROVIDED';
            $billing_phone_mobile = !empty($billing_detail->phone_mobile) ? $billing_detail->phone_mobile : 'NOT PROVIDED';
            $billing_city = !empty($billing_detail->city) ? $billing_detail->city : 'NOT PROVIDED';
            $billing_country = !empty($billing_detail->country) ? $billing_detail->country : 'NOT PROVIDED';
            $billing_postcode = !empty($billing_detail->postcode) ? $billing_detail->postcode : 'NOT PROVIDED';
            $billing_firstname = !empty($billing_detail->firstname) ? $billing_detail->firstname : 'NOT PROVIDED';
            $billing_lastname = !empty($billing_detail->lastname) ? $billing_detail->lastname : 'NOT PROVIDED';

            $shipping_firstname = !empty($shipping_detail->firstname) ? $shipping_detail->firstname : 'NOT PROVIDED';
            $shipping_lastname = !empty($shipping_detail->lastname) ? $shipping_detail->lastname : 'NOT PROVIDED';
            $shipping_city = !empty($shipping_detail->city) ? $shipping_detail->city : 'NOT PROVIDED';
            $shipping_country = !empty($shipping_detail->country) ? $shipping_detail->country : 'NOT PROVIDED';
            $shipping_postcode = !empty($shipping_detail->postcode) ? $shipping_detail->postcode : 'NOT PROVIDED';

            $weepayArray = array();
            $weepayArray['Auth'] = array(
                'bayiId' => Configuration::get('WEEPAY_FORM_LIVE_BAYI_ID'),
                'apiKey' => Configuration::get('WEEPAY_FORM_LIVE_API_ID'),
                'secretKey' => Configuration::get('WEEPAY_FORM_LIVE_SECRET'),
            );

            $credit_card = ($iso_code == "tr") ? "Kredi Kartı" : "Credit Card";
            $module_dir = __PS_BASE_URI__;

            if ($params->cookie->is_guest == 1) {

                $weepayArray['Customer'] = array(
                    'customerId' => $params->cookie->id_customer,
                    'customerName' => $first_name,
                    'customerSurname' => $last_name,
                    'gsmNumber' => $phone_mobile,
                    'email' => $email,
                    'identityNumber' => '11111111111',
                    'city' => $city,
                    'country' => $country,
                );

                $weepayArray['BillingAddress'] = array(
                    'contactName' => $first_name . ' ' . $last_name,
                    'address' => $guest_user_detail[0]['address1'] . ' ' . $guest_user_detail[0]['address2'],
                    'city' => $city,
                    'country' => $country,
                    'zipCode' => $postcode,
                );

                $weepayArray['ShippingAddress'] = array(
                    'contactName' => $first_name . ' ' . $last_name,
                    'address' => $guest_user_detail[0]['address1'] . ' ' . $guest_user_detail[0]['address2'],
                    'city' => $city,
                    'country' => $country,
                    'zipCode' => $postcode,
                );
            } else {

                $weepayArray['Customer'] = array(
                    'customerId' => $params->cookie->id_customer,
                    'customerName' => $billing_firstname,
                    'customerSurname' => $billing_firstname,
                    'gsmNumber' => $billing_phone_mobile,
                    'email' => $email,
                    'identityNumber' => '11111111111',
                    'city' => $city,
                    'country' => $country,
                );

                $weepayArray['BillingAddress'] = array(
                    'contactName' => $billing_firstname . ' ' . $billing_firstname,
                    'address' => $billing_detail->address1 . ' ' . $billing_detail->address2,
                    'city' => $billing_city,
                    'country' => $billing_country,
                    'zipCode' => $billing_postcode,
                );

                $weepayArray['ShippingAddress'] = array(
                    'contactName' => $shipping_firstname . ' ' . $shipping_lastname,
                    'address' => $shipping_detail->address1 . ' ' . $shipping_detail->address2,
                    'city' => $shipping_city,
                    'country' => $shipping_country,
                    'zipCode' => $shipping_postcode,
                );
            }

            $form_class = Configuration::get('WEEPAY_FORM_CLASS');
            $currency = $currency[0]['iso_code'];
            if ($currency == 'TRY') {
                $currency = "TL";
            }

            $weepayArray['Data'] = array(
                'callBackUrl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=result&fc=module&module=weepaypayment&controller=result',
                'paidPrice' => number_format($order_amount, 2, '.', ''),
                'locale' => $locale,
                'ipAddress' => (string) Tools::getRemoteAddr(),
                'orderId' => $params->cookie->id_cart,
                'description' => "",
                'currency' => $currency,
                'paymentGroup' => 'PRODUCT',
                'paymentSource' => 'PRESTASHOP|' . _PS_VERSION_ . '|' . $weepay->_ModuleVersion,
                'channel' => 'Module',
            );

            foreach ($products as $product) {
                $productsIds[] = $product['id_product'];
            }

            $sql_prod_discount = "SELECT reduction_product, reduction_amount, reduction_percent FROM " . _DB_PREFIX_ . "cart_rule WHERE reduction_product > 0 AND reduction_product IN (" . implode(',', $productsIds) . ")";
            $prod_ids_cart_rule = Db::getInstance()->ExecuteS($sql_prod_discount);

            if (!empty($prod_ids_cart_rule)) {
                foreach ($prod_ids_cart_rule as $key => $value) {
                    $product_id_contain_discount[$value['reduction_product']] = array(
                        'reduction_amount' => $value['reduction_product'],
                        'reduction_percent' => $value['reduction_product'],
                    );
                }
            }

            if ($cart_id) {
                $product_ids_discount = $this->_productDiscountArr($cart_id, $product_id_contain_discount);
            }
            $cart_discount_price = 0;
            $product_ids_array = array_keys($product_ids_discount);

            if (!empty($product_ids_discount['order_specific_discount'])) {
                foreach ($product_ids_discount['order_specific_discount'] as $key => $value) {
                    if ($value['discount_type'] == 'percent') {
                        $cart_discount_price += ($product_sub_total * $value['amount']) / 100;
                    } else {
                        $cart_discount_price += $value['amount'];
                    }
                }
            }

            $total_discount = 0;
            $remaining_discount = 0;
            $shipping_price_per_product = 0;
            $cart_total = 0;
            $items = array();
            foreach ($products as $product) {
                $discount = 0;
                $discount_price = 0;
                $product_price = ($product['price_wt'] * $product['cart_quantity']);

                $category = !empty($product['category']) ? $product['category'] : 'NOT PROVIDED';

                if (in_array($product['id_product'], $product_ids_array)) {
                    if ($product_ids_discount[$product['id_product']]['discount_type'] == 'percent') {
                        $discount_price = ($product_price * (float) $product_ids_discount[$product['id_product']]['amount']) / 100;
                        $product_price = $product_price - $discount_price;
                    } else {
                        $discount_price = $product_ids_discount[$product['id_product']]['amount'];
                        $product_price = $product_price - $discount_price;
                    }
                }

                $discount = $cart_discount_price * ($product_price / $product_sub_total);
                $discount = number_format($discount, 2);
                $product_price -= $discount;

                if ($shipping_price > 0) {
                    if ($product_price < 0) {
                        $prod_price = 0;
                    } else {
                        $prod_price = $product_price;
                    }
                    $shipping_price_per_product = (($product['price_wt'] * $product['cart_quantity']) / $product_sub_total) * $shipping_price;
                    $shipping_price_per_product = number_format($shipping_price_per_product, 2);
                    $product_price = $prod_price + $shipping_price_per_product;
                }

                if ($product_price > 0) {

                    $basketItems = new stdClass();

                    $basketItems->productId = $product['id_product'];
                    $basketItems->productPrice = number_format($product_price, 2, '.', '');
                    $basketItems->name = $product['name'];
                    $product_type = $product['is_virtual'] ? "VIRTUAL" : "PHYSICAL";
                    $basketItems->itemType = $product_type;

                    $cart_total += number_format($product_price, 2, '.', '');
                    $items[] = $basketItems;
                } else {
                    $remaining_discount += abs($product_price);
                }
                $total_discount += $discount;
            }

            $discount_remain = $cart_discount_price - $total_discount;
            $total_price_final = 0;
            if ($discount_remain > 0) {
                foreach ($items as $key => $item) {
                    $product_price = $item->getPrice();
                    $discount = $discount_remain * ($product_price / $cart_total);
                    $product_price -= $discount;
                    if ($product_price > 0) {
                        $item->setPrice(number_format($product_price, 2));
                        $total_price_final += number_format($product_price, 2);
                    } else {
                        unset($items[$key]);
                    }
                }
            } else {
                $total_price_final = $cart_total;
            }

            if ($total_price_final < $order_amount) {
                $diff_price = (float) $order_amount - (float) $total_price_final;
                $diff_price = (float) number_format($diff_price, 2);
                $item_count = count($items);
                $last_item_index = $item_count - 1;
                $prod_price = $items[$last_item_index]->getPrice();
                $product_price = $prod_price + $diff_price;
                $items[$last_item_index]->setPrice($product_price);
                $total_price_final = $total_price_final + $diff_price;
            }

            if ($total_price_final > $order_amount) {
                $diff_price = (float) $total_price_final - (float) $order_amount;
                $diff_price = (float) number_format($diff_price, 2);
                $item_count = count($items);
                $last_item_index = $item_count - 1;
                $prod_price = $items[$last_item_index]->getPrice();
                $product_price = $prod_price - $diff_price;
                $total_price_final = $total_price_final - $diff_price;

                if ($product_price <= 0) {
                    unset($items[$last_item_index]);
                } else {
                    $items[$last_item_index]->setPrice($product_price);
                }
            }

            if (!empty($items)) {

                $weepayArray['Products'] = $items;
                $weepayArray['Data']['paidPrice'] = $total_price_final;
                $liveUrl = "https://api.weepay.co/Payment/PaymentCreate";

                $response = json_decode($this->curlPostExt(json_encode($weepayArray), $liveUrl, true), true);

                if ($response['status'] == 'failure') {
                    $this->error = $response['message'] . $response['errorCode'];
                    $this->context->smarty->assign('error', $this->error);
                    $this->context->smarty->assign('credit_card', $credit_card);
                    $this->response = '';
                }
                if ($response['status'] == 'success') {
                    $this->response = $response['CheckoutFormData'];
                    $this->context->smarty->assign('response', $this->response);
                    $this->context->smarty->assign('form_class', $form_class);
                    $this->context->smarty->assign('credit_card', $credit_card);
                    $this->context->smarty->assign('module_dir', $module_dir);
                    $this->error = '';
                }

                $cart = $this->context->cart;
                $this->context->smarty->assign(array(
                    'nbProducts' => $cart->nbProducts(),
                    'cust_currency' => $cart->id_currency,
                    'currencies' => $this->module->getCurrency((int) $cart->id_currency),
                    'total' => $cart->getOrderTotal(true, Cart::BOTH),
                    'credit_card' => $credit_card,
                    'this_path' => $this->module->getPathUri(),
                    'this_path_bw' => $this->module->getPathUri(),
                    'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',

                ));
                $this->setTemplate('payment_execution.tpl');
            } else {
                $this->context->cookie->zero_total = true;
                $this->smarty->assign('success_url', Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=payment&fc=module&module=weepaypayment&controller=result');

                return $this->display(__FILE__, 'no_payment.tpl');
            }
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

    }
    private function _productDiscountArr($cart_id, $product_id_contain_discount)
    {
        $product_ids_discount = array();
        $cart_product_discounted_ids = array_keys($product_id_contain_discount);
        $cart_rule_query = 'SELECT cr.id_cart,cr.id_cart_rule, crpg.id_product_rule_group  FROM ' . _DB_PREFIX_ . 'cart_cart_rule cr '
        . 'LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_product_rule_group crpg ON cr.id_cart_rule = crpg.id_cart_rule'
        . '  WHERE  cr.id_cart = ' . (int) $cart_id;
        $cart_rule_result = Db::getInstance()->ExecuteS($cart_rule_query);

        if (!empty($cart_rule_result)) {
            foreach ($cart_rule_result as $key => $value) {
                $id_product_rule_group = $value['id_product_rule_group'];
                $is_product_specific_discount = !empty($id_product_rule_group) ? true : false;
                $id_cart_rule = $value['id_cart_rule'];

                $reduction_amount = $this->_findCartRulePrice($id_cart_rule, $is_product_specific_discount);
                $discount_amount = 0;
                $discount_type = '';
                if (!empty($reduction_amount['reduction_amount']) && (float) $reduction_amount['reduction_amount'] > 0) {
                    $discount_amount = $reduction_amount['reduction_amount'];
                    $discount_type = 'amount';
                } else if (!empty($reduction_amount['reduction_percent'])) {
                    $discount_amount = $reduction_amount['reduction_percent'];
                    $discount_type = 'percent';
                }

                if (!empty($reduction_amount) && $is_product_specific_discount && !empty($cart_product_discounted_ids)) {
                    $product_ids = $this->_getProductRules($id_product_rule_group);
                    foreach ($product_ids as $row) {
                        if (in_array($row['id_item'], $cart_product_discounted_ids)) {
                            $product_ids_discount[$row['id_item']] = array('amount' => $discount_amount, 'discount_type' => $discount_type);
                        }
                    }
                } else {
                    $product_ids_discount['order_specific_discount'][] = array('amount' => $discount_amount, 'discount_type' => $discount_type);
                }
            }
        }
        return $product_ids_discount;
    }
    private function _getProductRules($id_product_rule_group)
    {
        $results = Db::getInstance()->executeS('
		SELECT *
		FROM ' . _DB_PREFIX_ . 'cart_rule_product_rule pr
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_product_rule_value prv ON pr.id_product_rule = prv.id_product_rule
		WHERE pr.id_product_rule_group = ' . (int) $id_product_rule_group);
        return $results;
    }

    private function _findCartRulePrice($id_cart_rule, $is_product_specific_discount)
    {
        if ($is_product_specific_discount) {
            $sql = 'SELECT reduction_amount, reduction_percent FROM ' . _DB_PREFIX_ . 'cart_rule WHERE reduction_product > 0 AND id_cart_rule = ' . (int) $id_cart_rule . ' LIMIT 0,1';
        } else {
            $sql = 'SELECT reduction_amount, reduction_percent FROM ' . _DB_PREFIX_ . 'cart_rule WHERE  id_cart_rule = ' . (int) $id_cart_rule . ' LIMIT 0,1';
        }

        $results = Db::getInstance()->executeS($sql);
        if (!empty($results)) {
            return $results[0];
        }
        return $results;
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
        return false;
    }

}