<?php
namespace Opencart\Catalog\Controller\Extension\EnhancedStockStatus\Event;

use Opencart\Catalog\Controller\Extension\EnhancedStockStatus\Module\EnhancedStockStatus as BaseController;

class EnhancedStockStatusListing extends BaseController {
    /**
     * Hook after products are fetched from model
     */
    public function enhanceProducts(string &$route, array &$args, mixed &$output): void {
        $this->logMessage('enhanceProducts event triggered. Route: ' . $route);
        
        // Only run if module is enabled
        if (!$this->config->get('module_enhanced_stock_status_status')) {
            $this->logMessage('Module disabled, exiting...');
            return;
        }
        
        $this->logMessage('Processing ' . count($output) . ' products');
        
        // Process products
        foreach ($output as &$product) {
            if (isset($product['product_id'])) {
                // Save just what we need - quantity and stock_status_id
                self::$enhanced_statuses[$product['product_id']] = [
                    'stock_status_id' => $product['stock_status_id'] ?? 0,
                    'quantity' => $product['quantity'] ?? 0
                ];
                
                $this->logMessage('Saved data for product ID: ' . $product['product_id'] . 
                    ' (stock_status_id: ' . ($product['stock_status_id'] ?? 'unknown') . 
                    ', qty: ' . ($product['quantity'] ?? 'unknown') . ')');
            }
        }
    }

    /**
     * Hook after Journal3 product listing HTML is generated
     */
    public function addEnhancedStockStatusToListing(string &$route, array &$data, string &$output): void {
        $this->logMessage('addEnhancedStockStatusToListing event triggered. Route: ' . $route);
        
        // Only run if module is enabled and show in thumb is enabled
        if (!$this->config->get('module_enhanced_stock_status_status') || 
            !$this->config->get('module_enhanced_stock_status_show_in_thumb')) {
            $this->logMessage('Module disabled or show_in_thumb disabled, exiting...');
            return;
        }

        // Get the product ID directly from data
        $product_id = $data['product']['product_id'] ?? 0;
        $this->logMessage('Processing product ID: ' . $product_id);
        
        // Get the saved product status info
        $product_status_info = self::$enhanced_statuses[$product_id] ?? null;
        $this->logMessage('Product status info: ' . print_r($product_status_info, true));

        
        // If we don't have a stock status ID, find the quantity from the &data
        if (!$product_status_info) {
            $this->logMessage('No stock status ID found, using quantity from data');
            $product_status_info['quantity'] = $data['product']['quantity'] ?? 0;
            $product_status_info['stock_status_id']  = $this->enhanced_stock_status_handler->getStockStatusIdByName($data['product']['stock_status'] ?? 'Out of Stock');
        }

        // If we don't have data for this product, exit
        if (!$product_status_info['quantity'] && !$product_status_info['stock_status_id']) {
            $this->logMessage('No enhanced status data for product ID: ' . $product_id);
            return;
        }




        // Add CSS
        $this->document->addStyle('extension/enhanced_stock_status/catalog/view/stylesheet/enhanced_stock_status.css');
        
        // Find caption div to insert before
        $pattern = '/<div class="caption">/';
        
        if (preg_match($pattern, $output)) {
            $this->logMessage('Found caption element');
            
            // Use the already initialized handler - no need to create it again
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
            $enhanced_html = $this->load->view('extension/enhanced_stock_status/module/stock_status', $template_data);
            $this->logMessage('Generated stock status HTML');
            
            // Insert enhanced stock status HTML before the caption
            $output = preg_replace(
                $pattern, 
                $enhanced_html . '<div class="caption">', 
                $output, 
                1
            );
            
            $this->logMessage('Inserted enhanced status for product ' . $product_id);
        } else {
            $this->logMessage('No caption div found in HTML');
        }
    }
}
