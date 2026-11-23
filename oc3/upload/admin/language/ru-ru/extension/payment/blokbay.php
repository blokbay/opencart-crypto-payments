<?php
// Blokbay crypto payments
$_['heading_title'] = 'Криптоплатежи Blokbay (BTC, USDC, USDT, ETH)';
$_['text_extension'] = 'Расширения';
$_['text_success'] = 'Настройки Blokbay сохранены.';
$_['text_edit'] = 'Изменить Blokbay';
$_['text_intro'] = 'Покупатели платят криптовалютой на платёжной странице Blokbay, и средства поступают прямо на ваш кошелёк: без хранения средств у посредника. 1% от оплаченной цены удерживается автоматически, без фиксированных платежей. Валюты заказов: USD, EUR, GBP, CHF, JPY, CAD, AUD, SGD, HKD, AED; покупатель платит криптовалютой по текущему курсу.';
$_['entry_secret_key'] = 'Секретный ключ API';
$_['entry_webhook_secret'] = 'Секрет подписи вебхука';
$_['entry_public_key'] = 'Аккаунт (публичный ключ)';
$_['entry_api_base'] = 'Адрес API';
$_['entry_pending_status'] = 'Статус в ожидании оплаты';
$_['entry_paid_status'] = 'Статус после оплаты';
$_['entry_expired_status'] = 'Статус при истечении платежа';
$_['entry_status'] = 'Статус';
$_['entry_sort_order'] = 'Порядок сортировки';
$_['help_secret_key'] = 'Панель Blokbay, раздел «Платежи с вашего сайта». Ключ остаётся на вашем сервере и никогда не показывается покупателям.';
$_['help_webhook'] = 'В панели Blokbay укажите этот адрес вебхука, затем скопируйте сюда секрет подписи:';
$_['help_public_key'] = 'Необязательно. Если указан, события вебхука от других аккаунтов Blokbay отклоняются.';
$_['error_permission'] = 'Внимание: у вас нет прав на изменение оплаты Blokbay!';
$_['error_secret_key'] = 'Введите секретный ключ (sk_live_...).';
$_['error_webhook_secret'] = 'Введите секрет подписи вебхука (whsec_...).';
$_['error_api_base'] = 'Адрес API должен начинаться с http:// или https://.';
$_['text_blokbay'] = '<a href="https://blokbay.com" target="_blank" rel="noopener">Blokbay</a>';
$_['text_currency'] = 'Валюта магазина по умолчанию: %s. Blokbay принимает %s, поэтому оплата криптовалютой скрыта для заказов в других валютах.';
