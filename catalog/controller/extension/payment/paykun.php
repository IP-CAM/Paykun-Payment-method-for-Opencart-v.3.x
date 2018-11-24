<?php

require_once DIR_SYSTEM."Paykun/Payment.php";
require_once DIR_SYSTEM."Paykun/Errors/ValidationException.php";


class ControllerExtensionPaymentPaykun extends Controller {

	private $_merchantId;
    private $_accessToken;
    private $_encKey;
    private $log;
    private $isLogEnabled;

    /**
     * @return mixed
     */
    public function index() {

		$this->load->language('extension/payment/paykun');
		$this->load->model('extension/payment/paykun');
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$mobile_no = "";
		if(isset($order_info['telephone'])){
			$mobile_no = preg_replace('/\D/', '', $order_info['telephone']);
		}


		/*Set payment Params*/
		$this->_merchantId = $this->config->get('payment_paykun_merchant_id');
		$this->_accessToken = $this->config->get('payment_paykun_access_token');
		$this->_encKey = $this->config->get('payment_paykun_enc_key');


		//Get log setting
		$this->isLogEnabled = $this->config->get('payment_paykun_log_status');
        //Log file paykun_payment.log
        $this->log = new Log('paykun_payment.log');

		$pkdata = $this->initPayment($this->getOrderDetail($order_info));

		if($order_info['currency_code'] != "INR"){
		    echo $order_info['currency_code'];
            echo "Only Indian (INR) currency is allowed. Please change your store currency settings.";
            exit;
            /*Do not allow transaction if currency is not indian*/
            //trigger error message

		}

		$data['action'] = $this->config->get('payment_paykun_transaction_url');
		$data['button_confirm'] = $this->language->get('button_confirm');
        $data['paykunData'] = $pkdata;
		if(file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/paykun')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/payment/paykun', $data);
		} else {
			return $this->load->view('extension/payment/paykun', $data);
		}

	}

    /**
     * @param $message
     */
	private function addLog($message) {

        //Please check below path for logs "upload\system\storage\logs\paykun_payment.log"
        /*Check your path for the storage directory, storage directory path might be configured differently site by site*/

        if($this->isLogEnabled) {

            $this->log->write($message);

        }


    }

    /**
     * @param $orderDetail
     * @return null|string
     */
    private function initPayment($orderDetail) {

        try {

            $this->addLog(
                "merchantId => ".$this->_merchantId.
                ", accessToken=> ".$this->_accessToken.
                ", encKey => ".$this->_encKey.
                ", orderId => ".$orderDetail['orderId'].
                ", purpose=>".$orderDetail['purpose'].
                ", amount=> ".$orderDetail['amount']
            );

            $obj = new Payment($this->_merchantId, $this->_accessToken, $this->_encKey, true, true);

            // Initializing Order
            $obj->initOrder($orderDetail['orderId'], $orderDetail['purpose'], $orderDetail['amount'],
                $orderDetail['successUrl'], $orderDetail['failureUrl']);

            // Add Customer
            $obj->addCustomer($orderDetail['customerName'], $orderDetail['customerEmail'], $orderDetail['customerMoNo']);

            // Add Shipping address
            $obj->addShippingAddress($orderDetail['s_country'], $orderDetail['s_state'], $orderDetail['s_city'], $orderDetail['s_pinCode'],
                $orderDetail['s_addressString']);

            // Add Billing Address
            $obj->addBillingAddress($orderDetail['b_country'], $orderDetail['b_state'], $orderDetail['b_city'], $orderDetail['b_pinCode'],
                $orderDetail['b_addressString']);

            $obj->setCustomFields(['udf_1' => $orderDetail['orginalOrderId']]);
            //Render template and submit the form
            $data = $obj->submit();
            return $data;

        } catch (ValidationException $e) {

            $this->addLog($e->getMessage());
            echo $e->getMessage();
            //throw new ValidationException("Something went wrong.".$e->getMessage(), $e->getCode(), null);
            return null;
        }

    }

    /**
     * @param bool $isFailed
     * @return string
     */
    private function getCallbackUrl($isFailed = false){
        $callback_url = "index.php?route=extension/payment/paykun/callbackSuccess";

    	if($isFailed === true) {
            $callback_url = "index.php?route=extension/payment/paykun/callbackFailed";
		}

        return $_SERVER['HTTPS']? HTTPS_SERVER . $callback_url : HTTP_SERVER . $callback_url;
    }

    /**
     * @param $orderInfo
     * @return array|null
     */
    private function getOrderDetail($orderInfo) {
        try {

            $this->load->model('account/order');
//            $order_info = $this->model_checkout_order->getOrder($orderInfo['order_id']);
            $products = $this->model_account_order->getOrderProducts($orderInfo['order_id']);
            $itemPurpose = "";
            $totalItem = count($products);
            foreach ($products as $index => $value) {
                $stuff = ", ";
                if ($index == $totalItem - 1) {
                    $stuff = "";
                }
                $itemPurpose .= $value["name"].$stuff;
            }

            $oderdetail = [
                "orginalOrderId"    => $orderInfo['order_id'],
                /*  data*/
                "orderId"   => $this->getOrderIdForPaykun($orderInfo['order_id']),
                "purpose" => $itemPurpose,
                "amount"    => $this->currency->format($orderInfo['total'], $orderInfo['currency_code'], $orderInfo['currency_value'], false),
                "successUrl"    => $this->getCallbackUrl(),
                "failureUrl"    => $this->getCallbackUrl(true),
                /*Init over*/

                /*customer data*/
                "customerName"  => $orderInfo['firstname']. ' '.$orderInfo['lastname'],
                "customerEmail" =>  $orderInfo['email'],
                "customerMoNo"  => $orderInfo['telephone'],
                /*customer data over*/

                /*Shipping detail*/
                "s_country" => $orderInfo["shipping_country"],
                "s_state" => $orderInfo["shipping_zone"],
                "s_city"  => $orderInfo["shipping_city"],
                "s_pinCode"   => $orderInfo["shipping_postcode"],
                "s_addressString" => $orderInfo["shipping_address_1"].$orderInfo["shipping_address_2"],
                /*Shipping detail over*/

                /*Billing detail*/
                "b_country" => $orderInfo["payment_country"],
                "b_state" => $orderInfo["payment_zone"],
                "b_city"  => $orderInfo["payment_city"],
                "b_pinCode"   => $orderInfo["payment_postcode"],
                "b_addressString" => $orderInfo["payment_address_1"].$orderInfo["payment_address_2"],
                /*Billing detail over*/
            ];

            return $oderdetail;

        } catch (ValidationException $e) {

            $this->addLog($e->getMessage());
            return null;

        }

    }

    /**
     * @param $orderId
     * @return string
     */
    private function getOrderIdForPaykun($orderId) {

        $orderNumber = str_pad((string)$orderId, 10, '0', STR_PAD_LEFT);
        return $orderNumber;

    }

    /**
     *
     */
    public function callbackSuccess() {

        $this->language->load('extension/payment/paykun');
        $this->load->model('checkout/order');

        $paymentId = $this->request->get['payment-id'];
        $response = $this->_getcurlInfo($paymentId);
        $data = [];

        $data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
        $data['language'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');
        $data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));


        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $data['continue'] = $this->url->link('checkout/cart');
        $this->template = 'extension/payment/paykun_failure';

        if($response && isset($response['status']) && $response['status'] == "1" || $response['status'] == 1 ) {
            $payment_status = $response['data']['transaction']['status'];
            $order_id = $response['data']['transaction']['custom_field_1'];
            if($payment_status === "Success") {
            //if(1) {

            //compare amount
                $resAmout = $response['data']['transaction']['order']['gross_amount'];
                $orderInfo = $this->model_checkout_order->getOrder($order_id);
                if(($orderInfo['total']	== $resAmout)) {

                    $data['text_response'] = $this->language->get('text_response');
                    $data['text_success'] = $this->language->get('text_success');
                    $data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));

                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_paykun_order_success_status_id'), $this->getSuccessOrderStatusHtml($paymentId, true));

                    $data['continue'] = $this->url->link('checkout/success');
                    $this->template = 'extension/payment/paykun_success';
                    $this->cart->clear();

                }
                else { //Fraud activity is happening here, amount does not match

                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_paykun_order_failed_status_id'), $this->getSuccessOrderStatusHtml($paymentId));
                    $data['text_failure'] = $this->language->get('text_failure');
                    $data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));


                    $this->addLog("Fraud activity is happening with payment Id => $paymentId");
                }

//                $this->response->setOutput($this->load->view($this->template, $data));

            } else {

                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_paykun_order_failed_status_id'), $this->getSuccessOrderStatusHtml($paymentId));
                $data['text_failure'] = $this->language->get('text_failure');
                $data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));

//                $data['continue'] = $this->url->link('checkout/cart');
//                $this->template = 'extension/payment/paykun_failure';

                // unset order id if it is set, so new order id could be generated by paykun for next txns
                if(isset($this->session->data['order_id']))
                    unset($this->session->data['order_id']);

//                $this->response->setOutput($this->load->view($this->template, $data));

            }
        }
        else {

//            $data['continue'] = $this->url->link('checkout/cart');
//            $this->template = 'extension/payment/paykun_failure';
            $data['text_failure'] = "Unauthorised Transaction Id";
            $data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));

        }

        $this->response->setOutput($this->load->view($this->template, $data));
    }

    /**
     *
     */
    public function callbackFailed() {

        $this->language->load('extension/payment/paykun');
        $paymentId = $this->request->get['payment-id'];
        $response = $this->_getcurlInfo($paymentId);
        $data = [];

        $data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
        $data['language'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');
        $data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

        if(isset($response['status']) && $response['status'] == "1" || $response['status'] == 1 ) {

            $payment_status = $response['data']['transaction']['status'];
            $data['text_response'] = "Payment Failed.";
            $data['text_failure'] = "You have cancelled payment.";
            $data['text_failure_wait'] = "You will be redirected to the cart soon...";
            $this->load->model('checkout/order');
            $order_id = $response['data']['transaction']['custom_field_1'];

            /*Set default order status from paykun config*/
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_paykun_order_failed_status_id'), $this->getSuccessOrderStatusHtml($paymentId));

            $data['continue'] = $this->url->link('checkout/cart');
            $this->template = 'extension/payment/paykun_failure';

            // unset order id if it is set, so new order id could be generated by paykun for next txns
            if(isset($this->session->data['order_id']))
                unset($this->session->data['order_id']);

        } else {
            $data['continue'] = $this->url->link('checkout/cart');
            $this->template = 'extension/payment/paykun_failure';
            $data['text_response'] = "Could not get response from server.";
            $data['text_failure'] = "You have cancelled payment.";
            $data['text_failure_wait'] = "You will be redirected to the cart soon...";
            $this->load->model('checkout/order');

        }

        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $this->response->setOutput($this->load->view($this->template, $data));
    }

    /**
     * @param $iTransactionId
     * @return mixed|null
     */
    private function _getcurlInfo($iTransactionId) {

        try {

            $this->_merchantId = $this->config->get('payment_paykun_merchant_id');
            $this->_accessToken = $this->config->get('payment_paykun_access_token');
            $this->_encKey = $this->config->get('payment_paykun_enc_key');

            $cUrl        = 'https://api.paykun.com/v1/merchant/transaction/' . $iTransactionId . '/';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $cUrl);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("MerchantId:$this->_merchantId", "AccessToken:$this->_accessToken"));

            $response       = curl_exec($ch);
            $error_number   = curl_errno($ch);
            $error_message  = curl_error($ch);

            $res = json_decode($response, true);
            curl_close($ch);

            return ($error_message) ? null : $res;

        } catch (ValidationException $e) {
            $this->addLog($e->getMessage());
            echo $e->getMessage();
            //throw new ValidationException("Server couldn't respond, ".$e->getMessage(), $e->getCode(), null);
            return null;

        }

    }

    /**
     * @param $paymentId
     * @param $status true = success, false = failed
     */
    private function getSuccessOrderStatusHtml($paymentId, $status = false) {

        $colorCode = "#ea3232"; //red for failed
        $html = "";
        if($status) {
            $colorCode = "#577949";
        }
        return '<span style="padding: 5px;border-radius: 50%;background: '.$colorCode.';height: 5px;display: inline-block;"></span><span style="margin-left: 4px;">Paykun Payment Id => '.$paymentId.'</span>';

    }
}
?>
