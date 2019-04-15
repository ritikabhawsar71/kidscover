<?php
class Magentothem_Testimonial_Block_Render_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $url = Mage::getBaseUrl('media').$value;
        $img = "<img src=$url alt=$value width='80' height='80'/>";
        return $img;
    }
}