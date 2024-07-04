<?php
class ModelExtensionTotalCoinpaysCheckout extends Model {
	public function getTotal($total) {
		$this->load->language('extension/payment/coinpays_checkout');

		$total['totals'][] = array(
			'code'       => 'coinpays_checkout',
			'title'      => $this->language->get('text_total'),
			'value'      => max(0, $total['total']),
			'sort_order' => '8'
		);
	}
}