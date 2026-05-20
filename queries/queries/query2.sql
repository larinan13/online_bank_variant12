-- Консультации по сотрудникам с группировкой
SELECT 
    e.department,
    CONCAT(e.last_name, ' ', e.first_name) AS employee,
    COUNT(b.booking_id) AS consultations,
    COUNT(DISTINCT b.client_id) AS unique_clients
FROM employees e
LEFT JOIN bookings b ON e.employee_id = b.employee_id 
    AND b.operation_id IN (SELECT operation_id FROM operations WHERE operation_type = 'консультация')
    AND b.status = 'проведено'
    AND b.booking_date >= '2026-05-01'
GROUP BY e.employee_id
HAVING consultations > 0
ORDER BY consultations DESC;
