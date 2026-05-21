<?php

class BookingRepository extends AbstractRepository
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'bookings', 'booking_id');
    }

    public function findByDate(string $date): array
    {
        $sql = "SELECT b.*, 
                       CONCAT(c.last_name, ' ', c.first_name) AS client_name,
                       CONCAT(e.last_name, ' ', e.first_name) AS employee_name,
                       o.operation_name
                FROM bookings b
                JOIN clients c ON b.client_id = c.client_id
                JOIN employees e ON b.employee_id = e.employee_id
                JOIN operations o ON b.operation_id = o.operation_id
                WHERE b.booking_date = :date
                ORDER BY b.booking_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date' => $date]);
        return $stmt->fetchAll();
    }

    public function findByEmployeeAndDate(int $employeeId, string $date): array
    {
        $sql = "SELECT * FROM bookings WHERE employee_id = :employee_id AND booking_date = :date ORDER BY booking_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId, 'date' => $date]);
        return $stmt->fetchAll();
    }

    public function getFreeSlots(int $employeeId, string $date): array
    {
        $bookedTimes = $this->findByEmployeeAndDate($employeeId, $date);
        $bookedSlots = array_map(function($booking) {
            return substr($booking['booking_time'], 0, 5);
        }, $bookedTimes);
        
        $allSlots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        $freeSlots = array_diff($allSlots, $bookedSlots);
        
        return array_values($freeSlots);
    }

    public function create(array $data): int
    {
        $required = ['client_id', 'employee_id', 'operation_id', 'booking_date', 'booking_time'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new RepositoryException("Missing required field: $field");
            }
        }
        
        $this->checkEmployeeAvailability($data['employee_id'], $data['booking_date'], $data['booking_time']);
        
        if (!empty($data['amount']) && $data['amount'] >= 1000000) {
            $this->checkCreditHistory($data['client_id']);
        }
        
        $sql = "INSERT INTO bookings (client_id, employee_id, operation_id, booking_date, booking_time, amount, status, notes) 
                VALUES (:client_id, :employee_id, :operation_id, :booking_date, :booking_time, :amount, :status, :notes)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_id' => $data['client_id'],
            'employee_id' => $data['employee_id'],
            'operation_id' => $data['operation_id'],
            'booking_date' => $data['booking_date'],
            'booking_time' => $data['booking_time'],
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'] ?? 'запланировано',
            'notes' => $data['notes'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $bookingId, string $status): bool
    {
        $allowedStatuses = ['запланировано', 'проведено', 'отменено', 'не явился'];
        if (!in_array($status, $allowedStatuses)) {
            throw new RepositoryException("Invalid status: $status");
        }
        
        $sql = "UPDATE bookings SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE booking_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['status' => $status, 'id' => $bookingId]);
    }

    public function cancelBooking(int $bookingId, string $reason = null): bool
    {
        $this->beginTransaction();
        try {
            $sql = "UPDATE bookings SET status = 'отменено', notes = CONCAT(IFNULL(notes, ''), ' Отменено: ', :reason) 
                    WHERE booking_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['reason' => $reason ?? 'Без причины', 'id' => $bookingId]);
            
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollBack();
            throw new RepositoryException("Failed to cancel booking: " . $e->getMessage());
        }
    }

    public function getMonthlyStats(int $employeeId, string $yearMonth): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'проведено' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'отменено' THEN 1 ELSE 0 END) AS cancelled,
                    COUNT(DISTINCT client_id) AS unique_clients
                FROM bookings
                WHERE employee_id = :employee_id AND DATE_FORMAT(booking_date, '%Y-%m') = :year_month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId, 'year_month' => $yearMonth]);
        return $stmt->fetch();
    }

    private function checkEmployeeAvailability(int $employeeId, string $date, string $time): void
    {
        $sql = "SELECT COUNT(*) FROM bookings WHERE employee_id = :employee_id AND booking_date = :date AND booking_time = :time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId, 'date' => $date, 'time' => $time]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new RepositoryException("Employee already has a booking at this time");
        }
    }

    private function checkCreditHistory(int $clientId): void
    {
        $sql = "SELECT is_bad_history FROM credit_histories WHERE client_id = :client_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        $result = $stmt->fetch();
        
        if ($result && $result['is_bad_history'] == 1) {
            throw new RepositoryException("Client with bad credit history cannot make a large deposit (>= 1,000,000 rubles)");
        }
    }
}
