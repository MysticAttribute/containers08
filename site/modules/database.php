<?php
class Database {
    private $pdo;

    public function __construct($path) {
        $this->pdo = new PDO("sqlite:" . $path);
    }

    public function Execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function Fetch($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Create($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $this->Execute($sql, $data);
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM $table WHERE id = :id";
        $rows = $this->Fetch($sql, ["id" => $id]);
        return $rows[0] ?? [];
    }

    public function Update($table, $id, $data) {
        $fields = implode(", ", array_map(fn($k) => "$k = :$k", array_keys($data)));
        $sql = "UPDATE $table SET $fields WHERE id = :id";
        $data["id"] = $id;
        return $this->Execute($sql, $data);
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM $table WHERE id = :id";
        return $this->Execute($sql, ["id" => $id]);
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        $rows = $this->Fetch($sql);
        return $rows[0]['count'] ?? 0;
    }
}
