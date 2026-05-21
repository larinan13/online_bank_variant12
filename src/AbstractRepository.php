<?php

abstract class AbstractRepository
{
    protected PDO $db;
    protected string $tableName;
    protected string $primaryKey = 'id';

    public function __construct(PDO $db, string $tableName, string $primaryKey = 'id')
    {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
    }

    protected function validateColumnName(string $column, array $allowedColumns): void
    {
        if (!in_array($column, $allowedColumns)) {
            throw new RepositoryException("Invalid column name: $column");
        }
    }

    public function findAll(array $where = [], array $params = [], string $orderBy = '', string $direction = 'ASC', ?int $limit = null): array
    {
        $allowedDirections = ['ASC', 'DESC'];
        $direction = strtoupper($direction);
        if (!in_array($direction, $allowedDirections)) {
            $direction = 'ASC';
        }

        $sql = "SELECT * FROM {$this->tableName}";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy $direction";
        }
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
