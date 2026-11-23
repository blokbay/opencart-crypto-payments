<?php
/**
 * Blokbay crypto payments for OpenCart 3.0.x: settings (Extensions > Extensions > Payments).
 *
 * @author  Blokbay
 * @license MIT
 */
class ControllerExtensionPaymentBlokbay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/blokbay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$token = 'user_token=' . $this->session->data['user_token'];

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$post = $this->request->post;

			foreach (array('secret_key', 'webhook_secret', 'public_key', 'api_base') as $key) {
				$post['payment_blokbay_' . $key] = isset($post['payment_blokbay_' . $key]) ? trim((string)$post['payment_blokbay_' . $key]) : '';
			}

			$this->model_setting_setting->editSetting('payment_blokbay', $post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', $token . '&type=payment', true));
		}

		foreach (array('warning', 'secret_key', 'webhook_secret', 'api_base') as $key) {
			$data['error_' . $key] = isset($this->error[$key]) ? $this->error[$key] : '';
		}

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=payment', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/payment/blokbay', $token, true))
		);

		$data['action'] = $this->url->link('extension/payment/blokbay', $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=payment', true);
		$data['webhook_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/blokbay/webhook';

		// Store currency not accepted by Blokbay: notice on the settings page (the method stays hidden for those orders).
		$accepted = array('USD', 'EUR', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD', 'SGD', 'HKD', 'AED');
		$store_currency = strtoupper((string)$this->config->get('config_currency'));
		$data['currency_warning'] = in_array($store_currency, $accepted, true) ? '' : sprintf($this->language->get('text_currency'), $store_currency, implode(', ', $accepted));

		$defaults = array('secret_key' => '', 'webhook_secret' => '', 'public_key' => '', 'api_base' => 'https://blokbay.com', 'pending_status_id' => 1, 'paid_status_id' => 2, 'expired_status_id' => 14, 'status' => 0, 'sort_order' => '');

		foreach ($defaults as $key => $default) {
			$name = 'payment_blokbay_' . $key;

			if (isset($this->request->post[$name])) {
				$data[$name] = $this->request->post[$name];
			} elseif ($this->config->get($name) !== null && $this->config->get($name) !== '') {
				$data[$name] = $this->config->get($name);
			} else {
				$data[$name] = $default;
			}
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/blokbay', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/blokbay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!isset($post['payment_blokbay_secret_key']) || !preg_match('/^sk_live_[0-9A-Za-z]{20,100}$/', trim((string)$post['payment_blokbay_secret_key']))) {
			$this->error['secret_key'] = $this->language->get('error_secret_key');
		}

		if (!isset($post['payment_blokbay_webhook_secret']) || strpos(trim((string)$post['payment_blokbay_webhook_secret']), 'whsec_') !== 0) {
			$this->error['webhook_secret'] = $this->language->get('error_webhook_secret');
		}

		if (!empty($post['payment_blokbay_api_base']) && !preg_match('#^https?://#', (string)$post['payment_blokbay_api_base'])) {
			$this->error['api_base'] = $this->language->get('error_api_base');
		}

		return !$this->error;
	}

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blokbay_payment` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`payment_id` VARCHAR(40) NOT NULL,
			`order_id` INT(11) NOT NULL,
			`amount` VARCHAR(20) NOT NULL,
			`status` VARCHAR(10) NOT NULL DEFAULT 'pending',
			`created_at` DATETIME NOT NULL,
			`paid_at` DATETIME NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `payment_id` (`payment_id`),
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function uninstall() {
		// The table stays: it links existing orders to their Blokbay payments.
	}
}
