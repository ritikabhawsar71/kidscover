<?php
class Magentothem_Testimonial_FormController extends Mage_Core_Controller_Front_Action {

	const STATUS_ENABLED	= 1;
	const STATUS_DISABLED	= 2;
	const STATUS_PENDING	= 3;
	const XML_PATH_EMAIL_SELECT_TEMPLATE_AFTER_POST = 'testimonial/email_configuration/select_template_post';

	public function indexAction() {
		$this->loadLayout();
		$this->_initLayoutMessages('testimonial/session');
		$this->renderLayout();
	}
	
	public function _getSession() {
		return Mage::getSingleton('testimonial/session');
	}
	
	public function captchaAction() {
		require_once(Mage::getBaseDir('lib').DS.'captcha'.DS.'class.simplecaptcha.php');
		//Background Image
		$config['BackgroundImage'] = Mage::getBaseDir('lib') . DS .'captcha'. DS . "white.png";

		//Background Color- HEX
		$config['BackgroundColor'] = "FFFC00";

		//image height - same as background image
		$config['Height']=30; 

		//image width - same as background image
		$config['Width']=100; 

		//text font size
		$config['Font_Size']=20; 

		//text font style
		$config['Font']= Mage::getBaseDir('lib') . DS .'captcha'. DS . "ARLRDBD.TTF";

		//text angle to the left
		$config['TextMinimumAngle']=15;

		//text angle to the right
		$config['TextMaximumAngle']=45;

		//Text Color - HEX
		$config['TextColor']='000000';

		//Number of Captcha Code Character
		$config['TextLength']=6;

		//Background Image Transparency
		// 0 - Not Visible, 100 - Fully Visible
		$config['Transparency']=70;
		
		$captcha = new SimpleCaptcha($config);
		//Mage::getSingleton('customer/session')->setData('captcha_code', $captcha->Code);
		Mage::getSingleton('testimonial/session')->setData('captcha_code', $captcha->Code);
		
	}
	

	public function save() {
        $model= Mage::getModel('testimonial/testimonial');
		$post=$this->getRequest()->getPost();

		if ($post) {
			//Upload avatar			
			if(isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != ''){
                try{
                    /*Starting upload*/
                    $uploader = new Varien_File_Uploader('avatar');
                    //any extension would work
                    $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
                    $uploader->setAllowRenameFiles(true);
                    //set the file upload mode
                    //false -> get  the file directly in the specified folder
                    //true -> get the file in the product like folders
                    //(file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);
                    
                    //we set media as the upload dir
                    $path = Mage::getBaseDir('media').DS.'magentothem/avatar'.DS;
                    $result = $uploader->save($path,$_FILES['avatar']['name']);
                }catch(Exception $e){
                    echo 'Error Message: '.$e->getMessage();
                }
            }

			//Save to datatabase
			try {
				$model->setData($post);
				
				//To Get the current store 
				$store = Mage::app()->getStore();
				$model->setStores($store->getId());
				
				if(isset($_FILES['avatar']['name']) && $_FILES['avatar']['name'] != ''){
					$model->setAvatar('magentothem/avatar/'.$result['file']);
				}
				$now = Mage::getModel('core/date')->timestamp(now());
				$model->setCreatedTime(date('Y-m-d H:i:s',$now));

				if(Mage::getStoreConfig('testimonial/testimonial_options/approve_testimonial', Mage::app()->getStore())==true) {
					$model->setData('status',self::STATUS_PENDING);
				}
				else {
					$model->setData('status',self::STATUS_ENABLED);
				}
				$model->save();
				
				if(Mage::getSingleton('customer/session')->getCustomer()->getEmail())
					$m_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
				else
					$m_email = $post['email'];
					
				if(Mage::getSingleton('customer/session')->getCustomer()->getLastname())
					$m_name = Mage::getSingleton('customer/session')->getCustomer()->getLastname();
				else
					$m_name = $post['name'];	
				
				if (Mage::getStoreConfig('testimonial/email_configuration/send_email_after_post_testimonial', Mage::app()->getStore())=="1") {
					$to=array('email'=>$m_email, 'name'=>$m_name);
					$this->sendemailAction($to, $templateConfigPath=self::XML_PATH_EMAIL_SELECT_TEMPLATE_AFTER_POST);
				}
						
				$this->_redirect('*/index/thankmessage');	
			}
			catch (Exception $e) {
				echo $e->getMessage();
			}	
		}
		else
		$this->_redirect('');		
	}
	
	public function sendemailAction($to, $templateConfigPath) {
		$post=$this->getRequest()->getPost();
		if(!$to) return;
		$translate=Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);
		$mailTemplate=Mage::getModel('core/email_template');
		$template=Mage::getStoreConfig($templateConfigPath, Mage::app()->getStore()->getId());
		$sendTo=array();
		foreach($to as $recipient) {
			if(is_array($recipient)) {
				$sendTo[]=$recipient;
			}
			else {
				$sendTo[]=array(
					'email'=>$recipient,
					'name'=>null
				);	
			}
		
		}
		foreach ($sendTo as $recipient ) {
			$mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>Mage::app()->getStore()->getId()))
			->sendTransactional(
			$template,
			Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, Mage::app()->getStore()->getId()),
			$recipient['email'],
			$recipient['name'],
			array(  'firstname'=>Mage::getModel('customer/session')->getCustomer()->getFirstname(),
					'customer_name'=>$post['name'],
					'customer_email'=>$post['email'],
					'address'=>$post['address'],
					'website'=>$post['website'],
					'company'=>$post['company'],
					'testimonial'=>$post['testimonial']
			      )
			);
		}
		$translate->setTranslateInline(true);
		return $this;
	}

	public function postAction() {
		if(Mage::getStoreConfig('testimonial/testimonial_options/testimonial_captcha_enabled', Mage::app()->getStore())==true) {
			$code=$this->_getSession()->getCaptchaCode();
			$captcha_code=$_POST['captcha_code'];
			if ($code !=  $captcha_code) {
				Mage::getSingleton('core/session')->addError('The security code entered was incorrect. Please try again!');
				$this->_redirect('*/form');
			}
			else $this->save();
		}
		else $this->save();					
	}

}


