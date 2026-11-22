# OpenCart 3 and 4 Crypto Payment Gateway: Bitcoin, USDT, USDC, ETH (Blokbay)

Accept crypto payments in OpenCart 3 and 4: Bitcoin (BTC), Tether (USDT), USD Coin (USDC) and Ether (ETH) on Ethereum, BNB Smart Chain (BSC), Polygon, Base and Arbitrum. Non-custodial: every payment goes straight to your own wallet. 1% per payment, no monthly fee. Download the latest release: [Releases](../../releases/latest).

Accept BTC, USDC, USDT and ETH in your OpenCart shop. The customer pays on the Blokbay payment page and the funds go straight to
your own wallet: no custody, no frozen funds, no waiting for a payout. 1% of the paid price is withheld automatically, no fixed costs.
You remain responsible for your shop and for your obligations towards your customers. Author: Blokbay. License: MIT.

Two packages:
- OpenCart 4.0.2.x and 4.1.x: `blokbay.ocmod.zip` (keep this exact file name: OpenCart 4 takes the extension code from it).
- OpenCart 3.0.x: `blokbay-opencart3.ocmod.zip`.

Languages: English, Italian, Arabic, Turkish, Russian (folders en-gb, it-it, ar, tr-tr, ru-ru: the texts appear when the shop has that language pack).

## Setup
1. Open a free account on https://blokbay.com, add your wallet, then open "Payments from your site".
2. Extensions > Installer: upload the zip (OpenCart 4: then click Install next to "Blokbay crypto payments").
3. Extensions > Extensions > Payments: install "Blokbay crypto payments" and edit it.
4. Paste the secret API key (sk_live_...), the webhook signing secret (whsec_...) and, optionally, the account (pk_live_...).
   Choose the order statuses (defaults: Pending while waiting, Processing when paid, Expired when the payment expires) and enable it.
5. In the Blokbay dashboard set the webhook URL shown in the settings and send the test event (the shop answers "pong"):
   - OpenCart 4: `https://YOUR-SHOP/index.php?route=extension/blokbay/payment/blokbay.webhook`
   - OpenCart 3: `https://YOUR-SHOP/index.php?route=extension/payment/blokbay/webhook`

The method appears only when the shop currency selected by the customer is USD. Bitcoin is available from 100 USD; stablecoins and ETH from 1 USD.

## How it works
- "Pay with crypto" creates a Blokbay payment (Idempotency-Key unique to the shop, order and attempt) and opens the payment page.
  If the payment cannot be created (wrong key, network), nothing changes: the customer sees a message and keeps the cart.
- When paid, Blokbay sends a signed `payment.paid` webhook (HMAC-SHA256, 5-minute tolerance) and the order moves to the paid status once,
  even if the same event arrives twice. When the customer comes back, the extension also asks Blokbay for the status.
- `payment.expired` changes the order only if it is the latest payment of an order that is still waiting.

---

## Supported networks
Payments settle on chain through the verified LuxSplit gateway contract `0xd4b637cC81D5B5115Cd0204A9ba6B60474F05e31` (same address on every network, source verified on Sourcify):

| Network | Chain ID | Assets |
|---|---|---|
| BNB Smart Chain (BSC, BNB Chain) | 56 | USDC, USDT |
| Ethereum | 1 | ETH, USDC, USDT |
| Polygon | 137 | USDC, USDT |
| Base | 8453 | USDC |
| Arbitrum | 42161 | USDC, USDT |
| Bitcoin | – | BTC (orders from 100 USD) |

On BNB Smart Chain the buyer pays with USDC or USDT (BEP-20) and the funds reach the merchant's wallet in the same transaction.
Verified source (Sourcify, chain 56): https://repo.sourcify.dev/56/0xd4b637cC81D5B5115Cd0204A9ba6B60474F05e31
The network list is also in [`networks.json`](networks.json).

## Italiano
Accetta BTC, USDC, USDT ed ETH nel tuo negozio OpenCart: il cliente paga sulla pagina di pagamento di Blokbay e i fondi vanno direttamente
nel tuo wallet, senza custodia. L'1% del prezzo pagato è trattenuto in automatico, nessun costo fisso. Installazione: Estensioni > Installatore
(carica lo zip), poi Estensioni > Estensioni > Pagamenti > Blokbay; inserisci chiave segreta, segreto del webhook e, se vuoi, l'account;
nel pannello Blokbay imposta l'URL del webhook mostrato nelle impostazioni. Ordini in USD.
