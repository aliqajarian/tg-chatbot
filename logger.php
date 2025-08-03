<?php
// Simple logging class for the Telegram bot
class BotLogger {
    private $logFile;
    
    public function __construct($logFileName = 'bot.log') {
        $this->logFile = $logFileName;
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public function logRequest($update) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [REQUEST] " . json_encode($update) . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public function clearLog() {
        file_put_contents($this->logFile, '');
    }
    
    public function getLog() {
        if (file_exists($this->logFile)) {
            return file_get_contents($this->logFile);
        }
        return '';
    }
}

// Create a global logger instance
$logger = new BotLogger();
?>
