<?php

class FileUpload {
    private $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    private $maxSize = 5 * 1024 * 1024; // 5MB
    private $uploadDir;

    public function __construct() {
        $this->uploadDir = __DIR__ . '/../public/uploads/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a single file
     */
    public function upload($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds 5MB limit');
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate extension
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG');
        }

        // Validate MIME type (additional security)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid file type detected');
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($extension);
        $filepath = $this->uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return $filename;
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple($files) {
        $uploadedFiles = [];

        // Check if files array is valid
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $uploadedFiles;
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            // Skip if no file uploaded
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Create single file array
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            try {
                $uploadedFiles[] = $this->upload($file);
            } catch (Exception $e) {
                // Log error but continue with other files
                error_log("File upload error: " . $e->getMessage());
            }
        }

        return $uploadedFiles;
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension) {
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Delete a file
     */
    public function delete($filename) {
        $filepath = $this->uploadDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Get file path
     */
    public function getFilePath($filename) {
        return $this->uploadDir . $filename;
    }
}