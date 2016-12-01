<?php
$MerchantID 			= $_POST['zp_mid'];
$Price 					= $_POST['zp_amount'];
$Description 			= $_POST['zp_comments'];
$InvoiceNumber 			= time();
$CallbackURL 			= $_POST['zp_callback_url'];
	
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'http://gatepay.co/webservice/paymentRequest.php');
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
curl_setopt($curl, CURLOPT_POSTFIELDS, "MerchantID=$MerchantID&Price=$Price&Description=$Description&InvoiceNumber=$InvoiceNumber&CallbackURL=". urlencode($CallbackURL));
curl_setopt($curl, CURLOPT_TIMEOUT, 400);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$result = json_decode(curl_exec($curl));
curl_close($curl);

if ($result->Status == 100){
	header('location: http://gatepay.co/webservice/startPayment.php?au='. $result->Authority);
} else {
	echo $result->Status;
}
?>
