<?php

class OperationRepository extends AbstractRepository
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'operations', 'operation_id');
    }

    public function findByType(string $type): array
    {
        $allowedTypes = ['вклад', 'консультация', 'кредит', 'другое'];
        if (!in_array($type, $allowedTypes)) {
            throw new RepositoryException("Invalid operation type: $type");
        }
        
        $sql = "SELECT * FROM operations WHERE operation_type = :type AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $required = ['operation_name', 'operation_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RepositoryException("Missing required field: $field");
            }
        }
        
        $sql = "INSERT INTO operations (operation_name, operation_type, min_amount, max_amount, description, is_active) 
                VALUES (:name, :type, :min_amount, :max_amount, :description, 1)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['operation_name'],
            'type' => $data['operation_type'],
            'min_amount' => $data['min_amount'] ?? null,
            'max_amount' => $data['max_amount'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
}
