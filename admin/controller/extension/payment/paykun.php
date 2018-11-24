<?php
class ControllerExtensionPaymentPaykun extends Controller {

    private $error = array();

    /**
     * @return string
     */
	private function getCallbackUrl(){
		$callback_url = "index.php?route=extension/payment/paykun/callback";
		return $_SERVER['HTTPS']? HTTPS_CATALOG . $callback_url : HTTP_CATALOG . $callback_url;
	}

    /**
     *
     */
	public function index() {

		$this->language->load('extension/payment/paykun');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');		

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_paykun', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL'));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['error_merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['entry_access_token'])) {
			$data['error_access_token'] = $this->error['entry_access_token'];
		} else {
			$data['error_access_token'] = '';
		}

		if (isset($this->error['enc_key'])) {
			$data['error_enc_key'] = $this->error['error_enc_key'];
		} else {
			$data['error_enc_key'] = '';
		}
		
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

  		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('text_home'),
			'href'	  => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('text_extension'),
			'href'	  => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('text_payments'),
			'href'	  => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL'),
		'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('heading_title'),
			'href'	  => $this->url->link('extension/payment/paykun', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/payment/paykun', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL');


		if (isset($this->request->post['payment_paykun_merchant_id'])) {
			$data['payment_paykun_merchant_id'] = $this->request->post['payment_paykun_merchant_id'];
		} else {
			$data['payment_paykun_merchant_id'] = $this->config->get('payment_paykun_merchant_id');
		}

		if (isset($this->request->post['payment_paykun_access_token'])) {
			$data['payment_paykun_access_token'] = $this->request->post['payment_paykun_access_token'];
		} else {
			$data['payment_paykun_access_token'] = $this->config->get('payment_paykun_access_token');
		}

		if (isset($this->request->post['payment_paykun_enc_key'])) {
			$data['payment_paykun_enc_key'] = $this->request->post['payment_paykun_enc_key'];
		} else {
			$data['payment_paykun_enc_key'] = $this->config->get('payment_paykun_enc_key');
		}
	
		$data["default_callback_url"] = $this->getCallbackUrl();

		if (isset($this->request->post['payment_paykun_order_success_status_id'])) {
			$data['payment_paykun_order_success_status_id'] = $this->request->post['payment_paykun_order_success_status_id'];
		} else {
			$data['payment_paykun_order_success_status_id'] = $this->config->get('payment_paykun_order_success_status_id');
		}

		if (isset($this->request->post['payment_paykun_order_failed_status_id'])) {
			$data['payment_paykun_order_failed_status_id'] = $this->request->post['payment_paykun_order_failed_status_id'];
		} else {
			$data['payment_paykun_order_failed_status_id'] = $this->config->get('payment_paykun_order_failed_status_id');
		}

		if (isset($this->request->post['payment_paykun_sort_order'])) {
			$data['payment_paykun_sort_order'] = $this->request->post['payment_paykun_sort_order'];
		} else {
			$data['payment_paykun_sort_order'] = (int)$this->config->get('payment_paykun_sort_order');
		}

        if (isset($this->request->post['payment_paykun_status'])) {
            $data['payment_paykun_status'] = $this->request->post['payment_paykun_status'];
        } else {
            $data['payment_paykun_status'] = $this->config->get('payment_paykun_status');
        }

        if (isset($this->request->post['payment_paykun_log_status'])) {
            $data['payment_paykun_log_status'] = $this->request->post['payment_paykun_log_status'];
        } else {
            $data['payment_paykun_log_status'] = $this->config->get('payment_paykun_log_status');
        }

		$last_updated = "";
		$path = DIR_SYSTEM . "/paykun_version.txt";
		if(file_exists($path)){
			$handle = fopen($path, "r");
			if($handle !== false){
				$date = fread($handle, 10); // i.e. DD-MM-YYYY or 25-04-2018
				$last_updated = '<p>Last Updated: '. date("d F Y", strtotime($date)) .'</p>';
			}
		}

		$data['footer_text'] = '<div class="text-center">'.$last_updated.'<p>'.$this->language->get('text_opencart_version').': '.VERSION.'</p></div>';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/paykun', $data));


	}

    /**
     * @return bool
     */
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/paykun')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// trim all values except for Promo Codes
		foreach($this->request->post as $key=>&$val){
            $val = trim($val);
		}

		if (!$this->request->post['payment_paykun_merchant_id']) {
			$this->error['error_merchant_id'] = $this->language->get('error_merchant_id');
		}
		if (!$this->request->post['payment_paykun_access_token']) {
			$this->error['error_access_token'] = $this->language->get('error_access_token');
		}
		if (!$this->request->post['payment_paykun_enc_key']) {
			$this->error['error_enc_key'] = $this->language->get('error_enc_key');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}