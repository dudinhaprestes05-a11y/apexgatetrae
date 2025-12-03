<?php

require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel {
    protected $table = 'notifications';

    public function createNotification($userId, $sellerId, $type, $title, $message, $link = null) {
        $data = [
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0
        ];

        return $this->create($data);
    }

    public function getUnreadByUser($userId) {
        return $this->where([
            'user_id' => $userId,
            'is_read' => 0
        ], 'created_at DESC');
    }

    public function getUnreadBySeller($sellerId) {
        return $this->where([
            'seller_id' => $sellerId,
            'is_read' => 0
        ], 'created_at DESC');
    }

    public function getRecentByUser($userId, $limit = 10) {
        return $this->where(['user_id' => $userId], 'created_at DESC', $limit);
    }

    public function getRecentBySeller($sellerId, $limit = 10) {
        return $this->where(['seller_id' => $sellerId], 'created_at DESC', $limit);
    }

    public function markAsRead($notificationId) {
        return $this->update($notificationId, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markAllAsReadByUser($userId) {
        $sql = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
        return $this->execute($sql, [$userId]);
    }

    public function markAllAsReadBySeller($sellerId) {
        $sql = "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE seller_id = ? AND is_read = 0";
        return $this->execute($sql, [$sellerId]);
    }

    public function countUnreadByUser($userId) {
        return $this->count([
            'user_id' => $userId,
            'is_read' => 0
        ]);
    }

    public function countUnreadBySeller($sellerId) {
        return $this->count([
            'seller_id' => $sellerId,
            'is_read' => 0
        ]);
    }

    public function deleteOldNotifications($days = 90) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->execute($sql, [$days]);
    }
}
