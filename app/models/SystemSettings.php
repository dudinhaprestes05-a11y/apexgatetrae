<?php

require_once __DIR__ . '/BaseModel.php';

class SystemSettings extends BaseModel {
    protected $table = 'system_settings';

    public function getSettings() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = 1");
        return $stmt->fetch();
    }

    public function updateSettings($data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = 1; // ID sempre 1

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
