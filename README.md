# mono-pay-woocommecrce

Модуль WooCommerce для wordpress.
Перероблений офіційний модуль від monobank для підключення інтернет-еквайрингу.
За основу було взято версію 1.0.4.

== Description ==

Виправлення

= 1.0.0 =

1. Вирішено помилку: name was called incorrectly. Властивості товару недоступні напряму. Backtrace: require('wp-blog-header.php'), require_once('wp-includes/template-loader.php'), do_action('template_redirect'), WP_Hook->do_action, WP_Hook->apply_filters, WC_AJAX::do_wc_ajax, do_action('wc_ajax_checkout'), WP_Hook->do_action, WP_Hook->apply_filters, WC_AJAX::checkout, WC_Checkout->process_checkout, WC_Checkout->process_order_payment, WC_Gateway_Mono->process_payment, WC_Abstract_Legacy_Product->__get, wc_doing_it_wrong. This message was added in version 3.0.
2. Перероблена функція process_payment()
3. Вирішено проблему пов'язану з відповіддю mono api: [errText] => 'code' is required for fiscalization.
4. Додана можливість використовувати купони та знижки.
