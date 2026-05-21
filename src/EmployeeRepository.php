<?php

class EmployeeRepository extends AbstractRepository
{
    private array $allowedColumns = ['employee_id', 'last_name', 'first_name', 'department', 'position', 'email', 'phone', 'salary'];
    
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'employees', 'employee_id');
    }

    public function findByDepartment(string $department): array
    {
        $sql = "SELECT * FROM employees WHERE department = :department AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['department' => $department]);
        return $stmt->fetchAll();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM employees WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $required = ['last_name', 'first_name', 'position', 'department', 'phone', 'email', 'hire_date', 'salary'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RepositoryException("Missing required field: $field");
            }
        }

        $sql = "INSERT INTO employees (last_name, first_name, patronymic, position, department, phone, email, hire_date, salary, is_active) 
                VALUES (:last_name, :first_name, :patronymic, :position, :department, :phone, :email, :hire_date, :salary, 1)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'patronymic' => $data['patronymic'] ?? null,
            'position' => $data['position'],
            'department' => $data['department'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'hire_date' => $data['hire_date'],
            'salary' => $data['salary']
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = ['position', 'department', 'phone', 'salary', 'is_active'];
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
        
        $sql = "UPDATE employees SET " . implode(', ', $updates) . " WHERE employee_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getTopPerformers(string $startDate, string $endDate, int $limit = 5): array
    {
        $sql = "SELECT 
                    e.employee_id,
                    CONCAT(e.last_name, ' ', e.first_name) AS full_name,
                    e.department,
                    e.position,
                    COUNT(DISTINCT b.client_id) AS unique_clients,
                    COUNT(b.booking_id) AS total_operations
                FROM employees e
                JOIN bookings b ON e.employee_id = b.employee_id
                WHERE b.status = 'проведено' AND b.booking_date BETWEEN :start_date AND :end_date
                GROUP BY e.employee_id
                ORDER BY unique_clients DESC, total_operations DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
