<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Weepaypayment extends PaymentModule
{

    protected $_html = '';
    protected $_postErrors = array();
    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;
    public $_prestashop = '_ps';
    public $_ModuleVersion = '1.0.1';

    protected $hooks = array(
        'payment',
        'backOfficeHeader',
        'displayAdminOrder',
    );

    public function __construct()
    {
        $this->name = 'weepaypayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'KahveDigital';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('weepay payment gateway');
        $this->description = $this->l('weepay ödeme modülü ile Internet üzerinden müşterilerinize ödeme yöntemleri sunmanın en hızlı ve en kolay yoludur. Sizi karmaşık Sanal POS başvuru işlemlerinden ve bekleme sürelerinden kurtarır.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('displayAdminOrder') || !$this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn') || !$this->registerHook('ModuleRoutes')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            Configuration::deleteByName('WEEPAY_FORM_LIVE_BAYI_ID');
            Configuration::deleteByName('WEEPAY_FORM_LIVE_API_ID');
            Configuration::deleteByName('WEEPAY_FORM_LIVE_SECRET');
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }

            }
        }
        return true;
    }
    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('WEEPAY_FORM_LIVE_API_ID') || !Tools::getValue('WEEPAY_FORM_LIVE_SECRET') || !Tools::getValue('WEEPAY_FORM_LIVE_BAYI_ID')) {
                $this->_postErrors[] = $this->l('Account keys are required.');
            }
        }
    }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('WEEPAY_FORM_LIVE_BAYI_ID', Tools::getValue('WEEPAY_FORM_LIVE_BAYI_ID'));
            Configuration::updateValue('WEEPAY_FORM_LIVE_SECRET', Tools::getValue('WEEPAY_FORM_LIVE_SECRET'));
            Configuration::updateValue('WEEPAY_FORM_LIVE_API_ID', Tools::getValue('WEEPAY_FORM_LIVE_API_ID'));
            Configuration::updateValue('WEEPAY_FORM_CLASS', Tools::getValue('WEEPAY_FORM_CLASS'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }

        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPayment($params)
    {

        $this->context->smarty->assign('response', "");
        $this->context->smarty->assign('form_class', "responsive");
        $this->context->smarty->assign('credit_card', "kredi kartı");
        $this->context->smarty->assign('module_dir', __PS_BASE_URI__);
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
        ));
        return $this->display(__FILE__, 'payment.tpl');

    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('weepay LIVE Bayi ID'),
                        'name' => 'WEEPAY_FORM_LIVE_BAYI_ID',
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('weepay LIVE API ID'),
                        'name' => 'WEEPAY_FORM_LIVE_API_ID',
                        'required' => true,
                    ),

                    array(
                        'type' => 'text',
                        'label' => $this->l('weepay LIVE SECRET'),
                        'name' => 'WEEPAY_FORM_LIVE_SECRET',
                        'required' => true,
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Form Class'),
                        'name' => 'WEEPAY_FORM_CLASS',
                        'values' => array(
                            array(
                                'value' => 'popup',
                                'label' => $this->l('Popup'),
                            ),
                            array(
                                'value' => 'responsive',
                                'label' => $this->l('Responsive'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {

        return array(
            'WEEPAY_FORM_LIVE_BAYI_ID' => Tools::getValue('WEEPAY_FORM_LIVE_BAYI_ID', Configuration::get('WEEPAY_FORM_LIVE_BAYI_ID')),
            'WEEPAY_FORM_LIVE_API_ID' => Tools::getValue('WEEPAY_FORM_LIVE_API_ID', Configuration::get('WEEPAY_FORM_LIVE_API_ID')),
            'WEEPAY_FORM_LIVE_SECRET' => Tools::getValue('WEEPAY_FORM_LIVE_SECRET', Configuration::get('WEEPAY_FORM_LIVE_SECRET')),
            'WEEPAY_FORM_CLASS' => Tools::getValue('WEEPAY_FORM_CLASS', Configuration::get('WEEPAY_FORM_CLASS')),
        );
    }

    public function hookDisplayAdminOrder($params)
    {
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