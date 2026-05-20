USE bank_variant12;

SELECT 
    a.appointment_id,
    CONCAT(c.last_name, ' ', c.first_name) AS client_name,
    c.phone AS client_phone,
    CONCAT(e.last_name, ' ', e.first_name) AS employee_name,
    d.name AS department,
    o.name AS operation,
    o.type AS operation_type,
    a.appointment_datetime,
    a.status
FROM appointments a
JOIN clients c ON a.client_id = c.client_id
JOIN employees e ON a.employee_id = e.employee_id
JOIN departments d ON e.department_id = d.department_id
JOIN operations o ON a.operation_id = o.operation_id
ORDER BY a.appointment_datetime DESC;
