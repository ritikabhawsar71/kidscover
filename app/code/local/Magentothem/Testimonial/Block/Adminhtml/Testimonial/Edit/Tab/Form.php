<?php

class Magentothem_Testimonial_Block_Adminhtml_Testimonial_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $form->setHtmlIdPrefix('testimonial_');
        $fieldset = $form->addFieldset('testimonial_form', array('legend'=>Mage::helper('testimonial')->__('General Information')));

        if ( Mage::getSingleton('adminhtml/session')->getTestimonialData() )
        {
            $data = Mage::getSingleton('adminhtml/session')->getTestimonialData();
            Mage::getSingleton('adminhtml/session')->setTestimonialData(null);
        } elseif ( Mage::registry('testimonial_data') ) {
            $data = Mage::registry('testimonial_data')->getData();
        }

        $fieldset->addField('name', 'text', array(
            'label'     => Mage::helper('testimonial')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'name',
        ));
		
		if(!Mage::app()->isSingleStoreMode()) {
		  $fieldset->addField('stores','multiselect',array(
			'name'      => 'stores[]',
			'tabindex'           => 1,
			'label'     => Mage::helper('testimonial')->__('Store View'),
			'title'     => Mage::helper('testimonial')->__('Store View'),
			'required'  => true,
			'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
		  ));
		} else {
		  $fieldset->addField('stores', 'hidden', array(
			'name'      => 'stores[]',
			'tabindex'           => 1,
			'value'     => Mage::app()->getStore(true)->getId()
		  ));
		  $model->getStoreId(Mage::app()->getStore(true)->getId());
		}

        $fieldset->addField('email', 'text', array(
            'label'     => Mage::helper('testimonial')->__('Email'),
            'required'  => true,
            'name'      => 'email',
        ));
		
		$fieldset->addField('avatar','image',array(
            'label'     => Mage::helper('testimonial')->__('Avatar'),
            'required'  => true,
            'name'      => 'avatar',
        ));


        $fieldset->addField('website', 'text', array(
            'label'     => Mage::helper('testimonial')->__('Website'),
            'required'  => false,
            'name'      => 'website',
        ));

        $fieldset->addField('company', 'text', array(
            'label'     => Mage::helper('testimonial')->__('Company'),
            'required'  => false,
            'name'      => 'company',
        ));

        $fieldset->addField('address', 'text', array(
            'label'     => Mage::helper('testimonial')->__('Address'),
            'required'  => false,
            'name'      => 'address',
        ));

        $fieldset->addField('testimonial', 'textarea', array(
            'label'     => Mage::helper('testimonial')->__('Testimonial'),
            'required'  => true,
            'name'      => 'testimonial',
        ));



        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('testimonial')->__('Status'),
            'name'      => 'status',
            'values'    => array(
                array(
                    'value'     => 1,
                    'label'     => Mage::helper('testimonial')->__('Approved'),
                ),

                array(
                    'value'     => 2,
                    'label'     => Mage::helper('testimonial')->__('Not Approved'),
                ),

                array(
                    'value'     => 3,
                    'label'     => Mage::helper('testimonial')->__('Pending'),
                ),
            ),
        ));


        $form->setValues($data);
        return parent::_prepareForm();

    }
}