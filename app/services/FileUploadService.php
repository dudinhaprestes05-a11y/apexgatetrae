<?php

require_once __DIR__ . '/../models/SellerDocument.php';
require_once __DIR__ . '/../models/Log.php';

class FileUploadService {
    private $sellerDocumentModel;
    private $logModel;
    private $uploadPath;
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    private $maxFileSize = 10485760;

    public function __construct() {
        $this->sellerDocumentModel = new SellerDocument();
        $this->logModel = new Log();
        $this->uploadPath = BASE_PATH . '/uploads/documents/sellers/';

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0777, true);
        }
    }

    public function uploadDocument($file, $sellerId, $documentType) {
        try {
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new Exception('Invalid file parameters');
            }

            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No file sent');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File size exceeds limit');
                default:
                    throw new Exception('Unknown upload error');
            }

            if ($file['size'] > $this->maxFileSize) {
                throw new Exception('File size exceeds ' . ($this->maxFileSize / 1048576) . 'MB');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $this->allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: JPG, PNG, PDF');
            }

            $ext = $this->getExtensionFromMimeType($mimeType);
            $fileName = $this->generateFileName($sellerId, $documentType, $ext);
            $sellerPath = $this->uploadPath . $sellerId . '/';

            if (!is_dir($sellerPath)) {
                mkdir($sellerPath, 0777, true);
            }

            $fullPath = $sellerPath . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            chmod($fullPath, 0644);

            $documentId = $this->sellerDocumentModel->uploadDocument(
                $sellerId,
                $documentType,
                $fileName,
                $fullPath,
                $file['size'],
                $mimeType
            );

            $this->logModel->info('upload', 'Document uploaded successfully', [
                'seller_id' => $sellerId,
                'document_id' => $documentId,
                'document_type' => $documentType,
                'file_size' => $file['size']
            ]);

            return [
                'success' => true,
                'document_id' => $documentId,
                'file_name' => $fileName
            ];

        } catch (Exception $e) {
            $this->logModel->error('upload', 'Document upload failed', [
                'seller_id' => $sellerId,
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateFileName($sellerId, $documentType, $ext) {
        return sprintf(
            'seller_%d_%s_%s.%s',
            $sellerId,
            $documentType,
            date('YmdHis'),
            $ext
        );
    }

    private function getExtensionFromMimeType($mimeType) {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf'
        ];

        return $map[$mimeType] ?? 'bin';
    }

    public function getDocumentPath($documentId) {
        $document = $this->sellerDocumentModel->find($documentId);

        if (!$document) {
            return null;
        }

        return $document['file_path'];
    }

    public function deleteDocument($documentId) {
        $document = $this->sellerDocumentModel->find($documentId);

        if (!$document) {
            return false;
        }

        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }

        return $this->sellerDocumentModel->delete($documentId);
    }

    public function validateDocumentAccess($documentId, $sellerId) {
        $document = $this->sellerDocumentModel->find($documentId);

        if (!$document) {
            return false;
        }

        return $document['seller_id'] == $sellerId;
    }
}
