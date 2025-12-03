<?php

require_once __DIR__ . '/BaseModel.php';

class SellerDocument extends BaseModel {
    protected $table = 'seller_documents';

    public function getDocumentsBySeller($sellerId) {
        return $this->where(['seller_id' => $sellerId], 'created_at DESC');
    }

    public function getDocumentByType($sellerId, $documentType) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE seller_id = ? AND document_type = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$sellerId, $documentType]);
        return $stmt->fetch();
    }

    public function getPendingDocuments($limit = null) {
        return $this->where(['status' => 'pending'], 'created_at ASC', $limit);
    }

    public function getUnderReviewDocuments($limit = null) {
        return $this->where(['status' => 'under_review'], 'created_at ASC', $limit);
    }

    public function approveDocument($documentId, $reviewedBy) {
        return $this->update($documentId, [
            'status' => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => null
        ]);
    }

    public function rejectDocument($documentId, $reviewedBy, $reason) {
        return $this->update($documentId, [
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason
        ]);
    }

    public function countByStatus($sellerId, $status) {
        return $this->count([
            'seller_id' => $sellerId,
            'status' => $status
        ]);
    }

    public function getAllDocumentsForSeller($sellerId) {
        $stmt = $this->db->prepare("
            SELECT
                document_type,
                MAX(id) as latest_id,
                MAX(created_at) as latest_upload
            FROM {$this->table}
            WHERE seller_id = ?
            GROUP BY document_type
        ");

        $stmt->execute([$sellerId]);
        $results = $stmt->fetchAll();

        $documents = [];
        foreach ($results as $row) {
            $documents[] = $this->find($row['latest_id']);
        }

        return $documents;
    }

    public function hasAllRequiredDocuments($sellerId, $personType) {
        $required = $this->getRequiredDocumentTypes($personType);

        foreach ($required as $docType) {
            $doc = $this->getDocumentByType($sellerId, $docType);
            if (!$doc || $doc['status'] !== 'approved') {
                return false;
            }
        }

        return true;
    }

    public function getRequiredDocumentTypes($personType) {
        if ($personType === 'individual') {
            return ['rg_front', 'rg_back', 'cpf', 'selfie', 'proof_address'];
        } else {
            return ['social_contract', 'cnpj', 'partner_docs', 'proof_address'];
        }
    }

    public function uploadDocument($sellerId, $documentType, $fileName, $filePath, $fileSize, $mimeType) {
        $data = [
            'seller_id' => $sellerId,
            'document_type' => $documentType,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => 'pending'
        ];

        return $this->create($data);
    }
}
