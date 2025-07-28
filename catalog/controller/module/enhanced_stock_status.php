<?php
namespace Opencart\Catalog\Controller\Extension\EnhancedStockStatus\Module;

class EnhancedStockStatus extends \Opencart\System\Engine\Controller {
    // Debug mode constant - set to false to disable all logging
    private const DEBUG_MODE = false;
    
    // Static variable to store enhanced stock status data between events
    protected static $enhanced_statuses = [];
    
    // Store the handler instance
    protected $enhanced_stock_status_handler;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Only initialize if the module is enabled
        if ($this->config->get('module_enhanced_stock_status_status')) {
            $this->logMessage('Initializing EnhancedStockStatusHandler');
            
            // Load required model
            $this->load->model('localisation/stock_status');

            // Get all stock statuses - do this once
            $stock_statuses = $this->model_localisation_stock_status->getStockStatuses();

            // Collect all enhanced settings - do this once
            $enhanced_settings = [];
            foreach ($stock_statuses as $status) {
                $setting_key = 'module_enhanced_stock_status_' . $status['stock_status_id'];
                $enhanced_settings[$setting_key] = $this->config->get($setting_key);
            }
            
            // Get available product settings
            $language_id = $this->config->get('config_language_id');
            $available_settings = [
                'text_' . $language_id => $this->config->get('module_enhanced_stock_status_available_' . $language_id),
                'color' => $this->config->get('module_enhanced_stock_status_available_color'),
                'icon' => $this->config->get('module_enhanced_stock_status_available_icon')
            ];

            // Create handler with specific dependencies
            $this->enhanced_stock_status_handler = new \Opencart\System\Library\Extension\EnhancedStockStatus\EnhancedStockStatusHandler(
                $enhanced_settings,
                $stock_statuses,
                $available_settings,
                $language_id
            );
        }
    }
    
    // Private method for controlled logging
    protected function logMessage($message): void {
        if (self::DEBUG_MODE) {
            $this->log->write('EnhancedStockStatus: ' . $message);
        }
    }
        
}