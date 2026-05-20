- Сотрудники, обслужившие наибольшее число клиентов за месяц (май 2026)
SELECT 
    e.employee_id,
    CONCAT(e.last_name, ' ', e.first_name) AS employee_name,
    e.department,
    COUNT(DISTINCT b.client_id) AS unique_clients,
    COUNT(b.booking_id) AS total_ops,
    RANK() OVER (ORDER BY COUNT(DISTINCT b.client_id) DESC) AS ranking
FROM employees e
JOIN bookings b ON e.employee_id = b.employee_id
WHERE b.status = 'проведено' 
  AND b.booking_date BETWEEN '2026-05-01' AND '2026-05-31'
GROUP BY e.employee_id
ORDER BY ranking;
