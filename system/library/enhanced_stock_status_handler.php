<?php
namespace Opencart\System\Library\Extension\EnhancedStockStatus;

class EnhancedStockStatusHandler {
    private $enhanced_settings;
    private $stock_statuses;
    private $available_settings;
    private $language_id;
    
    /**
     * Constructor with explicit dependencies
     * 
     * @param array $enhanced_settings The enhanced stock status settings
     * @param array $stock_statuses All stock statuses from database
     * @param array $available_settings Settings for available products
     * @param int $language_id Current language ID
     */
    public function __construct(array $enhanced_settings, array $stock_statuses, array $available_settings, int $language_id) {
        $this->enhanced_settings = $enhanced_settings;
        $this->available_settings = $available_settings;
        $this->language_id = $language_id;
        $this->stock_statuses = [];
        
        // Index stock statuses by ID for faster lookup
        foreach ($stock_statuses as $status) {
            $this->stock_statuses[$status['stock_status_id']] = $status;
        }
    }
    
    /**
     * Get enhanced stock status data for a product
     */
    public function getEnhancedStockStatus(int $stock_status_id, int $quantity = 0): array {
        $result = [
            'name' => '',
            'color' => '',
            'icon' => '',
            'is_available' => ($quantity > 0)
        ];
        
        // if ($quantity > 0) {
        //     // For available products, use the configured settings
        //     $result['name'] = $this->available_settings['text_' . $this->language_id] ?? 'Available';
        //     $result['color'] = $this->available_settings['color'] ?? '';
        //     $result['icon'] = $this->available_settings['icon'] ?? '';
        //     return $result;
        // }
        
        // For out-of-stock products, get enhanced data
        $setting_key = 'module_enhanced_stock_status_' . $stock_status_id;
        $enhanced_data = isset($this->enhanced_settings[$setting_key]) ? $this->enhanced_settings[$setting_key] : null;
        
        if ($enhanced_data) {
            $result['color'] = $enhanced_data['color'] ?? '';
            $result['icon'] = $enhanced_data['icon'] ?? '';
        }
        
        // Get stock status name from the pre-loaded statuses
        if (isset($this->stock_statuses[$stock_status_id])) {
            $result['name'] = $this->stock_statuses[$stock_status_id]['name'];
        } else {
            $result['name'] = 'Out of Stock';
        }
        
        return $result;
    }

    public function getStockStatusIdByName(string $name): ?int {
        foreach ($this->stock_statuses as $stock_status) {
            if (isset($stock_status['name']) && $stock_status['name'] === $name) {
                return $stock_status['stock_status_id'];
            }
        }
        return null;
    }
}