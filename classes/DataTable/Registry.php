<?php
/**
 * DataTable Handler Registry
 * 
 * Manages registration and loading of DataTable handlers
 * Maps module names to their corresponding handler classes
 * 
 * @package HAI\DataTable
 */

class DataTableRegistry
{
    /**
     * Handler registry mapping
     * @var array
     */
    private static $handlers = [];

    /**
     * Database connection
     * @var mysqli
     */
    private $mysqli;

    /**
     * Current user ID
     * @var int|null
     */
    private $userId;

    /**
     * Current user role ID
     * @var int|null
     */
    private $roleId;

    /**
     * Current organization ID
     * @var int|null
     */
    private $organizationId;

    /**
     * Constructor
     *
     * @param mysqli $mysqli Database connection
     * @param int|null $userId Current user ID
     * @param int|null $roleId Current user role ID
     * @param int|null $organizationId Current organization ID
     */
    public function __construct($mysqli, $userId = null, $roleId = null, $organizationId = null)
    {
        $this->mysqli = $mysqli;
        $this->userId = $userId;
        $this->roleId = $roleId;
        $this->organizationId = $organizationId;
        $this->registerDefaultHandlers();
    }

    /**
     * Register a DataTable handler
     *
     * @param string $module Module name (e.g., 'customers', 'invoices')
     * @param string $handlerClass Handler class name (e.g., 'CustomersDataTable')
     * @return void
     */
    public function register(string $module, string $handlerClass): void
    {
        self::$handlers[$module] = $handlerClass;
    }

    /**
     * Register multiple handlers at once
     *
     * @param array $handlers Array of ['module' => 'HandlerClass']
     * @return void
     */
    public function registerMultiple(array $handlers): void
    {
        foreach ($handlers as $module => $handlerClass) {
            $this->register($module, $handlerClass);
        }
    }

    /**
     * Get handler for a module
     *
     * @param string $module Module name
     * @return BaseDataTable|null Handler instance or null if not found
     */
    public function getHandler(string $module): ?BaseDataTable
    {
        $module = strtolower($module);

        $this->ensureModuleRegistration($module);

        // Check if module is registered
        if (!isset(self::$handlers[$module])) {
            error_log("DataTable: Unknown module '{$module}'");
            return null;
        }

        $handlerClass = self::$handlers[$module];

        $this->ensureHandlerClassLoaded($handlerClass);

        // Check if class exists
        if (!class_exists($handlerClass)) {
            error_log("DataTable: Handler class '{$handlerClass}' not found for module '{$module}'");
            return null;
        }

        try {
            // Instantiate handler with dependencies
            return new $handlerClass($this->mysqli, $this->userId, $this->roleId, $this->organizationId);
        } catch (\Throwable $e) {
            error_log("DataTable: Failed to instantiate handler for module '{$module}' [" . get_class($e) . "]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a DataTable request
     *
     * @param string $module Module name
     * @param array $requestData Request data
     * @return array Response data
     */
    public function process(string $module, array $requestData): array
    {
        // Get handler
        $handler = $this->getHandler($module);

        if (!$handler) {
            return [
                'success' => false,
                'error' => "No handler found for module '{$module}'",
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0
            ];
        }

        // Process request
        return $handler->process($requestData);
    }

    /**
     * Check if a module handler is registered
     *
     * @param string $module Module name
     * @return bool
     */
    public function isRegistered(string $module): bool
    {
        $module = strtolower($module);
        $this->ensureModuleRegistration($module);
        return isset(self::$handlers[$module]);
    }

    /**
     * Get registered handler class name for a module.
     *
     * @param string $module Module name
     * @return string|null Handler class name or null when not registered
     */
    public function getHandlerClass(string $module): ?string
    {
        $module = strtolower($module);
        $this->ensureModuleRegistration($module);
        return self::$handlers[$module] ?? null;
    }

    /**
     * Get list of all registered modules
     *
     * @return array Module names
     */
    public function getRegisteredModules(): array
    {
        return array_keys(self::$handlers);
    }

    /**
     * Register default handlers
     * 
     * Registers all available DataTable handlers
     * Handlers are created as individual classes and registered here
     * ✅ 30 out of 30 modules completed (100% - PHASE 2 COMPLETE!)
     *
     * @return void
     */
    private function registerDefaultHandlers(): void
    {
        // Phase 2 Week 1: Core module handlers (completed)
        $this->register('listing_customers', 'CustomersDataTable');
        $this->register('listing_invoices', 'InvoicesDataTable');
        $this->register('listing_users', 'UsersDataTable');
        $this->register('listing_blogs', 'BlogsDataTable');
        $this->register('listing_guest_posts', 'BlogsDataTable');
        
        // Phase 2 Week 2: Category, Content & Setup handlers (completed)
        $this->register('listing_categories', 'CategoriesDataTable');
        $this->register('listing_subcategories', 'SubcategoriesDataTable');
        $this->register('listing_pages', 'PagesDataTable');
        $this->register('listing_items', 'ItemsDataTable');
        $this->register('listing_blog_categories', 'BlogCategoriesDataTable');
        $this->register('listing_hscodes', 'HSCodesDataTable');
        $this->register('listing_roles', 'RolesDataTable');
        $this->register('listing_company_categories', 'CompanyCategoriesDataTable');
        $this->register('listing_organizations', 'OrganizationsDataTable');
        $this->register('listing_setup_sources', 'SetupSourcesDataTable');
        $this->register('listing_setup_statuses', 'SetupStatusesDataTable');
        $this->register('listing_modules', 'ModulesDataTable');
        
        // Phase 2 Week 2: Email & Audit handlers (completed)
        $this->register('listing_inquiries', 'InquiriesDataTable');
        $this->register('listing_alerts', 'AlertsDataTable');
        $this->register('listing_searches', 'SearchesDataTable');
        $this->register('listing_email_providers', 'EmailProvidersDataTable');
        $this->register('listing_email_campaigns', 'EmailCampaignsDataTable');
        $this->register('listing_authentication_activity', 'AuthenticationActivityDataTable');
        
        // Phase 2 Final: Last 6 handlers (completed - 100% complete!)
        $this->register('listing_setup_tags', 'SetupTagsDataTable');
        $this->register('listing_banned_words', 'BannedWordsDataTable');
        $this->register('listing_public_ads', 'PublicAdsDataTable');
        $this->register('listing_email_templates', 'EmailTemplatesDataTable');
        $this->register('listing_email_targets', 'EmailTargetsDataTable');
        $this->register('listing_email_history', 'EmailHistoryDataTable');
        $this->register('listing_email_queue', 'EmailQueueDataTable');
        $this->register('listing_email_unsubscribes', 'EmailUnsubscribesDataTable');
        $this->register('listing_email_bounces', 'EmailBouncesDataTable');
        $this->register('listing_email_events', 'EmailEventsDataTable');
        $this->register('listing_email_sends', 'EmailSendsDataTable');
        $this->register('listing_email_automation_rules', 'EmailAutomationRulesDataTable');
        $this->register('listing_email_automation_queue', 'EmailAutomationQueueDataTable');
        
        // Phase 3: Growth & Verification handlers
        // Referral and company engagement handlers removed (tables decommissioned)
        
        // Phase 4: Master Data & Configuration handlers
        $this->register('listing_geo_countries', 'GeoCountriesDataTable');
        $this->register('listing_geo_states', 'GeoStatesDataTable');
        $this->register('listing_geo_cities', 'GeoCitiesDataTable');
        $this->register('listing_hs_code_sets', 'HSCodeSetsDataTable');
        $this->register('listing_hs_code_texts', 'HSCodeTextsDataTable');
        $this->register('listing_category_hs_codes', 'CategoryHSCodesDataTable');

        // Phase 5: Advanced Features & Automation handlers
        // Import partners and companies details handlers removed (tables decommissioned)
        $this->register('listing_system_settings', 'SystemSettingsDataTable');
        $this->register('listing_email_automation_advanced', 'EmailAutomationAdvancedDataTable');
        
        $this->register('listing_ip_countries', 'IPCountriesDataTable');
        
        // Phase 3 Final: Customer & Payment handlers (completed)
        $this->register('listing_payment_methods', 'PaymentMethodsDataTable');
        $this->register('listing_customer_contacts', 'CustomerContactsDataTable');
        $this->register('listing_customer_documents', 'CustomerDocumentsDataTable');
        $this->register('listing_frontend_users', 'FrontendUsersDataTable');
        $this->register('listing_customer_addresses', 'CustomerAddressesDataTable');
        $this->register('listing_frontend_user_searches', 'FrontendUserSearchesDataTable');

        // Shipping module listings
        $this->register('listing_shipping_advices', 'ShippingAdvicesDataTable');
        $this->register('listing_shipping_invoices', 'ShippingInvoicesDataTable');
        $this->register('listing_shipping_stocks', 'ShippingStocksDataTable');
        $this->register('listing_ports', 'PortsDataTable');
        $this->register('listing_carriers', 'CarriersDataTable');
        $this->register('listing_consignees', 'ConsigneesDataTable');
        $this->register('listing_shippers', 'ShippersDataTable');
        
        // Phase 6: SEO & Utilities
        $this->register('sitemaps', 'SitemapsDataTable');

        // Security & Spam Prevention
        $this->register('listing_disposable_email_domains', 'DisposableEmailDomainsDataTable');
    }

    /**
     * Load handlers from configuration
     * Alternative to manual registration
     *
     * @param array $config Configuration array of handlers
     * @return void
     */
    public function loadFromConfig(array $config): void
    {
        $this->registerMultiple($config);
    }

    /**
     * Get handler statistics
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_handlers' => count(self::$handlers),
            'registered_modules' => $this->getRegisteredModules()
        ];
    }

    /**
     * Resolve handler class using the listing module naming convention.
     */
    private function resolveHandlerClassFromModule(string $module): ?string
    {
        if (strpos($module, 'listing_') !== 0) {
            return null;
        }

        $baseModule = substr($module, 8);
        if ($baseModule === '') {
            return null;
        }

        $classBase = str_replace(' ', '', ucwords(str_replace('_', ' ', $baseModule)));
        $handlerClass = $classBase . 'DataTable';
        $handlerFile = __DIR__ . '/' . $handlerClass . '.php';

        if (!is_file($handlerFile)) {
            return null;
        }

        return $handlerClass;
    }

    /**
     * Register convention-resolved handlers into the runtime map when available.
     */
    private function ensureModuleRegistration(string $module): void
    {
        if (isset(self::$handlers[$module])) {
            return;
        }

        $resolvedHandlerClass = $this->resolveHandlerClassFromModule($module);
        if ($resolvedHandlerClass !== null) {
            $this->register($module, $resolvedHandlerClass);
        }
    }

    /**
     * Load handler class file on demand for convention-based handlers.
     */
    private function ensureHandlerClassLoaded(string $handlerClass): void
    {
        if (class_exists($handlerClass, false)) {
            return;
        }

        $handlerFile = __DIR__ . '/' . $handlerClass . '.php';
        if (is_file($handlerFile)) {
            require_once $handlerFile;
        }
    }
}
