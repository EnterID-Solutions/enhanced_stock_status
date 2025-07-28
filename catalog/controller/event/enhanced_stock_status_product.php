<?php
namespace Opencart\Catalog\Controller\Extension\EnhancedStockStatus\Event;

use Opencart\Catalog\Controller\Extension\EnhancedStockStatus\Module\EnhancedStockStatus as BaseController;

class EnhancedStockStatusProduct extends BaseController {
    /**
     * Capture product data after getProduct() is called
     */
    public function captureProductData(string &$route, array &$args, mixed &$output): void {
        $this->logMessage('captureProductData event triggered. Route: ' . $route);
        
        // Only run if module is enabled
        if (!$this->config->get('module_enhanced_stock_status_status')) {
            $this->logMessage('Module disabled, exiting...');
            return;
        }
        
        if (!$output) {
            $this->logMessage('No product data found');
            return;
        }
        
        // Save product data for later use
        $product_id = $output['product_id'];
        
        // Store the product data we need
        self::$enhanced_statuses[$product_id] = [
            'stock_status_id' => $output['stock_status_id'] ?? 0,
            'quantity' => $output['quantity'] ?? 0
        ];

        
        $this->logMessage('Captured product data for ID: ' . $product_id . 
            ' (stock_status_id: ' . ($output['stock_status_id'] ?? 'unknown') . 
            ', qty: ' . ($output['quantity'] ?? 'unknown') . ')');
    }

    /**
     * Add enhanced stock status in product page
     */
    public function enhanceProductPageOutput(string &$route, array &$data, string &$output): void {
        $this->logMessage('enhanceProductPageOutput event triggered. Route: ' . $route);
        
        // Only run if module is enabled
        if (!$this->config->get('module_enhanced_stock_status_status')) {
            $this->logMessage('Module disabled, exiting...');
            return;
        }
        
        // Get product ID from the URL
        if (!isset($this->request->get['product_id'])) {
            $this->logMessage('No product ID found in URL');
            return;
        }
        
        $product_id = (int)$this->request->get['product_id'];
        $this->logMessage('Enhancing product page for ID: ' . $product_id);
        
        // Get the saved product data
        $product_status_info = self::$enhanced_statuses[$product_id] ?? null;
        
        if (!$product_status_info) {
            $this->logMessage('No enhanced status data for product ID: ' . $product_id);
            return;
        }
        
        // Add CSS
        $this->document->addStyle('extension/enhanced_stock_status/catalog/view/stylesheet/enhanced_stock_status.css');
        
        // Get enhanced stock status from handler
        $enhanced_status = $this->enhanced_stock_status_handler->getEnhancedStockStatus(
            $product_status_info['stock_status_id'],
            $product_status_info['quantity']
        );

        // Prepare data for template
        $template_data = [
            'product' => [
                'enhanced_stock_status' => $enhanced_status
            ],
            'module_enhanced_stock_status_show_in_thumb' => true
        ];
        
        // Render the template
        $enhanced_content = $this->load->view('extension/enhanced_stock_status/module/stock_status', $template_data);
        $this->logMessage('Generated enhanced stock status content');
        
        // Find the product stats div in the HTML
        $pattern = '/<ul class="list-unstyled">/';
        
        if (preg_match($pattern, $output)) {
            $this->logMessage('Found product stats div, adding enhanced status after it');
            
            // Create a container for our enhanced stock status
            $enhanced_html = '<li>' . $enhanced_content . '</li>';
            
            // Insert our div after the product stats div
            $output = preg_replace($pattern, '<ul class="list-unstyled">' . $enhanced_html, $output, 1);
            
            $this->logMessage('Added enhanced status after product stats');
        } else {
            $this->logMessage('Product stats div not found in HTML');
        }
    }
}