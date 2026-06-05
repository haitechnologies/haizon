<?php
/**
 * Base Cron Job Class
 * 
 * All cron jobs should extend this class to ensure:
 * - CLI-only execution
 * - Consistent logging
 * - Error handling
 * - Performance monitoring
 * 
 * @author Development Team
 * @date 2026-02-18
 */

abstract class CronJobBase {
    /**
     * @var mysqli Database connection
     */
    protected $mysqli;
    
    /**
     * @var string Path to log file
     */
    protected $logFile;
    
    /**
     * @var float Job start time
     */
    protected $startTime;
    
    /**
     * @var int Number of items processed
     */
    protected $processedCount = 0;
    
    /**
     * @var int Number of errors encountered
     */
    protected $errorCount = 0;
    
    /**
     * Constructor
     * 
     * @param mysqli $mysqli Database connection
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->startTime = microtime(true);
        $this->validateCLI();
        $this->bootstrapErrorLogger();
        $this->ensureLogDirectory();

        if (function_exists('backend_log_coverage_heartbeat')) {
            backend_log_coverage_heartbeat([
                'module' => $this->getJobName(),
                'module_slug' => 'cron',
                'entrypoint_type' => 'cron',
            ]);
        }
    }

    /**
     * Load central dashboard error logger when available.
     */
    protected function bootstrapErrorLogger() {
        $loggerPath = __DIR__ . '/../admin_elements/error_logger.php';
        if (file_exists($loggerPath)) {
            require_once $loggerPath;
        }
    }
    
    /**
     * Ensure only CLI execution
     * Prevents web-based execution for security
     */
    private function validateCLI() {
        if (php_sapi_name() !== 'cli') {
            http_response_code(403);
            die('CLI only - This script can only be run from command line');
        }
    }
    
    /**
     * Ensure log directory exists and set log file path
     */
    protected function ensureLogDirectory() {
        $logDir = __DIR__ . '/../logs/cron';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/' . $this->getJobName() . '_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log a message to file and console
     * 
     * @param string $message Message to log
     * @param string $level Log level (INFO, WARNING, ERROR, SUCCESS, START, END)
     */
    protected function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        // Write to file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        
        // Output to console with color coding for different levels
        $colorCodes = [
            'INFO'    => "\033[0m",    // Default
            'SUCCESS' => "\033[0;32m", // Green
            'WARNING' => "\033[0;33m", // Yellow
            'ERROR'   => "\033[0;31m", // Red
            'START'   => "\033[0;36m", // Cyan
            'END'     => "\033[0;35m", // Magenta
        ];
        
        $color = isset($colorCodes[$level]) ? $colorCodes[$level] : $colorCodes['INFO'];
        $reset = "\033[0m";
        
        echo $color . $logMessage . $reset;

        if (function_exists('log_error') && in_array($level, ['ERROR', 'WARNING'], true)) {
            $severity = $level === 'ERROR' ? 'ERROR' : 'WARNING';
            log_error('[Cron:' . $this->getJobName() . '] ' . $message, $severity, __FILE__, __LINE__, [
                'module' => $this->getJobName(),
                'module_slug' => 'cron',
                'entrypoint_type' => 'cron',
                'source_channel' => 'cron_runtime',
            ]);
        }
    }
    
    /**
     * Get job execution summary
     * 
     * @return string Formatted summary with duration and memory usage
     */
    protected function getSummary() {
        $duration = round(microtime(true) - $this->startTime, 2);
        $memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        
        $summary = "Duration: {$duration}s, Peak Memory: {$memory}MB";
        
        if ($this->processedCount > 0) {
            $summary .= ", Processed: {$this->processedCount}";
        }
        
        if ($this->errorCount > 0) {
            $summary .= ", Errors: {$this->errorCount}";
        }
        
        return $summary;
    }
    
    /**
     * Increment processed count
     * 
     * @param int $count Number to increment by (default: 1)
     */
    protected function incrementProcessed($count = 1) {
        $this->processedCount += $count;
    }
    
    /**
     * Increment error count
     * 
     * @param int $count Number to increment by (default: 1)
     */
    protected function incrementErrors($count = 1) {
        $this->errorCount += $count;
    }
    
    /**
     * Safe database query with error logging
     * 
     * @param string $query SQL query
     * @return mysqli_result|bool Query result or false on failure
     */
    protected function safeQuery($query) {
        $result = $this->mysqli->query($query);
        
        if (!$result) {
            $this->log("Query failed: " . $this->mysqli->error, 'ERROR');
            $this->log("Query: " . substr($query, 0, 200), 'ERROR');
            $this->incrementErrors();
            return false;
        }
        
        return $result;
    }
    
    /**
     * Execute the cron job (abstract - must be implemented by child classes)
     * 
     * @return void
     */
    abstract public function execute();
    
    /**
     * Get job name for logging (abstract - must be implemented by child classes)
     * 
     * @return string Job name (e.g., 'email_queue_worker')
     */
    abstract protected function getJobName();
    
    /**
     * Run the job with error handling
     * Wraps execute() with try-catch and logging
     * 
     * @return void
     */
    public function run() {
        try {
            $this->log('Starting ' . $this->getJobName(), 'START');
            $this->execute();
            $this->log('Completed ' . $this->getJobName() . ' - ' . $this->getSummary(), 'END');
        } catch (Exception $e) {
            $this->log('Fatal error: ' . $e->getMessage(), 'ERROR');
            $this->log('Stack trace: ' . $e->getTraceAsString(), 'ERROR');
            $this->incrementErrors();
            exit(1);
        }
    }
}
