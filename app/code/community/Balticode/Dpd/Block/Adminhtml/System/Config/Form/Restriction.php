<?php
/**
 * 2015 UAB BaltiCode
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License available
 * through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@balticode.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to
 * newer versions in the future.
 *
 *  @author    UAB Balticode Kęstutis Kaleckas
 *  @package   Balticode_DPD
 *  @copyright Copyright (c) 2015 UAB Balticode (http://balticode.com/)
 *  @license   http://www.gnu.org/licenses/gpl-3.0.txt  GPLv3
 */

class Balticode_Dpd_Block_Adminhtml_System_Config_Form_Restriction
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_addRowButtonHtml = array();
    protected $_removeRowButtonHtml = array();

    protected function _getElementName($name)
    {
        $elementId = $this->getElement()->getId();
        return $elementId.'_'.$name;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = '<div class="grid" >';
        $html .= '<table style="display:none">';
        $html .= '<tbody id="'.$this->_getElementName('template').'">';
        $html .= $this->_getRowTemplateHtml('-1');
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<table class="border" cellspacing="0" cellpadding="0">';
        $html .= '<thead>';
        $html .= '<tr class="headings" id="heading">';
        $html .= '<th style="width: 100px;">'.__('Country').'</th>';
        $html .= '<th style="width: 100px;">'.__('Base shipping<br>price').'</th>';
        $html .= '<th style="width: 100px;">'.__('Max package size<br>(HeightxWidthxDepth)').'</th>';
        $html .= '<th style="width: 100px;">'.__('Price for<br>oversize*').'</th>';
        $html .= '<th style="width: 100px;">'.__('Max package<br>weight').'</th>';
        $html .= '<th style="width: 100px;">'.__('Price for<br>overweight*').'</th>';
        $html .= '<th style="width: 100px;">'.__('Free shipping<br>from**').'</th>';
        $html .= '<th style="width: 100px;"></th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody id="'.$this->_getElementName('container').'">';
        $i = 0;

        if ($this->_getValue('country'))
        {
            foreach ($this->_getValue('country') as $i => $f)
            {
                if ($i)
                {
                    $html .= $this->_getRowTemplateHtml($i);
                }
            }
        }
        $html .= '<input id="'.$this->_getElementName('increment').'" type="hidden" value="'.$i.'">';
        $html .= '</tbody>';
        $html .= '<tfoot>';

        $html .= '<tr>';
        $html .= '<td colspan="8" style="text-align:right;">';
        $html .= $this->_getAddRowButtonHtml($this->_getElementName('container'), $this->_getElementName('template'), $this->__('Add combination'));
        $html .= '</td></tr>';
        $html .= '</tfoot>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= $this->_getJs();
        return $html;
    }

    protected function _getJs()
    {
        $html = '<script>';
        $html .= 'function addBlock(template, heading, incrementNumberSelector)';
        $html .= '{';
        $html .= 'var number = document.getElementById(incrementNumberSelector).value;'; //get current value
        $html .= 'number++;'; //incement number
        $html .= 'document.getElementById(incrementNumberSelector).value = number;'; //set value
        $html .= 'var test = $(template).innerHTML;';
        $html .= 'Element.insert($(heading), {bottom: test.replace(/-1/g,number)});';
        $html .= '}';
        $html .= '</script>';
        return $html;
    }

    protected function _getRowTemplateHtml($i = 0)
    {
        $html = '<tr>';
        $html .= '<td>'.$this->_getCountryList('country', $i).'</td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[base_price]['.$i.']" value="'.$this->_getValue('base_price/'.$i).'" style="width:70px;" /></td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[dimensions]['.$i.']" value="'.$this->_getValue('dimensions/'.$i).'" style="width:110px;" /></td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[oversized_price]['.$i.']" value="'.$this->_getValue('oversized_price/'.$i).'" style="width:70px;" /></td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[weight]['.$i.']" value="'.$this->_getValue('weight/'.$i).'" style="width:80px;" /></td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[overweight_price]['.$i.']" value="'.$this->_getValue('overweight_price/'.$i).'" style="width:70px;" /></td>';
        $html .= '<td><input type="text" name="'.$this->getElement()->getName().'[free_from_price]['.$i.']" value="'.$this->_getValue('free_from_price/'.$i).'" style="width:70px;" /></td>';
        $html .= '<td style="vertical-align: middle;">'.$this->_getRemoveRowButtonHtml().'</td>';
        $html .= '</tr>';
        return $html;
    }

    protected function _getDisabled()
    {
        return $this->getElement()->getDisabled() ? ' disabled' : '';
    }

    protected function _getValue($key)
    {
        return $this->getElement()->getData('value/'.$key);
    }

    protected function _getAddRowButtonHtml($container, $template, $title='Add')
    {
        if (!isset($this->_addRowButtonHtml[$container]))
        {
            $this->_addRowButtonHtml[$container] = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('add '.$this->_getDisabled())
                    ->setLabel($this->__($title))
                    ->setOnClick("addBlock('".$template."', '".$container."', '".$this->_getElementName('increment')."')")
                    ->setDisabled($this->_getDisabled())
                    ->toHtml();
        }
        return $this->_addRowButtonHtml[$container];
    }

    protected function _getRemoveRowButtonHtml($selector='tr', $title='Delete')
    {
        if (!$this->_removeRowButtonHtml)
        {
            $this->_removeRowButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('delete v-middle '.$this->_getDisabled())
                    ->setLabel($this->__($title))
                    ->setOnClick("Element.remove($(this).up('".$selector."'))")
                    ->setDisabled($this->_getDisabled())
                    ->toHtml();
        }
        return $this->_removeRowButtonHtml;
    }

    protected function _getCountryList($name, $i)
    {
        $countryList = Mage::getResourceModel('directory/country_collection')
                    ->loadData()
                    ->toOptionArray(false);

        $html = '';
        $html = '<select id="dpd_'.$name.'" class="option-control" style="width: 100px" value="" name="'.$this->getElement()->getName().'['.$name.']['.$i.']" >';
        $selected = '';
        foreach ($countryList as $country)
        {
            if ($this->_getValue($name.'/'.$i) !== null)
                $selected = ($country['value'] == $this->_getValue($name.'/'.$i))?'selected':'';
            $html .= '<option value="'.$country['value'].'" '.$selected.'>'.$country['label'].'</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
