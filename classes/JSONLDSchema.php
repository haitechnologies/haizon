<?php
/**
 * JSON-LD Schema Generator for AI Optimization
 * 
 * Usage: Include this file on pages to generate appropriate schema markup
 * Example: $schema = new JSONLDSchema('organization');
 */

class JSONLDSchema {
    private $type;
    private $data = [];
    
    public function __construct($type = 'organization') {
        $this->type = $type;
        $this->initializeDefaults();
    }
    
    /**
     * Initialize default schema structure
     */
    private function initializeDefaults() {
        $this->data = [
            '@context' => 'https://schema.org',
            '@type' => ucfirst($this->type),
        ];
    }
    
    /**
     * Set Organization Schema (for homepage)
     */
    public static function organization($name, $url, $logo = '', $description = '') {
        $schema = new self('organization');
        $schema->data['name'] = $name;
        $schema->data['url'] = $url;
        $schema->data['description'] = $description;
        
        if (!empty($logo)) {
            $schema->data['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo,
                'width' => 300,
                'height' => 100
            ];
        }
        
        $schema->data['contactPoint'] = [
            '@type' => 'ContactPoint',
            'contactType' => 'Customer Service',
            'email' => 'info@yourdomain.com',
            'telephone' => '+971-4-xxx-xxxx'
        ];
        
        $schema->data['address'] = [
            '@type' => 'PostalAddress',
            'addressCountry' => 'AE',
            'addressRegion' => 'Dubai'
        ];
        
        $schema->data['areaServed'] = [
            '@type' => 'Place',
            'name' => 'United Arab Emirates',
            'geo' => [
                '@type' => 'GeoShape',
                'box' => '23.9 53.3 26.3 55.4'  // UAE bounding box
            ]
        ];
        
        return $schema;
    }
    
    /**
     * Set LocalBusiness Schema (for company listings)
     */
    public static function localBusiness($company) {
        $schema = new self('LocalBusiness');
        $schema->data['name'] = $company['company_name'] ?? '';
        $schema->data['description'] = $company['description'] ?? '';
        $schema->data['url'] = $company['website'] ?? '';
        $schema->data['phone'] = $company['phone'] ?? '';
        $schema->data['email'] = $company['email'] ?? '';
        
        // Address
        $schema->data['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $company['address'] ?? '',
            'addressLocality' => $company['city'] ?? 'Dubai',
            'addressRegion' => $company['city'] ?? 'Dubai',
            'postalCode' => $company['postal_code'] ?? '',
            'addressCountry' => 'AE'
        ];
        
        // Geo location
        if (!empty($company['latitude']) && !empty($company['longitude'])) {
            $schema->data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float)$company['latitude'],
                'longitude' => (float)$company['longitude']
            ];
        }
        
        // Business category
        if (!empty($company['category'])) {
            $schema->data['additionalType'] = [
                '@type' => 'BusinessCategory',
                'name' => $company['category']
            ];
        }
        
        // Reviews if available
        if (!empty($company['reviews'])) {
            $schema->data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $company['reviews']['average'] ?? 0,
                'reviewCount' => $company['reviews']['count'] ?? 0
            ];
        }
        
        return $schema;
    }
    
    /**
     * Set BreadcrumbList Schema (for navigation)
     */
    public static function breadcrumbs($items) {
        $schema = new self('BreadcrumbList');
        $schema->data['itemListElement'] = [];
        
        foreach ($items as $index => $item) {
            $schema->data['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url']
            ];
        }
        
        return $schema;
    }
    
    /**
     * Set Product/Service Schema (for category pages)
     */
    public static function product($name, $description, $category = '', $image = '') {
        $schema = new self('Product');
        $schema->data['name'] = $name;
        $schema->data['description'] = $description;
        $schema->data['category'] = $category;
        
        if (!empty($image)) {
            $schema->data['image'] = $image;
        }
        
        return $schema;
    }
    
    /**
     * Set Article Schema (for blog posts)
     */
    public static function article($headline, $content, $image = '', $author = '') {
        $schema = new self('Article');
        $schema->data['headline'] = $headline;
        $schema->data['articleBody'] = $content;
        $schema->data['datePublished'] = date('c');
        $schema->data['dateModified'] = date('c');
        
        if (!empty($image)) {
            $schema->data['image'] = $image;
        }
        
        if (!empty($author)) {
            $schema->data['author'] = [
                '@type' => 'Person',
                'name' => $author
            ];
        }
        
        return $schema;
    }
    
    /**
     * Set FAQPage Schema (for FAQ pages)
     */
    public static function faqPage($faqs = []) {
        $schema = new self('FAQPage');
        $schema->data['mainEntity'] = [];
        
        foreach ($faqs as $item) {
            $schema->data['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer']
                ]
            ];
        }
        
        return $schema;
    }
    
    /**
     * Add custom property
     */
    public function addProperty($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Add nested object
     */
    public function addObject($key, $type, $properties = []) {
        $object = [
            '@type' => $type
        ];
        
        foreach ($properties as $pKey => $pValue) {
            $object[$pKey] = $pValue;
        }
        
        $this->data[$key] = $object;
        return $this;
    }
    
    /**
     * Get JSON-LD script tag for HTML header
     */
    public function getScriptTag() {
        return '<script type="application/ld+json">'.
               json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).
               '</script>';
    }
    
    /**
     * Get raw JSON (for API responses)
     */
    public function getJSON() {
        return json_encode($this->data, JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Get array (for processing)
     */
    public function getArray() {
        return $this->data;
    }
    
    /**
     * Set to global variable for header inclusion
     */
    public function setGlobal() {
        global $jsonLdSchema;
        $jsonLdSchema = $this->getScriptTag();
        return $this;
    }
}

// Helper function for easy usage
function createSchema($type, ...$args) {
    switch ($type) {
        case 'organization':
            return JSONLDSchema::organization(...$args);
        case 'local-business':
            return JSONLDSchema::localBusiness(...$args);
        case 'breadcrumbs':
            return JSONLDSchema::breadcrumbs(...$args);
        case 'product':
            return JSONLDSchema::product(...$args);
        case 'article':
            return JSONLDSchema::article(...$args);
        case 'faq':
            return JSONLDSchema::faqPage(...$args);
        default:
            return new JSONLDSchema($type);
    }
}

// Usage Examples:
/*
// On homepage
$schema = JSONLDSchema::organization(
    'UAE Business Directory',
    'https://yourdomain.com',
    'https://yourdomain.com/logo.png',
    'Comprehensive directory of businesses across UAE'
);
$schema->setGlobal();

// On company detail page
$company = [ /* company data from database * /];
$schema = JSONLDSchema::localBusiness($company);
$schema->setGlobal();

// On category page
$breadcrumbs = [
    ['name' => 'Home', 'url' => 'https://yourdomain.com'],
    ['name' => 'Categories', 'url' => 'https://yourdomain.com/categories'],
    ['name' => 'Business Services', 'url' => 'https://yourdomain.com/categories/business-services']
];
$schema = JSONLDSchema::breadcrumbs($breadcrumbs);
$schema->setGlobal();
*/
?>
