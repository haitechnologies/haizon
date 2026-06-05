<?php
/**
 * User Agent Filter
 * Blocks requests from known scraper tools and suspicious bots
 * 
 * Usage:
 *   require_once __DIR__ . '/user_agent_filter.php';
 *   blockSuspiciousUserAgent();
 */

class UserAgentFilter {
    private $blockedPatterns = [];
    private $allowedUserAgents = [];
    
    public function __construct() {
        // Common scraper tools and their user agents
        $this->blockedPatterns = [
            // Python libraries
            '/python-requests/i',
            '/requests\//i',
            '/urllib/i',
            '/httplib/i',
            
            // Scraping frameworks
            '/scrapy/i',
            '/beautifulsoup/i',
            '/mechanize/i',
            '/pyspider/i',
            
            // Browser automation
            '/selenium/i',
            '/phantomjs/i',
            '/headlesschrome/i',
            '/headless/i',
            '/puppeteer/i',
            '/playwright/i',
            '/watir/i',
            
            // Web testing tools
            '/jsoup/i',
            '/httpclient/i',
            '/okhttp/i',
            
            // Command line tools
            '/curl/i',
            '/wget/i',
            '/lynx/i',
            
            // Vulnerability scanners
            '/nikto/i',
            '/sqlmap/i',
            '/burp/i',
            '/nessus/i',
            '/openvas/i',
            '/masscan/i',
            
            // Suspicious patterns
            '/bot/i' => false, // Don't block all bots, just flag for checking
            '/crawler/i' => false,
            '/spider/i' => false,
            '/scrape/i',
            '/grab/i',
            '/fetch/i',
            '/download/i' => false,
            
            // Missing/empty user agent
            '/^\s*$/' => true, // Empty user agent
            '/^$/' => true,
            '/unknown/i',
            
            // Very old or suspicious versions
            '/version\/1\./i',
            '/mozilla\/1\./i',
        ];
        
        // Legitimate user agents to allow (whitelist)
        $this->allowedUserAgents = [
            // Legitimate browsers
            '/mozilla/i',
            '/opera/i',
            '/chrome/i',
            '/firefox/i',
            '/safari/i',
            '/edge/i',
            
            // Legitimate crawlers
            '/googlebot/i',
            '/bingbot/i',
            '/slurp/i',
            '/duckduckbot/i',
            '/baiduspider/i',
            '/yandexbot/i',
            
            // Mobile browsers
            '/iphone/i',
            '/android/i',
            '/ipad/i',
        ];
    }
    
    /**
     * Get current user agent
     */
    public function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Check if user agent is suspicious
     */
    public function isSuspicious() {
        $userAgent = $this->getUserAgent();
        
        // Empty is always suspicious
        if (empty($userAgent)) {
            return true;
        }
        
        // Check blocked patterns
        foreach ($this->blockedPatterns as $pattern => $mustBlock) {
            if (is_int($pattern)) {
                // Pattern is in value position
                $pattern = $mustBlock;
                $mustBlock = true;
            }
            
            if (preg_match($pattern, $userAgent)) {
                return $mustBlock;
            }
        }
        
        // Not in blocked list, likely legitimate
        return false;
    }
    
    /**
     * Is user agent from a known legitimate bot?
     */
    public function isLegitimateBot() {
        $userAgent = $this->getUserAgent();
        
        $legitimateBots = [
            '/googlebot/i',
            '/bing/i',
            '/yandex/i',
            '/baidu/i',
            '/applebot/i',
            '/slurp/i', // Yahoo
            '/duckduckbot/i',
        ];
        
        foreach ($legitimateBots as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get severity level of suspicious user agent
     * Returns: 'low', 'medium', 'high', 'critical'
     */
    public function getSeverity() {
        $userAgent = $this->getUserAgent();
        
        // Critical threats
        $critical = [
            '/sqlmap/i',
            '/nikto/i',
            '/burp/i',
            '/nessus/i',
        ];
        
        foreach ($critical as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'critical';
            }
        }
        
        // High threats (defined scrapers)
        $high = [
            '/scrapy/i',
            '/selenium/i',
            '/phantomjs/i',
            '/puppeteer/i',
            '/python-requests/i',
        ];
        
        foreach ($high as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'high';
            }
        }
        
        // Medium threats (tools that could be used for scraping)
        $medium = [
            '/curl/i',
            '/wget/i',
            '/httpclient/i',
        ];
        
        foreach ($medium as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'medium';
            }
        }
        
        // Low threats (unusual but not necessarily malicious)
        if (empty($userAgent)) {
            return 'low';
        }
        
        return 'none';
    }
    
    /**
     * Log suspicious activity
     */
    public function logSuspicious($severity = null) {
        $severity = $severity ?? $this->getSeverity();
        
        if ($severity === 'none') {
            return; // Don't log legitimate traffic
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $this->getUserAgent(),
            'url' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'severity' => $severity,
        ];
        
        // Log to file
        $logFile = __DIR__ . '/../logs/suspicious_ua.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Alert on critical threats
        if ($severity === 'critical') {
            // Could send email or alert here
            error_log("CRITICAL: Possible attack detected from " . $_SERVER['REMOTE_ADDR']);
        }
    }
}

/**
 * Global function to block suspicious user agents
 */
function blockSuspiciousUserAgent() {
    $filter = new UserAgentFilter();
    
    // Log all suspicious activity
    if ($filter->isSuspicious() && !$filter->isLegitimateBot()) {
        $filter->logSuspicious();
    }
    
    // Block high/critical threats
    $severity = $filter->getSeverity();
    if ($severity === 'critical' || $severity === 'high') {
        http_response_code(403);
        header('Content-Type: text/plain');
        die("Access denied: Automated access not permitted\n");
    }
    
    // Don't block medium threats, just log them
    // (curl/wget might be legitimate in some contexts)
}

/**
 * Check if user is using a suspicious user agent
 * (for conditional warnings/CAPTCHA)
 */
function isUserAgentSuspicious() {
    $filter = new UserAgentFilter();
    return $filter->isSuspicious() && !$filter->isLegitimateBot();
}

/**
 * Get user agent information for debugging
 */
function getUserAgentInfo() {
    $filter = new UserAgentFilter();
    
    return [
        'user_agent' => $filter->getUserAgent(),
        'is_suspicious' => $filter->isSuspicious(),
        'is_legitimate_bot' => $filter->isLegitimateBot(),
        'severity' => $filter->getSeverity(),
    ];
}
?>
