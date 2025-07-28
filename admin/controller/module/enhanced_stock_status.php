<?php
namespace Opencart\Admin\Controller\Extension\EnhancedStockStatus\Module;
class EnhancedStockStatus extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/enhanced_stock_status/module/enhanced_stock_status');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/enhanced_stock_status/module/enhanced_stock_status', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/enhanced_stock_status/module/enhanced_stock_status.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        // Get stock statuses
        $this->load->model('localisation/stock_status');
        $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
        
        // Load enhanced data for each stock status
        $data['enhanced_statuses'] = [];
        
        foreach ($data['stock_statuses'] as $status) {
            $enhanced_data = $this->config->get('module_enhanced_stock_status_' . $status['stock_status_id']);
            
            if ($enhanced_data) {
                $data['enhanced_statuses'][$status['stock_status_id']] = $enhanced_data;
            } else {
                $data['enhanced_statuses'][$status['stock_status_id']] = [
                    'color' => '',
                    'icon' => ''
                ];
            }
        }

        $data['module_enhanced_stock_status_status'] = $this->config->get('module_enhanced_stock_status_status');
        $data['module_enhanced_stock_status_show_in_thumb'] = $this->config->get('module_enhanced_stock_status_show_in_thumb');
        
        // Get multilingual available text settings
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        
        $data['module_enhanced_stock_status_available'] = [];
        foreach ($data['languages'] as $language) {
            $data['module_enhanced_stock_status_available'][$language['language_id']] = $this->config->get('module_enhanced_stock_status_available_' . $language['language_id']) ?: $this->language->get('text_available_default');
        }
        
        // Get available color and icon
        $data['module_enhanced_stock_status_available_color'] = $this->config->get('module_enhanced_stock_status_available_color') ?: '#28a745'; // Default green
        $data['module_enhanced_stock_status_available_icon'] = $this->config->get('module_enhanced_stock_status_available_icon') ?: 'fa-check-circle';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/enhanced_stock_status/module/enhanced_stock_status', $data));
    }

    public function save(): void {
        $this->load->language('extension/enhanced_stock_status/module/enhanced_stock_status');
        
        $this->log->write('Saving Enhanced Stock Status settings');
        $this->log->write($this->request->post);
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/enhanced_stock_status/module/enhanced_stock_status')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            // Prepare all main module settings in one array
            $module_settings = [
                'module_enhanced_stock_status_status' => $this->request->post['module_enhanced_stock_status_status'],
                'module_enhanced_stock_status_show_in_thumb' => isset($this->request->post['module_enhanced_stock_status_show_in_thumb']) ? $this->request->post['module_enhanced_stock_status_show_in_thumb'] : 0,
            ];
            
            // Add available product settings to the main settings array
            if (isset($this->request->post['module_enhanced_stock_status_available'])) {
                foreach ($this->request->post['module_enhanced_stock_status_available'] as $language_id => $text) {
                    $module_settings['module_enhanced_stock_status_available_' . $language_id] = $text;
                }
            }
            
            // Add available color and icon to main settings
            $module_settings['module_enhanced_stock_status_available_color'] = $this->request->post['module_enhanced_stock_status_available_color'] ?? '#28a745';
            $module_settings['module_enhanced_stock_status_available_icon'] = $this->request->post['module_enhanced_stock_status_available_icon'] ?? 'fa-check-circle';
            
            // Save all main module settings at once
            $this->model_setting_setting->editSetting('module_enhanced_stock_status', $module_settings);
            
            // Save enhanced data for each stock status
            if (isset($this->request->post['enhanced_status'])) {
                foreach ($this->request->post['enhanced_status'] as $stock_status_id => $data) {
                    $this->model_setting_setting->editSetting('module_enhanced_stock_status_' . $stock_status_id, [
                        'module_enhanced_stock_status_' . $stock_status_id => $data
                    ]);
                }
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        // Add events
        $this->load->model('setting/event');
        
        // Event for adding enhanced stock status data to products from model
        $this->model_setting_event->addEvent([
            'code' => 'enhanced_stock_status_add_to_products',
            'description' => 'Add enhanced stock status data to product results',
            'trigger' => 'catalog/model/catalog/product.getProducts/after',
            'action' => 'extension/enhanced_stock_status/event/enhanced_stock_status_listing.enhanceProducts',
            'status' => true,
            'sort_order' => 0
        ]);
        
        // Event for modifying Journal3 product listings HTML
        $this->model_setting_event->addEvent([
            'code' => 'enhanced_stock_status_journal_listing',
            'description' => 'Add enhanced stock status to Journal3 product listings',
            'trigger' => 'catalog/view/journal3/products/after',
            'action' => 'extension/enhanced_stock_status/event/enhanced_stock_status_listing.addEnhancedStockStatusToListing',
            'status' => true,
            'sort_order' => 0
        ]);

        // Event for capturing product data
        $this->model_setting_event->addEvent([
            'code' => 'enhanced_stock_status_product_data',
            'description' => 'Capture product data after getProduct',
            'trigger' => 'catalog/model/catalog/product.getProduct/after',
            'action' => 'extension/enhanced_stock_status/event/enhanced_stock_status_product.captureProductData',
            'status' => true,
            'sort_order' => 0
        ]);
        
        // Event for enhancing product page output
        $this->model_setting_event->addEvent([
            'code' => 'enhanced_stock_status_product_page_output',
            'description' => 'Replace stock status in product page output',
            'trigger' => 'catalog/view/product/product/after',
            'action' => 'extension/enhanced_stock_status/event/enhanced_stock_status_product.enhanceProductPageOutput',
            'status' => true,
            'sort_order' => 0
        ]);
        

    }

    public function uninstall(): void {
        // Remove events
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_add_to_products');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_journal_listing');

        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_product_data');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_product_page_output');
        
        // // Remove settings
        // $this->load->model('setting/setting');
        // $this->model_setting_setting->deleteSetting('module_enhanced_stock_status');
        
        // // Delete all enhanced status settings
        // $this->load->model('localisation/stock_status');
        // $stock_statuses = $this->model_localisation_stock_status->getStockStatuses();
        
        // foreach ($stock_statuses as $status) {
        //     $this->model_setting_setting->deleteSetting('module_enhanced_stock_status_' . $status['stock_status_id']);
        // }
    }
    
}