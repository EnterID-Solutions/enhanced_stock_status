<?php
class ControllerEventEnhancedStockStatusProducts extends Controller {

    const DEBUG_MODE = true; // Set to false in production
    public function afterProductsView(&$route, &$data, &$output) {
        // Only run if module is enabled
        if (!$this->config->get('module_enhanced_stock_status_status')) {
            return;
        }
        if (empty($data['product']) || !is_array($data['product'])) {
            return;
        }
        require_once(DIR_SYSTEM . 'library/enhancedstockstatushandler.php');
        $handler = EnhancedStockStatusHandler::create($this->config, $this->db);
        $product = &$data['product'];
        if (!isset($product['stock_status_id']) || !$product['stock_status_id']) {
            // Try to find stock_status_id by name
            if (isset($product['stock_status'])) {
                $product['stock_status_id'] = $handler->getStockStatusIdByName($product['stock_status']);
            } else {
                $product['stock_status_id'] = 0;
            }
        }
        $stock_status_id = $product['stock_status_id'];
        $quantity = isset($product['quantity']) ? $product['quantity'] : 0;
        $enhanced_status = $handler->getEnhancedStockStatus($stock_status_id, $quantity);
        // Render HTML for listing
        $template_data = array('enhanced_stock_status' => $enhanced_status);
        $enhanced_html = $this->load->view('module/enhanced_stock_status', $template_data);
        $product['enhanced_stock_status_html'] = $enhanced_html;
        $product['enhanced_stock_status'] = $enhanced_status;

        $caption_pattern = '/(<div class="caption">)/';
        if (preg_match($caption_pattern, $output)) {
            $replacement = '$1' . $enhanced_html;
            $output = preg_replace($caption_pattern, $replacement, $output, 1);
        }
    }



    // Private method for controlled logging
    protected function logMessage($message): void {
        if (self::DEBUG_MODE) {
            //$this->log->write('EnhancedStockStatus: ' . $message);
            if (is_array($message)) {
                echo '<pre>'; 
                 print_r($message); 
                 echo '</pre>';
            }else{
                echo $message . "<br>"; // For debugging purposes, can be removed later
            }
        }
    }
}
