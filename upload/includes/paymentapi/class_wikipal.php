<?php
/*=============================================================================================*\
|| ########################################################################################### ||
|| # Product Name	: WikiPal Payment API Module for vBulletin		Version: 4.X.X
|| # By				: WikiPal.Co									WebSite: www.wikipal.co
|| ########################################################################################### ||
\*=============================================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 20000 $
* @date		$Date: 2012-03-25 01:24:45 +0350 (Sun, 25 March 2012) $
*/
class vB_PaidSubscriptionMethod_wikipal extends vB_PaidSubscriptionMethod
{
	var $supports_recurring = false;	 
	var $display_feedback = true;

	function verify_payment()
	{		
		$this->registry->input->clean_array_gpc('r', array(
			'item'		=> TYPE_STR,			
			'Authority'	=> TYPE_STR,
			'Status'	=> TYPE_STR
		));  
		
		if (!class_exists('SoapClient'))
		{
			$this->error = 'SOAP is not installed';
			return false;
		}
		
		if (!$this->test())
		{
			$this->error = 'Payment processor not configured';
			return false;
		}
		
		$this->transaction_id = $_POST['authority'];
		if(!empty($this->registry->GPC['item']) AND !empty($_POST['authority']))
		{
			$this->paymentinfo = $this->registry->db->query_first("
				SELECT paymentinfo.*, user.username
				FROM " . TABLE_PREFIX . "paymentinfo AS paymentinfo
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE hash = '" . $this->registry->db->escape_string($this->registry->GPC['item']) . "'
			");
			if (!empty($this->paymentinfo) && $_POST['status'] == 1)
			{
				$sub = $this->registry->db->query_first("SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $this->paymentinfo['subscriptionid']);
				$cost = unserialize($sub['cost']);				
				$amount = floor($cost[0][cost][usd]*$this->settings['d2t']);

				$MerchantID 			= $this->settings['zpmid'];
				$Price 					= $amount;
				$Authority 				= $_POST['authority'];
				$InvoiceNumber 			= $_POST['InvoiceNumber'];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, 'http://gatepay.co/webservice/paymentVerify.php');
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
				curl_setopt($curl, CURLOPT_POSTFIELDS, "MerchantID=$MerchantID&Price=$Price&Authority=$Authority");
				curl_setopt($curl, CURLOPT_TIMEOUT, 400);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result = json_decode(curl_exec($curl));
				curl_close($curl);

				if ($result->Status == 100) {
					$this->paymentinfo['currency'] = 'usd';
					$this->paymentinfo['amount'] = $cost[0][cost][usd];				
					$this->type = 1;								
					return true;
				} else {
					$this->error = 'ERR: '. $result->Status;
					return false;
				}
			
			} else {
				$this->error = 'Invalid trasaction';
				return false;	
			}
		}else{		
			$this->error = 'Duplicate transaction.';
			return false;
		}
    }

	function test()
	{	
		if (class_exists('SoapClient')){
			if(!empty($this->settings['zpmid']) AND !empty($this->settings['d2t'])){
				return true;
			}
		}
		return false;
	}

	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		global $vbphrase, $vbulletin, $show;
        
		$item = $hash;		
		$cost = floor($cost*$this->settings['d2t']);		
		$merchantID = $this->settings['zpmid'];
		
		$form['action'] = 'wikipal.php';
		$form['method'] = 'POST';        
			
		$settings =& $this->settings;
		
		$templater = vB_Template::create('subscription_payment_wikipal');
	     	$templater->register('merchantID', $merchantID);
		$templater->register('cost', $cost);
		$templater->register('item', $item);					
		$templater->register('subinfo', $subinfo);
		$templater->register('settings', $settings);
		$templater->register('userinfo', $userinfo);
		$form['hiddenfields'] .= $templater->render();
		return $form;
	}
}

/*=============================================================================================*\
|| ########################################################################################### ||
|| # Product Name	: wikipal Payment API Module for vBulletin		Version: 4.X.X
|| # By				: Viasky Company							  WebSite: www.viasky.net
|| ########################################################################################### ||
\*=============================================================================================*/
?>
