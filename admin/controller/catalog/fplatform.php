<?php
class ControllerCatalogFplatform extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/fplatform');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/fplatform');
        $this->model_catalog_fplatform->Fix();
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_fplatform->addSet($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
		}
        
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['apikey'])) {
			$data['error_apikey'] = $this->error['apikey'];
		} else {
			$data['error_apikey'] = '';
		}
		
        if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/fplatform', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('catalog/fplatform', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		
		$store_url = isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1')) ? HTTPS_CATALOG : HTTP_CATALOG;
		
		

		$data['user_token'] = $this->session->data['user_token'];
		
		$forz = $this->model_catalog_fplatform->getSet();
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		if (isset($this->request->post['furl'])) {
			$data['furl'] = $this->request->post['furl'];
		} elseif (isset($forz['furl'])) {
			$data['furl'] = $forz['furl'];
		} else {
			$data['furl'] = '';
		}
		
		if (isset($this->request->post['apikey'])) {
			$data['apikey'] = $this->request->post['apikey'];
		} elseif (isset($forz['apikey'])) {
			$data['apikey'] = $forz['apikey'];
		} else {
			$data['apikey'] = '';
		}
		
		if (isset($this->request->post['getkey'])) {
			$data['getkey'] = $this->request->post['getkey'];
			if($this->request->post['getkey'] != '') {
			    $data['cron_url'] = $store_url . 'index.php?route=startup/fplatform&ffkey='.$this->request->post['getkey'];
			} else {
				$data['cron_url'] = $store_url . 'index.php?route=startup/fplatform';
			}
		} elseif (isset($forz['getkey'])) {
			$data['getkey'] = $forz['getkey'];
			$data['cron_url'] = $store_url . 'index.php?route=startup/fplatform&ffkey='.$forz['getkey'];
		} else {
			$data['getkey'] = '';
			$data['cron_url'] = $store_url . 'index.php?route=startup/fplatform';
		}
		
		if (isset($this->request->post['sklad_id'])) {
			$data['sklad_id'] = $this->request->post['sklad_id'];
		} elseif (isset($forz['sklad_id'])) {
			$data['sklad_id'] = $forz['sklad_id'];
		} else {
			$data['sklad_id'] = 1;
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/fplatform', $data));
	}
	
	public function webs() {
	    $json = array();

		if (isset($this->request->post['apikey']) && isset($this->request->post['sklad_id']) && isset($this->request->post['furl'])) {
            $d = 0;
			$o = 0;
			$err = '';
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->request->post['furl'].'/client_api/v1/stocks?sklad_id='.$this->request->post['sklad_id']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            $headers = array();
            $headers[] = 'Accept: application/json';
            $headers[] = 'Token: '.$this->request->post['apikey'];
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
						
						$this->load->model('catalog/fplatform');
                        if($this->model_catalog_fplatform->getTotalProductsSku($sku)) {
							if($this->model_catalog_fplatform->editProduct($sku, $quantity)) {
							    $o++;
							}
						} else {
							$this->model_catalog_fplatform->addProduct($pr['product'], $sku, $quantity);
							$d++;
						}
					}
				}
			}
            //$this->log->write(print_r($response_data,true));
		}
		$json['d'] = $d;
		$json['o'] = $o;
		$json['err'] = $err;
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/fplatform')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (($this->request->post['apikey'] == '') || (utf8_strlen($this->request->post['apikey']) < 1)) {
				$this->error['apikey'] = $this->language->get('error_apikey');
		}

		return !$this->error;
	}
	
    
	
}