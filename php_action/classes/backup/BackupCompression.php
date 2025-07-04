<?php
/**
 * BackupCompression Class
 * Handles compression and decompression of backup files
 */
class BackupCompression {
    private $compressionLevel;
    private $log = [];
    private $config;

    /**
     * Constructor
     * @param int $compressionLevel Compression level (1-9, default 6)
     */
    public function __construct($compressionLevel = 6) {
        $this->compressionLevel = max(1, min(9, $compressionLevel));
        $this->config = ConfigurationManager::getInstance();
    }

    /**
     * Compress a file
     * @param string $sourceFile Path to source file
     * @param string $destinationFile Path to destination file (optional)
     * @return array Result of compression operation
     */
    public function compress($sourceFile, $destinationFile = null) {
        try {
            if (!file_exists($sourceFile)) {
                throw new Exception("Source file not found: $sourceFile");
            }

            // If no destination specified, create one with .gz extension
            if ($destinationFile === null) {
                $destinationFile = $sourceFile . '.gz';
            }

            // Open source file
            $sourceHandle = fopen($sourceFile, 'rb');
            if ($sourceHandle === false) {
                throw new Exception("Failed to open source file: $sourceFile");
            }

            // Open destination file
            $destinationHandle = gzopen($destinationFile, 'wb' . $this->compressionLevel);
            if ($destinationHandle === false) {
                fclose($sourceHandle);
                throw new Exception("Failed to create compressed file: $destinationFile");
            }

            // Copy data
            while (!feof($sourceHandle)) {
                $buffer = fread($sourceHandle, 4096);
                gzwrite($destinationHandle, $buffer);
            }

            // Close files
            fclose($sourceHandle);
            gzclose($destinationHandle);

            // Verify compression
            if (!file_exists($destinationFile) || filesize($destinationFile) === 0) {
                throw new Exception("Compression failed: Output file is empty or missing");
            }

            $this->log[] = "Successfully compressed: $sourceFile to $destinationFile";
            
            return [
                'success' => true,
                'source_size' => filesize($sourceFile),
                'compressed_size' => filesize($destinationFile),
                'compression_ratio' => round((1 - filesize($destinationFile) / filesize($sourceFile)) * 100, 2),
                'destination_file' => $destinationFile
            ];

        } catch (Exception $e) {
            $this->log[] = "Compression error: " . $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Decompress a file
     * @param string $sourceFile Path to compressed file
     * @param string $destinationFile Path to destination file (optional)
     * @return array Result of decompression operation
     */
    public function decompress($sourceFile, $destinationFile = null) {
        try {
            if (!file_exists($sourceFile)) {
                throw new Exception("Compressed file not found: $sourceFile");
            }

            // If no destination specified, remove .gz extension
            if ($destinationFile === null) {
                $destinationFile = preg_replace('/\.gz$/', '', $sourceFile);
            }

            // Open compressed file
            $sourceHandle = gzopen($sourceFile, 'rb');
            if ($sourceHandle === false) {
                throw new Exception("Failed to open compressed file: $sourceFile");
            }

            // Open destination file
            $destinationHandle = fopen($destinationFile, 'wb');
            if ($destinationHandle === false) {
                gzclose($sourceHandle);
                throw new Exception("Failed to create decompressed file: $destinationFile");
            }

            // Copy data
            while (!gzeof($sourceHandle)) {
                $buffer = gzread($sourceHandle, 4096);
                fwrite($destinationHandle, $buffer);
            }

            // Close files
            gzclose($sourceHandle);
            fclose($destinationHandle);

            // Verify decompression
            if (!file_exists($destinationFile) || filesize($destinationFile) === 0) {
                throw new Exception("Decompression failed: Output file is empty or missing");
            }

            $this->log[] = "Successfully decompressed: $sourceFile to $destinationFile";
            
            return [
                'success' => true,
                'compressed_size' => filesize($sourceFile),
                'decompressed_size' => filesize($destinationFile),
                'destination_file' => $destinationFile
            ];

        } catch (Exception $e) {
            $this->log[] = "Decompression error: " . $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get compression logs
     * @return array Array of log messages
     */
    public function getLog() {
        return $this->log;
    }

    /**
     * Clear compression logs
     */
    public function clearLog() {
        $this->log = [];
    }

    /**
     * Set compression level
     * @param int $level Compression level (1-9)
     */
    public function setCompressionLevel($level) {
        $this->compressionLevel = max(1, min(9, $level));
    }

    /**
     * Get current compression level
     * @return int Current compression level
     */
    public function getCompressionLevel() {
        return $this->compressionLevel;
    }
} 