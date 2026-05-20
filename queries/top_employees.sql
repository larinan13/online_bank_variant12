USE bank_variant12;

SELECT 
    e.employee_id,
    CONCAT(e.last_name, ' ', e.first_name, ' ', e.patronymic) AS employee_name,
    d.name AS department,
    p.title AS position,
    COUNT(DISTINCT a.client_id) AS clients_count,
    COUNT(a.appointment_id) AS appointments_count
FROM employees e
JOIN departments d ON e.department_id = d.department_id
JOIN positions p ON e.position_id = p.position_id
JOIN appointments a ON e.employee_id = a.employee_id
WHERE a.status IN ('completed', 'scheduled')
    AND YEAR(a.appointment_datetime) = 2026
    AND MONTH(a.appointment_datetime) = 5
GROUP BY e.employee_id, d.name, p.title
ORDER BY clients_count DESC, appointments_count DESC;
