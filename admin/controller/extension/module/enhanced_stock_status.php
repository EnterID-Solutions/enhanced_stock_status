<?php
class ControllerExtensionModuleEnhancedStockStatus extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/enhanced_stock_status');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_enhanced_stock_status', $this->request->post);
            // Save per-stock-status color/icon
            if (isset($this->request->post['enhanced_status'])) {
                foreach ($this->request->post['enhanced_status'] as $stock_status_id => $status_data) {
                    $this->model_setting_setting->editSetting('module_enhanced_stock_status_' . $stock_status_id, array('module_enhanced_stock_status_' . $stock_status_id => $status_data));
                }
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_show_in_thumb'] = $this->language->get('entry_show_in_thumb');
        $data['entry_available_settings'] = $this->language->get('entry_available_settings');
        $data['entry_available_text'] = $this->language->get('entry_available_text');
        $data['entry_color'] = $this->language->get('entry_color');
        $data['entry_icon'] = $this->language->get('entry_icon');
        $data['entry_stock_status'] = $this->language->get('entry_stock_status');
        $data['help_color'] = $this->language->get('help_color');
        $data['help_icon'] = $this->language->get('help_icon');
        $data['help_show_in_thumb'] = $this->language->get('help_show_in_thumb');
        $data['help_available_text'] = $this->language->get('help_available_text');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/enhanced_stock_status', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/enhanced_stock_status', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        // Load settings
        if (isset($this->request->post['module_enhanced_stock_status_status'])) {
            $data['module_enhanced_stock_status_status'] = $this->request->post['module_enhanced_stock_status_status'];
        } else {
            $data['module_enhanced_stock_status_status'] = $this->config->get('module_enhanced_stock_status_status');
        }
        if (isset($this->request->post['module_enhanced_stock_status_show_in_thumb'])) {
            $data['module_enhanced_stock_status_show_in_thumb'] = $this->request->post['module_enhanced_stock_status_show_in_thumb'];
        } else {
            $data['module_enhanced_stock_status_show_in_thumb'] = $this->config->get('module_enhanced_stock_status_show_in_thumb');
        }

        // Load available text, color, icon
        if (isset($this->request->post['module_enhanced_stock_status_available'])) {
            $data['module_enhanced_stock_status_available'] = $this->request->post['module_enhanced_stock_status_available'];
        } else {
            $data['module_enhanced_stock_status_available'] = $this->config->get('module_enhanced_stock_status_available');
        }
        if (isset($this->request->post['module_enhanced_stock_status_available_color'])) {
            $data['module_enhanced_stock_status_available_color'] = $this->request->post['module_enhanced_stock_status_available_color'];
        } else {
            $data['module_enhanced_stock_status_available_color'] = $this->config->get('module_enhanced_stock_status_available_color');
        }
        if (isset($this->request->post['module_enhanced_stock_status_available_icon'])) {
            $data['module_enhanced_stock_status_available_icon'] = $this->request->post['module_enhanced_stock_status_available_icon'];
        } else {
            $data['module_enhanced_stock_status_available_icon'] = $this->config->get('module_enhanced_stock_status_available_icon');
        }


        $this->load->model('localisation/stock_status');
        $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();


        // Load per-stock-status color/icon
        $data['enhanced_statuses'] = array();
        foreach ($data['stock_statuses'] as $status) {
            $key = 'module_enhanced_stock_status_' . $status['stock_status_id'];
            if (isset($this->request->post['enhanced_status'][$status['stock_status_id']])) {
                $data['enhanced_statuses'][$status['stock_status_id']] = $this->request->post['enhanced_status'][$status['stock_status_id']];
            } else {
                $setting = $this->model_setting_setting->getSetting($key);
                if (isset($setting[$key]) && is_array($setting[$key])) {
                    $data['enhanced_statuses'][$status['stock_status_id']] = $setting[$key];
                } else {
                    $data['enhanced_statuses'][$status['stock_status_id']] = array('color' => '', 'icon' => '');
                }
            }
        }



        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/enhanced_stock_status', $data));
    }

    public function install() {
        $this->load->model('setting/event');
        
        
        // Register event to enhance product page
        $this->model_setting_event->addEvent(
            'enhanced_stock_status_product_page',
            'catalog/view/product/product/after',
            'event/enhanced_stock_status_product/enhanceProductPage'
        );
        // Register event for product listing (Journal3)
        $this->model_setting_event->addEvent(
            'enhanced_stock_status_products_listing',
            'catalog/view/journal3/products/after',
            'event/enhanced_stock_status_products/afterProductsView'
        );
    }

    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_product_page');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_capture_data');
        $this->model_setting_event->deleteEventByCode('enhanced_stock_status_products_listing');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/enhanced_stock_status')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
