<?php

namespace Xhshop;

class FrontEndView extends View
{
    private $requiredCustomerData;

    public function __construct()
    {
        parent::__construct();
        $this->templatePath = XHS_TEMPLATES_PATH. 'frontend/';
        $this->themePath = XHS_BASE_PATH . 'theme/frontend/';
    }

    public function setRequiredCustomerData(array $value)
    {
        $this->requiredCustomerData = $value;
    }

    protected function paymentHint()
    {
        if (count($this->payments) == 1) {
            $hint = '';
        } else {
            $hint = $this->labels['choose_payment_mode'];
        }
        if (in_array('payment_mode', $this->missingData)) {
            $hint = '<span class="xhsRequired">'.$hint.'</span>';
        }
        return $hint;
    }

    protected function contactInput($field)
    {
        $html = '';
        $value = '';
        $label = $this->labels[$field];
        if (in_array($field, $this->requiredCustomerData)) {
            $class = 'xhsFormLabel xhsRequired';
        } else {
            $class = 'xhsFormLabel';
        }
        $label = '<label for="'.$field.'" class="' . $class . '">'.$label. ':</label>';
        if (in_array($field, $this->missingData)) {
            $label = '<span class="xhsRequired">'.$label.'</span>';
        }
        if (isset($_SESSION['xhsCustomer']->$field)) {
            $value = $_SESSION['xhsCustomer']->$field;
        }
        switch ($field) {
            case 'zip_code':
                $params['size'] = 6;
//                $params['placeholder'] = $this->labels[$field];
//                $params['class'] = '';
                break;
//            case 'city':
//                $params['class'] = 'xhsContInp';
//                break;
            case 'email':
                $params['type'] = 'email';
                /* fall through */
            default:
                $params['class'] = 'xhsContInp';
//                $params['placeholder'] = $this->labels[$field];
                break;
        }
        $params['id'] = $field;
        if (in_array($field, $this->requiredCustomerData, true)) {
            $params['required'] = 'required';
        }
        $html .= $label;
        $html .= $this->textInputNameValueLabel($field, $value, $params);
        return $html;
    }

    protected function salutationSelectbox()
    {
        if (in_array('salutation', $this->requiredCustomerData)) {
            $class = 'xhsFormLabel xhsRequired';
        } else {
            $class = 'xhsFormLabel';
        }
        $html = '<label for="xhsSalutation" class="' . $class . '">' . $this->labels['salutation'] . ':</label>'
            . '<select name="salutation" id="xhsSalutation" required>';
        $salutations = array('', $this->labels['salutation_misses'],
                $this->labels['salutation_mister'], $this->labels['salutation_x']);
        foreach ($salutations as $salutation) {
            $html .= '<option';
            if ($_SESSION['xhsCustomer']->salutation === $salutation) {
                $html .= ' selected';
            }
            $html .= '>' . $salutation . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    protected function countriesSelectbox()
    {
        if (empty($this->shippingCountries)) {
            return '';
        }
        if (in_array('salutation', $this->requiredCustomerData)) {
            $class = 'xhsFormLabel xhsRequired';
        } else {
            $class = 'xhsFormLabel';
        }
        $html = '<label for="xhsCountries" class="' . $class . '">' . $this->labels['country'] . ':</label>'
        . '<select name="country" id="xhsCountries">';
        foreach ($this->shippingCountries as $country) {
            $html .= "\n\t".'<option';
            if ($_SESSION['xhsCustomer']->country == trim($country)) {
                $html .= ' selected="selected"';
            }
            $html .= '>'.trim($country).'</option>';
        }
        $html .= "\n" . '</select>';
        return $html;
    }

    /**
     *
     * @return <string>
     *
     * TODO: leave url preparation to cms_bridge
     */
    protected function cosHint()
    {
        return $this->linkedPageHint($this->gtcUrl, $this->hints['gtc_confirmation']);
    }

    protected function shippingCostsHint()
    {
        return $this->linkedPageHint($this->shippingCostsUrl, $this->hints['price_info_shipping']);
    }

    public function linkedPageHint($url, $text)
    {
        if ($url) {
            $starttag = sprintf('<a href="%s&print" class="zoom_i xhsCosLnk" target="_blank">', $url);
            $endtag = '</a>';
        } else {
            $starttag = $endtag = '';
        }
        return sprintf($text, $starttag, $endtag);
    }
}
