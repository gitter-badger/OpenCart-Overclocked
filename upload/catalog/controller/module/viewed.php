<?php
class ControllerModuleViewed extends Controller {
	private $_name = 'viewed';

	public function index($setting) {
		static $module = 0;

		$this->language->load('module/' . $this->_name);

		$this->data['heading_title'] = $this->language->get('heading_title');

		// Module
		$this->data['theme'] = $this->config->get($this->_name . '_theme');
		$this->data['title'] = $this->config->get($this->_name . '_title' . $this->config->get('config_language_id'));

		if (!$this->data['title']) {
			$this->data['title'] = $this->data['heading_title'];
		}

		$stylesheet_mode = $this->config->get('config_stylesheet');

		if (!$stylesheet_mode) {
			$header_color = $this->config->get($this->_name . '_header_color');
			$header_shape = $this->config->get($this->_name . '_header_shape');
			$content_color = $this->config->get($this->_name . '_content_color');
			$content_shape = $this->config->get($this->_name . '_content_shape');

			$this->data['header_color'] = ($header_color) ? $header_color . '-skin' : 'white-skin';
			$this->data['header_shape'] = ($header_shape) ? $header_shape . '-top' : 'rounded-0';
			$this->data['content_color'] = ($content_color) ? $content_color . '-skin' : 'white-skin';
			$this->data['content_shape'] = ($content_shape) ? $content_shape . '-bottom' : 'rounded-0';
		} else {
			$this->data['header_color'] = '';
			$this->data['header_shape'] = '';
			$this->data['content_color'] = '';
			$this->data['content_shape'] = '';
		}

		$this->data['stylesheet_mode'] = $stylesheet_mode;

		$this->data['text_from'] = $this->language->get('text_from');
		$this->data['text_offer'] = $this->language->get('text_offer');

		$this->data['lang'] = $this->language->get('code');

		$this->data['button_view'] = $this->language->get('button_view');
		$this->data['button_quote'] = $this->language->get('button_quote');
		$this->data['button_cart'] = $this->language->get('button_cart');

		$this->data['viewproduct'] = $this->config->get($this->_name . '_viewproduct');
		$this->data['addproduct'] = $this->config->get($this->_name . '_addproduct');

		$this->load->model('catalog/product');
		$this->load->model('catalog/offer');
		$this->load->model('tool/image');

		$offers = $this->model_catalog_offer->getListProductOffers(0);

		$products = array();

		if (isset($this->request->cookie['viewed'])) {
			$products = explode(',', $this->request->cookie['viewed']);
		} elseif (isset($this->session->data['viewed'])) {
			$products = $this->session->data['viewed'];
		}

		if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {
			$product_id = $this->request->get['product_id'];

			$products = array_diff($products, array($product_id));

			array_unshift($products, $product_id);

			setcookie('viewed', implode(',', $products), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
		}

		if (empty($setting['limit'])) {
			$setting['limit'] = 4;
		}

		$products = array_slice($products, 0, (int)$setting['limit']);

		$this->data['products'] = array();

		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $setting['image_width'], $setting['image_height']);
				} else {
					$image = false;
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					if (($product_info['price'] == '0.0000') && $this->config->get('config_price_free')) {
						$price = $this->language->get('text_free');
					} else {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')));
					}
				} else {
					$price = false;
				}

				if ((float)$product_info['special']) {
					$special_label = $this->model_tool_image->resize($this->config->get('config_label_special'), 50, 50);
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')));
				} else {
					$special_label = false;
					$special = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = $product_info['rating'];
				} else {
					$rating = false;
				}

				if ($product_info['quantity'] <= 0) {
					$stock_label = $this->model_tool_image->resize($this->config->get('config_label_stock'), 50, 50);
				} else {
					$stock_label = false;
				}

				if (in_array($product_info['product_id'], $offers, true)) {
					$offer_label = $this->model_tool_image->resize($this->config->get('config_label_offer'), 50, 50);
					$offer = true;
				} else {
					$offer_label = false;
					$offer = false;
				}

				if ($product_info['quote']) {
					$quote = $this->url->link('information/quote', '', 'SSL');
				} else {
					$quote = false;
				}

				$this->data['products'][] = array(
					'product_id'      => $product_info['product_id'],
					'thumb'           => $image,
					'stock_label'     => $stock_label,
					'offer_label'     => $offer_label,
					'special_label'   => $special_label,
					'offer'           => $offer,
					'name'            => $product_info['name'],
					'stock_status'    => $product_info['stock_status'],
					'stock_quantity'  => $product_info['quantity'],
					'stock_remaining' => ($product_info['subtract']) ? sprintf($this->language->get('text_remaining'), $product_info['quantity']) : '',
					'quote'           => $quote,
					'price'           => $price,
					'price_option'    => $this->model_catalog_product->hasOptionPriceIncrease($product_info['product_id']),
					'special'         => $special,
					'minimum'         => ($product_info['minimum'] > 0) ? $product_info['minimum'] : 1,
					'age_minimum'     => ($product_info['age_minimum'] > 0) ? $product_info['age_minimum'] : '',
					'rating'          => (int)$rating,
					'reviews'         => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
					'href'            => $this->url->link('product/product', 'product_id=' . $product_info['product_id'], 'SSL')
				);
			}
		}

		$this->data['module'] = $module++;

		// Template
		$this->data['template'] = $this->config->get('config_template');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/' . $this->_name . '.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/' . $this->_name . '.tpl';
		} else {
			$this->template = 'default/template/module/' . $this->_name . '.tpl';
		}

		$this->render();
	}
}
