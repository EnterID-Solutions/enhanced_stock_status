<?php
class ControllerEventEnhancedStockStatusProduct extends Controller {

    const DEBUG_MODE = true; // Set to false in production
    public function enhanceProductPage(&$route, &$data, &$output) {
        // Only run if module is enabled
        if (!$this->config->get('module_enhanced_stock_status_status')) {
            return;
        }

        // Get product ID from the data
        if (!isset($data['product_id'])) {
            return;
        }
        $product_id = $data['product_id'];

        // Get product info
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);
        if (!$product_info) {
            return;
        }

        // Create handler using factory
        $handler = EnhancedStockStatusHandler::create($this->config, $this->db);

        // Get enhanced stock status
        $stock_status_id = $handler->getStockStatusIdByName($product_info['stock_status']);
        $quantity = $product_info['quantity'];
        $enhanced_status = $handler->getEnhancedStockStatus($stock_status_id, $quantity);

        // Prepare data for template
        $template_data = array(
            'enhanced_stock_status' => $enhanced_status
        );
        // Render the enhanced stock status HTML
        $enhanced_html = $this->load->view('module/enhanced_stock_status', $template_data);

        $pattern = '/<li class="product-stock\s*[^>]*>(.*?)<\/li>/';
        if (preg_match($pattern, $output)) {
            // Replace the stock status in the product page
            $replacement = $enhanced_html;
            $output = preg_replace($pattern, $replacement, $output);
        } else {
            $this->logMessage('Product stats div not found in HTML');
        }
    }



    // Private method for controlled logging
    protected function logMessage($message): void {
        if (self::DEBUG_MODE) {
            $this->log->write('EnhancedStockStatus: ' . $message);
        }
    }
}
