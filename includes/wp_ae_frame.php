<?php

class WP_AE_Frame {
	
	// temp vars
	private $ae_api_key = "";
	private $ae_tracking_id = "";
	
	
	public function get_frame($id, $url)
	{

		$this->ae_api_key = get_option('wp_ae_apikey');
		$this->ae_tracking_id = get_option('wp_ae_trackingid');
		
		$data = array();
		if($id == 0 && $url != ""){
			$id = $this->get_id_from_url($url);
		}
		if($id != 0){
			$data = $this->get_data_for_id($id);
		}
			
		$html = "";
		if(!isset($data['status']) || $data['status'] == 0){
			$html = $this->get_html_no_item(); 	
		}else{
			$html = $this->get_html_item($data['item']);
		}
		
		return $html;
		
	}
	
	private function get_data_for_id($id)
	{
		
		$data = array();
		
		$data['input_id'] = $id;
		$data['status'] = 0;
		
		if($id != 0){
			
			// disable cache:
			//delete_transient("wp_ae_item_".$id);
			
			$item_cache = get_transient("wp_ae_item_".$id);
			
			$item = array();
			if($item_cache == false){
				if($this->ae_api_key != ""){
					// own api key
					$item = $this->get_item_for_id($id);
				}else{
					// webservice
					$item = $this->get_item_webservice_for_id($id);
				}
				set_transient("wp_ae_item_".$id, $item, 1*DAY_IN_SECONDS);
			}else{
				$item = $item_cache;
			}
			$data['item'] = $item;
			if(count($item) > 0){
				$data['status'] = 1;
			}
				
		}
		
		return $data;
		
	}
	
	private function get_id_from_url($url)
	{
		
		$id = 0;
		
		$path = parse_url($url, PHP_URL_PATH);
		$filename = basename($path);
		$new_id = strtok($filename, '.');
		if(is_numeric($new_id)){ 
			$id = $new_id;
		}
		
		return $id;
		
	}
	
	private function get_item_for_id($id)
	{
		$item = array();
		
		$fields = "productId,productTitle,originalPrice,salePrice,discount,productUrl,imageUrl";
		$url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionProductDetail/".$this->ae_api_key."?fields=".$fields."&productId=".$id;
		$args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    'httpversion' => '1.0',
		    'user-agent'  => 'WordPress; WP-AliExpress-Shortcode',
		    'blocking'    => true,
		    'headers'     => array(),
		    'cookies'     => array(),
		    'body'        => null,
		    'compress'    => false,
		    'decompress'  => true,
		    'sslverify'   => true,
		    'stream'      => false,
		    'filename'    => null
		);
		
		$response = wp_remote_get( $url, $args );
		if(is_array($response)) {
			$response_fields = json_decode($response['body'],true);
			if(array_key_exists("errorCode", $response_fields) && $response_fields['errorCode'] == "20010000"){
				if(array_key_exists("result", $response_fields)){
					$item = $response_fields['result'];
	
					$item['promotionUrl'] = "";
					$promo_url = $this->get_promourl_for_url($item['productUrl']);
					if($promo_url != false){
						$item['promotionUrl'] = $promo_url;
					}
					
				}
			}
		}
		
		return $item;
	}
	
	private function get_promourl_for_url($url)
	{
		$promo_url = false;
		
		$fields = "promotionUrl";
		$url = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionLinks/".$this->ae_api_key."?fields=".$fields."&trackingId=".$this->ae_tracking_id."&urls=".urlencode($url);
		$args = array(
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.0',
				'user-agent'  => 'WordPress; WP-AliExpress-Shortcode',
				'blocking'    => true,
				'headers'     => array(),
				'cookies'     => array(),
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
				'filename'    => null
		);
		
		$response = wp_remote_get( $url, $args );
		if(is_array($response)) {
			$response_fields = json_decode($response['body'],true);
			if(array_key_exists("errorCode", $response_fields) && $response_fields['errorCode'] == "20010000"){
				if(array_key_exists("result", $response_fields)){
					$result = $response_fields['result'];
					if(count($result['promotionUrls']) > 0){
						$promo_url = $result['promotionUrls'][0]['promotionUrl'];
					}
				}
			}
		}
		
		return $promo_url;
	}
	
	private function get_item_webservice_for_id($id)
	{
		$item = array();
	
		$url = "https://api.eryk.io/wp-aliexpress-shortcode/v1/?id=".$id;
		$args = array(
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.0',
				'user-agent'  => 'WordPress/'.get_bloginfo('version').'; WP-AliExpress-Shortcode; '.get_bloginfo('url'),
				'blocking'    => true,
				'headers'     => array(),
				'cookies'     => array(),
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
				'filename'    => null
		);
	
		$response = wp_remote_get( $url, $args );
		if(is_array($response)) {
			$response_fields = json_decode($response['body'],true);
			if($response_fields['status'] == 1){
				$item = $response_fields['item'];
			}
		}
	
		return $item;
	}
	
	private function get_html_item($item)
	{
		$html = "";
		
		$link = $item['productUrl'];
		if(isset($item['promotionUrl']) && $item['promotionUrl'] != ""){
			$link = $item['promotionUrl'];
		}
		
		$html .= "<div class='wp_ae_item_container'>";
			$html .= "<div class='wp_ae_item_image'>";
			if(isset($item['imageUrl']) && $item['imageUrl'] != ""){
				$html .= "<div class='wp_ae_item_image_inner'>";
					$html .= "<a href='".$link."' target='_blank' class='wp_ae_item_link'>";
						$html .= "<img src='".$item['imageUrl']."' width='80' height='80' border='0' />";
					$html .= "</a>";
				$html .= "</div>";
			}
			$html .= "</div>";
			$html .= "<div class='wp_ae_item_title'>";
				$html .= "<a href='".$link."' target='_blank' class='wp_ae_item_link'>";
					$html .= $item['productTitle'];
				$html .= "</a>";
			$html .= "</div>";
			$html .= "<div class='wp_ae_clear'></div>";
			$html .= "<div class='wp_ae_item_bar'>";
				$html .= "<div class='wp_ae_item_logo'><span class='wp_ae_item_logo_color'>Ali</span>Express</div>";
				$html .= "<a href='".$link."' target='_blank' class='wp_ae_item_link'>";
					$html .= "<div class='wp_ae_item_button'>";
						$html .= "<a href='".$link."' target='_blank' class='wp_ae_item_link'><span class='dashicons dashicons-cart'></span> Buy</a>";
					$html .= "</div>";
				$html .= "</a>";
				$html .= "<div class='wp_ae_item_price'>".$item['salePrice']."</div>";
				$html .= "<div class='wp_ae_clear'></div>";
			$html .= "</div>";
		$html .= "</div>";
		
		return $html;	
	}
	
	private function get_html_no_item()
	{
		$html = "";
		
		$html .= "<div class='wp_ae_no_item_container'>";
			$html .= "<div class='wp_ae_no_item_logo'><span class='wp_ae_no_item_logo_color'>Ali</span>Express</div>";
			$html .= "<div class='wp_ae_no_item_error'>Error: Item no longer available or API problems.</div>";
			$html .= "<div class='wp_ae_clear'></div>";
		$html .= "</div>";
		
		return $html;
	}
	
}