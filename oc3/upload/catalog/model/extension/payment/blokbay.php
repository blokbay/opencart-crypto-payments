<?php
/**
 * Blokbay crypto payments for OpenCart 3.0.x: payment method entry and the shared logic of webhook and return
 * (exact lookup of the Blokbay payment, paid only once, expiry only for the current payment of a pending order).
 *
 * @author  Blokbay
 * @license MIT
 */
class ModelExtensionPaymentBlokbay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/blokbay');

		$currency = isset($this->session->data['currency']) ? (string)$this->session->data['currency'] : '';

		if (!$this->config->get('payment_blokbay_secret_key') || !in_array(strtoupper($currency), array('USD', 'EUR', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD', 'SGD', 'HKD', 'AED'), true) || $this->cart->hasRecurringProducts()) {
			return array();
		}

		return array(
			'code'       => 'blokbay',
			'title'      => $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_blokbay_sort_order')
		);
	}

	public function api() {
		require_once DIR_SYSTEM . 'library/blokbay/blokbay_api.php';

		return new BlokbayApi((string)$this->config->get('payment_blokbay_secret_key'), (string)$this->config->get('payment_blokbay_api_base'));
	}

	public function addPayment($payment_id, $order_id, $amount) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "blokbay_payment` SET `payment_id` = '" . $this->db->escape($payment_id) . "', `order_id` = '" . (int)$order_id . "', `amount` = '" . $this->db->escape($amount) . "', `status` = 'pending', `created_at` = NOW()");
	}

	public function getPayment($payment_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blokbay_payment` WHERE `payment_id` = '" . $this->db->escape((string)$payment_id) . "'");

		return $query->row;
	}

	public function countPayments($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "blokbay_payment` WHERE `order_id` = '" . (int)$order_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * Applies a Blokbay payment object (webhook "data" or GET /api/v1/payments/:id) to its order.
	 */
	public function apply(array $p) {
		$id = isset($p['id']) && is_string($p['id']) ? $p['id'] : '';
		$row = $id !== '' ? $this->getPayment($id) : array();

		if (!$row) {
			return 'unknown payment';
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder((int)$row['order_id']);

		if (!$order_info) {
			return 'unknown order';
		}

		$status = isset($p['status']) ? (string)$p['status'] : '';

		if ($status === 'paid') {
			// Amount and currency of the order: the amount was saved formatted in the order currency when the payment was created.
			if ((string)$row['amount'] !== (isset($p['amount']) ? (string)$p['amount'] : '') || (isset($p['currency']) ? $p['currency'] : '') !== strtoupper((string)$order_info['currency_code'])) {
				return 'amount mismatch';
			}

			// Atomic: the same event delivered twice (or webhook and return at the same time) pays the order once.
			$this->db->query("UPDATE `" . DB_PREFIX . "blokbay_payment` SET `status` = 'paid', `paid_at` = NOW() WHERE `payment_id` = '" . $this->db->escape($id) . "' AND `status` <> 'paid'");

			if ($this->db->countAffected() < 1) {
				return 'already paid';
			}

			$comment = 'Blokbay: ' . $id . ' paid';

			if (!empty($p['coin'])) {
				$comment .= ' in ' . (string)$p['coin'] . (!empty($p['network']) ? ' on ' . (string)$p['network'] : '');
			}

			if (!empty($p['tx'])) {
				$comment .= ', tx ' . substr((string)$p['tx'], 0, 120);
			}

			$this->model_checkout_order->addOrderHistory((int)$row['order_id'], (int)$this->config->get('payment_blokbay_paid_status_id'), $comment, true);

			return 'paid';
		}

		if ($status === 'expired') {
			// Only the latest payment of the order, and only while the order still waits for it.
			$latest = $this->db->query("SELECT `payment_id` FROM `" . DB_PREFIX . "blokbay_payment` WHERE `order_id` = '" . (int)$row['order_id'] . "' ORDER BY `created_at` DESC, `id` DESC LIMIT 1")->row;

			if ($row['status'] === 'pending' && $latest && $latest['payment_id'] === $id && (int)$order_info['order_status_id'] === (int)$this->config->get('payment_blokbay_pending_status_id')) {
				$this->db->query("UPDATE `" . DB_PREFIX . "blokbay_payment` SET `status` = 'expired' WHERE `payment_id` = '" . $this->db->escape($id) . "' AND `status` = 'pending'");

				if ($this->db->countAffected() > 0) {
					$this->model_checkout_order->addOrderHistory((int)$row['order_id'], (int)$this->config->get('payment_blokbay_expired_status_id'), 'Blokbay: ' . $id . ' expired');

					return 'expired';
				}
			}
		}

		return 'ok';
	}
}
