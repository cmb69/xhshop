<?php

namespace Xhshop\Payment;

use Xhshop\PaymentModule;

class Paypal extends PaymentModule
{
    private $urls = array(
        'development' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
        'production'  => 'https://www.paypal.com/cgi-bin/webscr'
    );

    public function __construct()
    {
        global $plugin_cf;

        $this->loadLanguage();
        $this->loadSettings();
        $this->settings['currency_code'] = $plugin_cf['xhshop']['shop_currency_code'];
    }

    private function isAvailable()
    {
        return strlen(trim($this->settings['currency_code'])) === 3 && strlen(trim($this->settings['email'])) > 5;
    }

    public function wantsCartItems()
    {
        return true;
    }

    public function getLabel()
    {
        return '<img src="' . XHS_BASE_PATH . 'images/paypal-logo.png">
';
    }

    public function getName()
    {
        return 'paypal';
    }

    public function orderSubmitForm()
    {
        $name = 'pp_' . session_id() . '.temp';
        //$name = 'test';
        $fh   = fopen(XHS_CONTENT_PATH . 'xhshop/tmp_orders/' . $name, "w");
        if (!$fh) {
            die("could not open ");
        }
        $temp = serialize($_SESSION);

        fwrite($fh, $temp) or die("could not write");
        fclose($fh);

        $form = '
<form action="' . $this->urls[$this->settings['sandbox'] ? 'development' : 'production'] . '" method="post">
    <input type="hidden" name="cmd" value="_cart" />
    <input type="hidden" name="upload" value="1" />
    <input type="hidden" name="business" value="' . $this->settings['email'] . '">
    <input type="hidden" name="currency_code" value="' . $this->settings['currency_code'] . '" />
    <input type="hidden" name="lc" value="' . strtoupper(XHS_LANGUAGE) . '" />
    <input type="hidden" name="rm" value="2" />
    <input type="hidden" name="custom" value="' . session_id() . '" />
    <input type="hidden" name="handling_cart" value="' . ($this->settings['fee'] + $this->shipping) . '" />
    <input type="hidden" name="cancel_return" value="' . $_SERVER['HTTP_REFERER'] . '" />
          <input type="hidden" name="notify_url" value="' . $_SERVER['HTTP_REFERER'] . '" />
    <input type="hidden" name="return" value="' . $_SERVER['HTTP_REFERER'] . '" />';

        foreach ($this->cartItems as $item) {
            $name = strip_tags($item['name']);
            $name .= isset($item['variantName']) ? ', ' . $item['variantName'] : '';
            $form .= '
     <input type="hidden" name="item_name_' . $item['itemCounter'] . '" value="' . $name . '" />
     <input type="hidden" name="quantity_' . $item['itemCounter'] . '" value="' . $item['amount'] . '" />
     <input type="hidden" name="amount_' . $item['itemCounter'] . '" value="' . number_format((float) $item['price'], 2, '.', '') . '" />
     <input type="hidden" name="item_number_' . $item['itemCounter'] . '" value="' . $item['itemCounter'] . '" />';
        }
        $form .= '
     <button class="xhsShopButton"><span class="fa fa-paypal fa-fw"></span> ' . $this->language['go_to_paypal'] . '</button>
</form>
';

        return $form;
    }

    public function ipn()
    {
        // read the post from PayPal system and add 'cmd'
        global $xhsController;
        $req = 'cmd=_notify-validate';

        foreach ($_POST as $key => $value) {
            $value = urlencode($value);
            $req .= "&$key=$value";
        }

// post back to PayPal system to validate

        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";

        if ($this->settings['sandbox']) {
            $header .= "Host: www.sandbox.paypal.com:443\r\n";
        } else {
            $header .= "Host: www.paypal.com:443\r\n";
        }
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

        if ($this->settings['sandbox']) {
            $fp = fsockopen('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
        } else {
            $fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
        }

        if (!$fp) {
            /*
             * HTTP-ERROR: Was tun?
             */
            return;
        }

        fputs($fp, $header . $req);
        while (!feof($fp)) {
            $res = fgets($fp, 1024);
            if (strcmp($res, "VERIFIED") == 0) {
                /*
                 *  bei Bedarf pruefen, ob die Bestellung ausgefuehrt werden soll. (Stimmt die Haendler-E-Mail, ...?
                 */
              
                $file = XHS_CONTENT_PATH . 'xhshop/tmp_orders/pp_' . $_POST['custom'];
                if (file_exists($file . '.temp')) {
                    if (!(bool) session_id()) {
                        session_id($_POST['custom']);
                        session_start();
                    }

                    $temp                    = implode("", file($file . '.temp'));
                    $temp                    = unserialize($temp);
                    $_SESSION['xhsCustomer'] = $temp['xhsCustomer'];
                    $_SESSION['xhsOrder']    = $temp['xhsOrder'];
                    rename($file . '.temp', $file . '.sent');
                    $xhsController->finishCheckout();
                } else {
                }
            } elseif (strcmp($res, "INVALID") == 0) {
                /*
                 *  Fehlerbehandlung "ungueltig"
                 */
            }
        }
        fclose($fp);
    }
}
