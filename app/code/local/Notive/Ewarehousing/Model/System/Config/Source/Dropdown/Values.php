<?php

class Notive_Ewarehousing_Model_System_Config_Source_Dropdown_Values
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'salable_stock',
                'label' => 'Salable',
            ),
            array(
                'value' => 'available_stock',
                'label' => 'Available',
            ),
            array(
                'value' => 'fysical_stock',
                'label' => 'Physical',
            ),
        );
    }
}