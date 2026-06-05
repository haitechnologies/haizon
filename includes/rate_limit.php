<?php
/**
 * Rate Limiting System
 * Prevents aggressive scraping by limiting requests per IP
 * 
 * Usage:
 *   require_once __DIR__ . '/rate_limit.php';
 *   checkRateLimit($_SERVER['REMOTE_ADDR']);
 */

class RateLimiter {
    private $storageDir;
    private $requestsPerMinute = 60;
    private $requestsPerHour = 1000;
    private $cookieName = 'rate_limit_check';
    
    public function __construct($storageDir = null) {
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/rate_limit';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Check rate limit for given IP
     * 
     * @param string $ip Client IP address
     * @return bool True if within limit, false if exceeded
     */
    public function check($ip) {
        // Get or create rate limit file
        $file = $this->getFile($ip);
        $data = $this->readData($file);
        $now = time();
        
        // Check minute limit
        if ($this->isWithinWindow($data['minute_start'], 60)) {
            if ($data['minute_count'] >= $this->requestsPerMinute) {
                // Rate limit exceeded for minute
                http_response_code(429);
                header('Retry-After: ' . (60 - ($now - $data['minute_start'])));
                return false;
            }
            $data['minute_count']++;
        } else {
            // Reset minute window
            $data['minute_start'] = $now;
            $data['minute_count'] = 1;
        }
        
        // Check hour limit
        if ($this->isWithinWindow($data['hour_start'], 3600)) {
            if ($data['hour_count'] >= $this->requestsPerHour) {
                // Rate limit exceeded for hour
                http_response_code(429);
                header('Retry-After: ' . (3600 - ($now - $data['hour_start'])));
                return false;
            }
            $data['hour_count']++;
        } else {
            // Reset hour window
            $data['hour_start'] = $now;
            $data['hour_count'] = 1;
        }
        
        // Save updated data
        $this->writeData($file, $data);
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $this->requestsPerMinute);
        header('X-RateLimit-Remaining: ' . max(0, $this->requestsPerMinute - $data['minute_count']));
        header('X-RateLimit-Reset: ' . ($data['minute_start'] + 60));
        
        return true;
    }
    
    /**
     * Get storage file for IP
     */
    private function getFile($ip) {
        return $this->storageDir . '/' . md5($ip) . '.json';
    }
    
    /**
     * Read rate limit data for IP
     */
    private function readData($file) {
        if (!file_exists($file)) {
            return [
                'minute_count' => 0,
                'minute_start' => time(),
                'hour_count' => 0,
                'hour_start' => time(),
                'created' => time()
            ];
        }
        
        return json_decode(file_get_contents($file), true);
    }
    
    /**
     * Write rate limit data
     */
    private function writeData($file, $data) {
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Check if within time window
     */
    private function isWithinWindow($startTime, $windowSize) {
        return (time() - $startTime) < $windowSize;
    }
    
    /**
     * Whitelist IP (bypass rate limiting)
     */
    public function whitelist($ip) {
        $file = $this->storageDir . '/whitelist.json';
        
        $whitelist = [];
        if (file_exists($file)) {
            $whitelist = json_decode(file_get_contents($file), true);
        }
        
        if (!in_array($ip, $whitelist)) {
            $whitelist[] = $ip;
            file_put_contents($file, json_encode($whitelist), LOCK_EX);
        }
    }
    
    /**
     * Is IP whitelisted?
     */
    public function isWhitelisted($ip) {
        $file = $this->storageDir . '/whitelist.json';
        
        if (!file_exists($file)) {
            return false;
        }
        
        $whitelist = json_decode(file_get_contents($file), true);
        return in_array($ip, $whitelist);
    }
    
    /**
     * Set custom limits
     */
    public function setLimits($perMinute, $perHour) {
        $this->requestsPerMinute = $perMinute;
        $this->requestsPerHour = $perHour;
    }
    
    /**
     * Get stats for IP
     */
    public function getStats($ip) {
        $file = $this->getFile($ip);
        return $this->readData($file);
    }
}

/**
 * Global function for easy usage
 */
function checkRateLimit($ip = null) {
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    
    // Skip localhost
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }
    
    $limiter = new RateLimiter();
    
    // Whitelist legitimate services
    $whitelist = [
        // Google crawler
        '66.249.64.0/19', // Googlebot range
        // Your own IPs
        // '123.45.67.89',
    ];
    
    // Check whitelist
    foreach ($whitelist as $entry) {
        if (strpos($entry, '/') !== false) {
            // CIDR range
            if (ipInCIDR($ip, $entry)) {
                return true;
            }
        } else {
            // Single IP
            if ($ip === $entry) {
                return true;
            }
        }
    }
    
    return $limiter->check($ip);
}

/**
 * Check if IP is in CIDR range
 */
function ipInCIDR($ip, $cidr) {
    list($subnet, $bits) = explode('/', $cidr);
    
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    
    $subnet &= $mask;
    return ($ip & $mask) == $subnet;
}
?>
