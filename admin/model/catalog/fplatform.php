<?php
class ModelCatalogFplatform extends Model {
	public function addSet($data) {   
        $this->db->query("DELETE FROM " . DB_PREFIX . "fplatform");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "fplatform` SET `apikey` = '" . $this->db->escape($data['apikey']) . "', `sklad_id` = '" . (isset($data['sklad_id']) ? (int)$data['sklad_id'] : 1) . "', `furl` = '" . $this->db->escape($data['furl']) . "', `getkey` = '" . $this->db->escape($data['getkey']) . "'");
    }
    
    public function getSet() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fplatform");
		return $query->row; 
    }

	public function Fix() {
	    $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."fplatform'");
        if(count($query->rows) <= 0){
            $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fplatform` (
               `apikey` text NOT NULL,
               `sklad_id` int(11) NOT NULL,
			   `getkey` varchar(128) NOT NULL,
			   `furl` varchar(128) NOT NULL
            )";
            $this->db->query($sql); 
        }
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