<?php

namespace Helpers;

/**
 * File Handler Helper
 * 
 * Handles file uploads and management
 */
class FileHandler
{
    protected string $uploadDir;
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected int $maxFileSize = 5242880; // 5MB

    public function __construct(string $uploadDir = '')
    {
        $this->uploadDir = $uploadDir ?: BASE_PATH . '/storage/uploads/';
    }

    /**
     * Handle file upload
     */
    public function upload($file, string $folder = ''): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('No file uploaded');
        }

        $this->validateFile($file);

        $uploadPath = $this->uploadDir . ($folder ? rtrim($folder, '/') . '/' : '');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fileName = $this->generateFileName($file['name']);
        $filePath = $uploadPath . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return str_replace(BASE_PATH, '', $filePath);
        }

        throw new \Exception('Failed to move uploaded file');
    }

    /**
     * Validate file
     */
    protected function validateFile(array $file): void
    {
        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception('File size exceeds limit');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception('File type not allowed');
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateFileName(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return uniqid('file_') . '_' . time() . '.' . $extension;
    }

    /**
     * Delete file
     */
    public function delete(string $filePath): bool
    {
        $fullPath = BASE_PATH . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Set allowed extensions
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set max file size
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
}
