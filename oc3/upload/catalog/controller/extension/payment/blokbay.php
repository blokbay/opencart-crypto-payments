<?php
/**
 * Blokbay crypto payments for OpenCart 3.0.x.
 * confirm: creates the Blokbay payment and sends the customer to the payment page.
 * callback: the customer's return (status asked to the API, the webhook may not have arrived yet).
 * webhook: signed events from Blokbay, URL index.php?route=extension/payment/blokbay/webhook
 *
 * @author  Blokbay
 * @license MIT
 */
class ControllerExtensionPaymentBlokbay extends Controller {
	public function index() {
		$this->load->language('extension/payment/blokbay');

		$data['text_title'] = $this->language->get('text_title');
		$data['text_instruction'] = $this->language->get('text_instruction');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_loading'] = $this->language->get('text_loading');

		return $this->load->view('extension/payment/blokbay', $data);
	}

	public function confirm() {
		$this->load->language('extension/payment/blokbay');

		$json = array();
		$order_info = array();

		if (isset($this->session->data['order_id'])) {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);

			if (!$order_info) {
				$json['error'] = $this->language->get('error_order');
			}
		} else {
			$json['error'] = $this->language->get('error_order');
		}

		if (!isset($this->session->data['payment_method']['code']) || $this->session->data['payment_method']['code'] != 'blokbay') {
			$json['error'] = $this->language->get('error_payment_method');
		}

		if (!$json && $order_info['currency_code'] !== 'USD') {
			$json['error'] = $this->language->get('error_currency');
		}

		if (!$json) {
			$this->load->model('extension/payment/blokbay');

			$order_id = (int)$order_info['order_id'];

			require_once DIR_SYSTEM . 'library/blokbay/blokbay_api.php';

			$amount = BlokbayApi::amount((float)$order_info['total'] * (float)$order_info['currency_value']);
			$attempt = $this->model_extension_payment_blokbay->countPayments($order_id);

			$res = $this->model_extension_payment_blokbay->api()->createPayment(array(
				'amount'         => $amount,
				'currency'       => 'USD',
				'reference'      => (string)$order_id,
				'description'    => 'Order ' . $order_id,
				'customer_email' => (string)$order_info['email'],
				'url_success'    => str_replace('&amp;', '&', $this->url->link('extension/payment/blokbay/callback', 'order_id=' . $order_id, true)),
				'url_cancel'     => str_replace('&amp;', '&', $this->url->link('checkout/checkout', '', true))
			), $this->idempotencyKey($order_info, $amount, $attempt));

			if (empty($res['id']) || empty($res['url']) || !is_string($res['id']) || !is_string($res['url'])) {
				// Nothing is changed: the order stays unconfirmed and the cart stays as it is, the customer can try again.
				$this->log->write('Blokbay: payment not created for order ' . $order_id . ' (' . (isset($res['error']) && is_string($res['error']) ? $res['error'] : 'error') . ')');

				$json['error'] = $this->language->get('error_create');
			} else {
				$this->model_extension_payment_blokbay->addPayment($res['id'], $order_id, $amount);

				$pending = (int)$this->config->get('payment_blokbay_pending_status_id');

				if ((int)$order_info['order_status_id'] !== $pending) {
					$this->model_checkout_order->addOrderHistory($order_id, $pending, 'Blokbay: ' . $res['id']);
				}

				$json['redirect'] = $res['url'];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback() {
		$id = isset($this->request->get['blokbay_payment']) ? (string)$this->request->get['blokbay_payment'] : '';
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

		$this->load->model('extension/payment/blokbay');

		$row = $id !== '' ? $this->model_extension_payment_blokbay->getPayment($id) : array();
		$mine = $row && (int)$row['order_id'] === $order_id;
		$status = '';

		if ($mine) {
			$p = $this->model_extension_payment_blokbay->api()->getPayment($id);

			if (isset($p['id']) && $p['id'] === $id && isset($p['status'])) {
				$status = (string)$p['status'];

				if ($status === 'paid') {
					$this->model_extension_payment_blokbay->apply($p);
				}
			}
		}

		if (!$mine || $status === 'expired') {
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		} else {
			// Paid, or still confirming on the blockchain (the webhook will mark it paid): the order is placed.
			$this->response->redirect($this->url->link('checkout/success', '', true));
		}
	}

	public function webhook() {
		$raw = (string)file_get_contents('php://input');
		$sig = isset($this->request->server['HTTP_BLOKBAY_SIGNATURE']) ? (string)$this->request->server['HTTP_BLOKBAY_SIGNATURE'] : '';
		$code = 200;
		$msg = 'ok';

		require_once DIR_SYSTEM . 'library/blokbay/blokbay_api.php';

		if (!BlokbayApi::verifySignature($raw, $sig, (string)$this->config->get('payment_blokbay_webhook_secret'))) {
			$code = 400;
			$msg = 'bad signature';
		} else {
			$ev = json_decode($raw, true);
			$pk = trim((string)$this->config->get('payment_blokbay_public_key'));

			if (!is_array($ev) || empty($ev['type'])) {
				$code = 400;
				$msg = 'bad payload';
			} elseif ($pk !== '' && (!isset($ev['account']) || $ev['account'] !== $pk)) {
				$code = 400;
				$msg = 'other account';
			} elseif ($ev['type'] === 'ping') {
				$msg = 'pong';
			} elseif (isset($ev['data']) && is_array($ev['data'])) {
				$this->load->model('extension/payment/blokbay');

				$msg = $this->model_extension_payment_blokbay->apply($ev['data']);
			}
		}

		http_response_code($code);

		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->addHeader('Cache-Control: no-store');
		$this->response->setOutput($msg);
	}

	/**
	 * Same key for the same attempt (double click, network retry), never shared with another shop on the same Blokbay account
	 * or with an older order that had the same number (store reinstalled): shop hash + order id + order date + amount + attempt.
	 */
	private function idempotencyKey(array $order_info, $amount, $attempt) {
		$shop = substr(hash('sha256', (defined('HTTP_SERVER') ? HTTP_SERVER : '') . '|' . DB_DATABASE . '|' . DB_PREFIX), 0, 10);

		return 'oc-' . $shop . '-' . (int)$order_info['order_id'] . '-' . (int)strtotime((string)$order_info['date_added']) . '-' . $amount . '-' . (int)$attempt;
	}
}
