<?php
class ControllerStartupFplatform extends Controller {
	public function index() {
	    $forz = $this->getSet();
		if($forz) {
			if ((isset($forz['getkey']) && isset($this->request->get['ffkey']) && ($forz['getkey'] != '') && ($forz['getkey'] == $this->request->get['ffkey'])) || (isset($forz['getkey']) && ($forz['getkey'] == ''))) {
				$d = 0;
			    $o = 0;
			    $err = '';
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $forz['furl'].'/client_api/v1/stocks?sklad_id='.$forz['sklad_id']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

                $headers = array();
                $headers[] = 'Accept: application/json';
                $headers[] = 'Token: '.$forz['apikey'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    $this->log->write(curl_error($ch));
				    $err = curl_errno($ch);
                }
        
                if (curl_errno($ch) != 0 && empty($result)) {
                    $result = false;
                }
        
                curl_close($ch);
			
                $response_data = json_decode($result, true);
			
			    if($response_data) {
				    foreach ($response_data as $pr) {
					
					    if($pr['product']) {
						    $sku = $pr['product']['client_code'];
						    $quantity = (int)$pr['num_avalible'];
						
                            if($this->getTotalProductsSku($sku)) {
							    if($this->editProduct($sku, $quantity)) {
							        $o++;
							    }
						    } else {
							    $this->addProduct($pr['product'], $sku, $quantity);
							    $d++;
						    }
					    }
				    }
			    }
				$datas = [ 'status' => 'God','updated' => $o, 'added' => $d ];
				$this->log->write('updated: '.$o);
				$this->log->write('added: '.$d);
			} else {
				$datas = [ 'status' => 'Error','description' => 'error getkey' ];
				$this->log->write('error getkey');
			}
			
			header('Content-type: application/json');
            echo json_encode( $datas );
		}
    }
	
	public function getSet() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fplatform");
		return $query->row; 
    }
	
	public function getTotalProductsSku($sku) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "'");

		return $query->row['total'];
	}
	
	public function editProduct($sku, $quantity) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "' AND quantity = '" . (int)$quantity . "'");
		if ($query->num_rows) {
			return false;
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = '" . (int)$quantity . "' WHERE sku = '" . $this->db->escape($sku) . "'");
			return 1;
		}
	}
	
	public function addProduct($data, $sku, $quantity) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '', sku = '" . $this->db->escape($sku) . "', upc = '', ean = '', jan = '', isbn = '', mpn = '', location = '', quantity = '" . (int)$quantity . "', minimum = '1', subtract = '0', stock_status_id = '0', date_available = NOW(), manufacturer_id = '0', shipping = '0', price = '0', points = '0', weight = '0', weight_class_id = '0', length = '0', width = '0', height = '0', length_class_id = '0', status = '0', noindex = '1', tax_class_id = '0', sort_order = '0', date_added = NOW(), date_modified = NOW()");

		$product_id = $this->db->getLastId();

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$this->config->get('config_language_id') . "', name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', tag = '', meta_title = '" . $this->db->escape($data['name']) . "', meta_h1 = '" . $this->db->escape($data['name']) . "', meta_description = '', meta_keyword = ''");

		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $product_id;
	}
}