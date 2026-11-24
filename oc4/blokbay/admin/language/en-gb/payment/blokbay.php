<?php
// Blokbay crypto payments
$_['heading_title'] = 'Blokbay crypto payments (USDT, USDC, ETH, BTC, SOL)';
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: you have saved the Blokbay settings.';
$_['text_edit'] = 'Edit Blokbay';
$_['text_intro'] = 'Customers pay in crypto on the Blokbay payment page and the funds go straight to your own wallet: no custody. 1% of the paid price is withheld automatically, no fixed costs. Order currencies: USD, EUR, GBP, CHF, JPY, CAD, AUD, SGD, HKD, AED; the customer pays in crypto at the current exchange rate. Coins: USDT and USDC on Ethereum, BNB Smart Chain, Arbitrum, Polygon and Solana, USDC on Base, ETH on Ethereum, Base and Arbitrum, SOL on Solana, and Bitcoin; the customer pays from a connected wallet, or by address and QR code from any wallet or exchange.';
$_['entry_secret_key'] = 'Secret API key';
$_['entry_webhook_secret'] = 'Webhook signing secret';
$_['entry_public_key'] = 'Account (public key)';
$_['entry_api_base'] = 'API address';
$_['entry_pending_status'] = 'Status while waiting for payment';
$_['entry_paid_status'] = 'Status when paid';
$_['entry_expired_status'] = 'Status when the payment expires';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort order';
$_['help_secret_key'] = 'Blokbay dashboard, "Payments from your site". It stays on your server and is never shown to customers.';
$_['help_webhook'] = 'In the Blokbay dashboard set this webhook URL, then copy the signing secret here:';
$_['help_public_key'] = 'Optional. When set, webhook events from any other Blokbay account are refused.';
$_['error_permission'] = 'Warning: you do not have permission to modify the Blokbay payment!';
$_['error_secret_key'] = 'Enter the secret key (sk_live_...).';
$_['error_webhook_secret'] = 'Enter the webhook signing secret (whsec_...).';
$_['error_api_base'] = 'The API address must start with http:// or https://.';
$_['text_currency'] = 'The default currency of this store is %s: Blokbay accepts %s, so the crypto payment method stays hidden for orders in other currencies.';
