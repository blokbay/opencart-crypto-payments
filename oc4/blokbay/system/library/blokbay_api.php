<?php
/**
 * Blokbay API client and webhook signature check for OpenCart (3.0.x and 4.x).
 * No OpenCart classes: unit-testable on its own. PHP 7.0+.
 *
 * @author  Blokbay
 * @license MIT
 */
if (!class_exists('BlokbayApi', false)) {
	class BlokbayApi {
		const DEFAULT_BASE = 'https://blokbay.com';
		const TOLERANCE = 300;

		private $secret_key;
		private $base;
		/** @var callable|null function($method, $url, array $headers, $body) => array('code' => int, 'body' => string) */
		private $transport;

		public function __construct($secret_key, $base = '', $transport = null) {
			$this->secret_key = trim((string)$secret_key);
			$base = trim((string)$base);
			$this->base = rtrim($base !== '' ? $base : self::DEFAULT_BASE, '/');
			$this->transport = $transport;
		}

		/**
		 * Currencies accepted by the Blokbay API, with their decimals (JPY has none). The buyer pays in crypto at the current
		 * exchange rate; amount and currency come back in the payment and in webhooks exactly as sent.
		 */
		public static $currencies = array('USD' => 2, 'EUR' => 2, 'GBP' => 2, 'CHF' => 2, 'JPY' => 0, 'CAD' => 2, 'AUD' => 2, 'SGD' => 2, 'HKD' => 2, 'AED' => 2);

		public static function supported($currency) {
			return isset(self::$currencies[strtoupper((string)$currency)]);
		}

		/** Amount with the decimals of the currency, as a string ("249.90", JPY "25000"). */
		public static function amount($value, $currency = 'USD') {
			$code = strtoupper((string)$currency);
			$dec = isset(self::$currencies[$code]) ? self::$currencies[$code] : 2;

			return number_format(round((float)$value, $dec), $dec, '.', '');
		}

		public function createPayment(array $data, $idempotency_key = '') {
			return $this->request('POST', '/api/v1/payments', $data, $idempotency_key);
		}

		public function getPayment($id) {
			if (!preg_match('/^gp_[a-z0-9]+$/', (string)$id)) {
				return array('error' => 'bad_id');
			}
			return $this->request('GET', '/api/v1/payments/' . $id);
		}

		private function request($method, $path, $data = null, $idempotency_key = '') {
			$headers = array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'User-Agent'    => 'Blokbay-OpenCart/1.0'
			);
			if ($idempotency_key !== '') {
				$headers['Idempotency-Key'] = substr((string)$idempotency_key, 0, 100);
			}
			$body = $data === null ? null : json_encode($data);
			if ($this->transport) {
				$res = call_user_func($this->transport, $method, $this->base . $path, $headers, $body);
			} else {
				$res = self::curl($method, $this->base . $path, $headers, $body);
			}
			$json = json_decode(isset($res['body']) ? (string)$res['body'] : '', true);
			if (!is_array($json)) {
				return array('error' => 'bad_response', '_code' => isset($res['code']) ? (int)$res['code'] : 0);
			}
			$json['_code'] = isset($res['code']) ? (int)$res['code'] : 0;
			return $json;
		}

		private static function curl($method, $url, array $headers, $body) {
			$h = array();
			foreach ($headers as $k => $v) {
				$h[] = $k . ': ' . $v;
			}
			$c = curl_init($url);
			if ($c === false) {
				return array('code' => 0, 'body' => '');
			}
			curl_setopt_array($c, array(
				CURLOPT_CUSTOMREQUEST  => $method,
				CURLOPT_HTTPHEADER     => $h,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => 20,
				CURLOPT_FOLLOWLOCATION => false
			));
			if ($body !== null) {
				curl_setopt($c, CURLOPT_POSTFIELDS, $body);
			}
			$out = curl_exec($c);
			$code = (int)curl_getinfo($c, CURLINFO_HTTP_CODE);
			return array('code' => $code, 'body' => is_string($out) ? $out : '');
		}

		/**
		 * Checks the Blokbay-Signature header: t=<unix>,v1=<hex HMAC-SHA256 of "<t>.<raw body>" with the signing secret>.
		 * Signatures older or newer than 5 minutes are refused (replay protection).
		 */
		public static function verifySignature($raw_body, $header, $secret, $now = null) {
			$secret = (string)$secret;
			$header = (string)$header;
			if ($secret === '' || $header === '') {
				return false;
			}
			$parts = array();
			foreach (explode(',', $header) as $kv) {
				$p = explode('=', trim($kv), 2);
				if (count($p) === 2) {
					$parts[$p[0]] = $p[1];
				}
			}
			if (empty($parts['t']) || empty($parts['v1']) || !ctype_digit($parts['t'])) {
				return false;
			}
			$now = $now === null ? time() : (int)$now;
			if (abs($now - (int)$parts['t']) > self::TOLERANCE) {
				return false;
			}
			$expected = hash_hmac('sha256', $parts['t'] . '.' . (string)$raw_body, $secret);
			return hash_equals($expected, $parts['v1']);
		}
	}
}
