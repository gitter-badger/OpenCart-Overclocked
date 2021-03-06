<?php
class ControllerModulePopular extends Controller {
	private $_name = 'popular';

	protected function index($setting) {
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

		$this->data['products'] = array();

		$results = $this->model_catalog_product->getPopularProducts($setting['limit']);

		foreach ($results as $result) {
			if ($result['image']) {
				$image = $this->model_tool_image->resize($result['image'], $setting['image_width'], $setting['image_height']);
			} else {
				$image = false;
			}

			if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
				if (($result['price'] == '0.0000') && $this->config->get('config_price_free')) {
					$price = $this->language->get('text_free');
				} else {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')));
				}
			} else {
				$price = false;
			}

			if ((float)$result['special']) {
				$special_label = $this->model_tool_image->resize($this->config->get('config_label_special'), 50, 50);
				$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')));
			} else {
				$special_label = false;
				$special = false;
			}

			if ($this->config->get('config_review_status')) {
				$rating = $result['rating'];
			} else {
				$rating = false;
			}

			if ($result['quantity'] <= 0) {
				$stock_label = $this->model_tool_image->resize($this->config->get('config_label_stock'), 50, 50);
			} else {
				$stock_label = false;
			}

			if (in_array($result['product_id'], $offers, true)) {
				$offer_label = $this->model_tool_image->resize($this->config->get('config_label_offer'), 50, 50);
				$offer = true;
			} else {
				$offer_label = false;
				$offer = false;
			}

			if ($result['quote']) {
				$quote = $this->url->link('information/quote', '', 'SSL');
			} else {
				$quote = false;
			}

			$this->data['products'][] = array(
				'product_id'      => $result['product_id'],
				'thumb'           => $image,
				'stock_label'     => $stock_label,
				'offer_label'     => $offer_label,
				'special_label'   => $special_label,
				'offer'           => $offer,
				'name'            => $result['name'],
				'stock_status'    => $result['stock_status'],
				'stock_quantity'  => $result['quantity'],
				'stock_remaining' => ($result['subtract']) ? sprintf($this->language->get('text_remaining'), $result['quantity']) : '',
				'quote'           => $quote,
				'price'           => $price,
				'price_option'    => $this->model_catalog_product->hasOptionPriceIncrease($result['product_id']),
				'special'         => $special,
				'minimum'         => ($result['minimum'] > 0) ? $result['minimum'] : 1,
				'age_minimum'     => ($result['age_minimum'] > 0) ? $result['age_minimum'] : '',
				'rating'          => (int)$rating,
				'reviews'         => sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
				'href'            => $this->url->link('product/product', 'product_id=' . $result['product_id'], 'SSL')
			);
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
