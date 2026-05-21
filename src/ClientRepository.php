<?php

class ClientRepository extends AbstractRepository
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'clients', 'client_id');
    }

    public function findByPhone(string $phone): ?array
    {
        $sql = "SELECT * FROM clients WHERE phone = :phone";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['phone' => $phone]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM clients WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByPassport(string $passport): ?array
    {
        $sql = "SELECT * FROM clients WHERE passport_number = :passport";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['passport' => $passport]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $required = ['last_name', 'first_name', 'passport_number', 'phone', 'email', 'birth_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RepositoryException("Missing required field: $field");
            }
        }

        $sql = "INSERT INTO clients (last_name, first_name, patronymic, passport_number, phone, email, birth_date, registration_address) 
                VALUES (:last_name, :first_name, :patronymic, :passport_number, :phone, :email, :birth_date, :address)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'passport_number' => $data['passport_number'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'birth_date' => $data['birth_date'],
            'address' => $data['registration_address'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = ['phone', 'email', 'registration_address'];
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $sql = "UPDATE clients SET " . implode(', ', $updates) . " WHERE client_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
