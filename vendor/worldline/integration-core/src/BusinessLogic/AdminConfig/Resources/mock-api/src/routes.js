const applicationController = require('./controller.js');

const setRoutes = (server) => {
    /**
     * shops
     *
     * Endpoints for retrieving shop information
     */
    // Retrieves all available stores in the system
    server.get('/shops/stores', applicationController.getStores);
    // Retrieves the current store
    server.get('/shops/stores/current', applicationController.getCurrentStore);
    // Get available store order status
    server.get('/shop/stores/:storeId/orderStatuses', applicationController.getOrderStatuses);

    /**
     * integrations
     *
     * Endpoints fore retrieving integration information
     */
    // Get information about integration versions
    server.get('/integration/version', applicationController.getVersion);
    // Retrieve integration state
    server.get('/integration/stores/:storeId/state', applicationController.getStoreState);
    /**
     * connection
     *
     * Endpoints for connection with Adyen
     */
    // Get connection settings
    server.get('/integration/store/:storeId/connection', applicationController.getConnection);
    // Authorize with Adyen
    server.post('/integration/store/:storeId/connection', applicationController.setStoreConnection);
    // Updates merchant connection with Adyen
    server.put('/integration/store/:storeId/connection', applicationController.setStoreConnection);
    // Disconnect merchant from Adyen
    server.delete('/integration/store/:storeId/connection', applicationController.deleteStoreConnection);
    // Get merchant accounts for provided API key and environment
    server.get('/integration/store/:storeId/merchants', applicationController.getMerchants);
    // Validates connection with Adyen
    server.post('/integration/store/:storeId/connection/test', applicationController.validateConnection);
    /**
     * general settings
     *
     * Endpoints for plugin general settings
     */
    // Get plugin general settings
    server.get('/integration/store/:storeId', applicationController.getGeneralSettings);
    // Save plugin general settings
    server.put('/integration/store/:storeId', applicationController.saveGeneralSettings);
    /**
     * Adyen giving
     *
     * Endpoints for Adyen giving settings
     */
    // Get Adyen giving settings
    server.get('/integration/store/:storeId/giving', applicationController.getAdyenGivingSettings);
    // Save Adyen giving settings
    server.put('/integration/store/:storeId/giving', applicationController.saveAdyenGivingSettings);
    /**
     * order settings
     *
     * Endpoints for order mappings
     */
    // Get order status mappings
    server.get('/integration/store/:storeId/orderMappings', applicationController.getOrderMappingSettings);
    // Save order status mappings
    server.put('/integration/store/:storeId/orderMappings', applicationController.saveOrderMappingSettings);
    /**
     * info settings
     *
     * Endpoints for plugin system info settings
     */
    // Get system information
    server.get('/integration/system/info', applicationController.getSystemInfo);
    // Change debug mode - enable or disable
    server.put('/integration/system/info', applicationController.saveSystemInfo);
    // Validates if tasks are being executed
    server.post('/integration/system/info/validate', applicationController.startIntegrationValidationTask);
    // Check if auto test task is completed
    server.get('/integration/system/:queueItemId/info/validate', applicationController.getIntegrationValidationStatus);
    // Downloads system information
    server.get('/integration/system/info/report', applicationController.getCurrentStore);
    // Downloads integration validation report
    server.get('/integration/system/info/validate/report', applicationController.getCurrentStore);
    // Validates whether webhooks are received from Adyen to the store
    server.post('/integration/store/:storeId/webhook/validate', applicationController.webhookValidation);
    // Downloads webhook validation report
    server.post('/integration/store/:storeId/webhook/report', applicationController.getCurrentStore);
    /**
     * notifications
     *
     * Endpoints for notifications hub
     */
    // Get shop event notifications
    server.get('/integrations/store/:storeId/notifications/event', applicationController.getShopEventsNotifications);
    // Get webhook event notifications
    server.get(
        '/integrations/store/:storeId/notifications/webhooks',
        applicationController.getWebhookEventsNotifications
    );
    /**
     * payment
     *
     * Endpoints for payment methods
     */
    // Get configured payment methods
    server.get('/integrations/store/:storeId/paymentConfigurations', applicationController.getActivePaymentMethods);
    // Save payment method configuration
    server.post('/integrations/store/:storeId/paymentConfigurations', applicationController.addPaymentMethod);
    // Get payment method details
    server.get('/integrations/store/:storeId/paymentConfigurations/:methodId', applicationController.getPaymentMethod);
    // Save payment method details
    server.put('/integrations/store/:storeId/paymentConfigurations/:methodId', applicationController.savePaymentMethod);
    // Delete payment method details
    server.delete(
        '/integrations/store/:storeId/paymentConfigurations/:methodId',
        applicationController.deletePaymentMethod
    );
    // Get all currently available and supported methods
    server.get(
        '/integrations/store/:storeId/availablePaymentMethods',
        applicationController.getAvailablePaymentMethods
    );
};

module.exports = { setRoutes };
