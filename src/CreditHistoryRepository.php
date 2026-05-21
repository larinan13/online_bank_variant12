<?php

class CreditHistoryRepository extends AbstractRepository
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'credit_histories', 'credit_id');
    }

    public function findByClientId(int $clientId): ?array
    {
        $sql = "SELECT * FROM credit_histories WHERE client_id = :client_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $required = ['client_id', 'credit_score', 'last_check_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RepositoryException("Missing required field: $field");
            }
        }
        
        $isBad = ($data['credit_score'] < 600) || ($data['default_count'] ?? 0) >= 3;
        
        $sql = "INSERT INTO credit_histories (client_id, credit_score, is_bad_history, default_count, total_debt, last_check_date, notes) 
                VALUES (:client_id, :credit_score, :is_bad, :default_count, :total_debt, :last_check_date, :notes)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_id' => $data['client_id'],
            'credit_score' => $data['credit_score'],
            'is_bad' => $isBad,
            'default_count' => $data['default_count'] ?? 0,
            'total_debt' => $data['total_debt'] ?? 0,
            'last_check_date' => $data['last_check_date'],
            'notes' => $data['notes'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function updateScore(int $clientId, int $newScore): bool
    {
        if ($newScore < 300 || $newScore > 850) {
            throw new RepositoryException("Credit score must be between 300 and 850");
        }
        
        $isBad = ($newScore < 600);
        
        $sql = "UPDATE credit_histories SET credit_score = :score, is_bad_history = :is_bad, last_check_date = CURDATE() 
                WHERE client_id = :client_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['score' => $newScore, 'is_bad' => $isBad, 'client_id' => $clientId]);
    }
}
