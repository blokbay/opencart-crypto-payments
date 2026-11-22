<?php
namespace Opencart\Admin\Controller\Extension\Blokbay\Payment;
/**
 * Class Blokbay
 *
 * Settings of the Blokbay payment method (Extensions > Extensions > Payments).
 *
 * @author  Blokbay
 * @license MIT
 */
class Blokbay extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/blokbay/payment/blokbay');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', $token . '&type=payment')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/blokbay/payment/blokbay', $token)
		];

		$data['save'] = $this->url->link('extension/blokbay/payment/blokbay.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=payment');

		$data['webhook_url'] = HTTP_CATALOG . 'index.php?route=extension/blokbay/payment/blokbay.webhook';

		foreach (['secret_key', 'webhook_secret', 'public_key', 'api_base', 'status', 'sort_order'] as $key) {
			$data['payment_blokbay_' . $key] = (string)$this->config->get('payment_blokbay_' . $key);
		}

		if ($data['payment_blokbay_api_base'] === '') {
			$data['payment_blokbay_api_base'] = 'https://blokbay.com';
		}

		$data['payment_blokbay_pending_status_id'] = (int)($this->config->get('payment_blokbay_pending_status_id') ?: 1);
		$data['payment_blokbay_paid_status_id'] = (int)($this->config->get('payment_blokbay_paid_status_id') ?: 2);
		$data['payment_blokbay_expired_status_id'] = (int)($this->config->get('payment_blokbay_expired_status_id') ?: 14);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/blokbay/payment/blokbay', $data));
	}

	public function save(): void {
		$this->load->language('extension/blokbay/payment/blokbay');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/blokbay/payment/blokbay')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!isset($post['payment_blokbay_secret_key']) || !preg_match('/^sk_live_[0-9A-Za-z]{20,100}$/', trim((string)$post['payment_blokbay_secret_key']))) {
			$json['error']['secret_key'] = $this->language->get('error_secret_key');
		}

		if (!isset($post['payment_blokbay_webhook_secret']) || strpos(trim((string)$post['payment_blokbay_webhook_secret']), 'whsec_') !== 0) {
			$json['error']['webhook_secret'] = $this->language->get('error_webhook_secret');
		}

		if (!empty($post['payment_blokbay_api_base']) && !preg_match('#^https?://#', (string)$post['payment_blokbay_api_base'])) {
			$json['error']['api_base'] = $this->language->get('error_api_base');
		}

		if (!$json) {
			foreach (['secret_key', 'webhook_secret', 'public_key', 'api_base'] as $key) {
				$post['payment_blokbay_' . $key] = trim((string)($post['payment_blokbay_' . $key] ?? ''));
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_blokbay', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
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

	public function uninstall(): void {
		// The table stays: it links existing orders to their Blokbay payments.
	}
}
