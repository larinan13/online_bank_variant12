-- Анализ загрузки по отделам (оконные функции)
WITH dept_stats AS (
    SELECT 
        e.department,
        e.employee_id,
        CONCAT(e.last_name, ' ', e.first_name) AS employee,
        COUNT(b.booking_id) AS completed_ops
    FROM employees e
    LEFT JOIN bookings b ON e.employee_id = b.employee_id AND b.status = 'проведено'
    WHERE b.booking_date BETWEEN '2026-05-01' AND '2026-05-31' OR b.booking_id IS NULL
    GROUP BY e.employee_id
)
SELECT 
    department,
    employee,
    completed_ops,
    ROUND(100 * completed_ops / SUM(completed_ops) OVER (PARTITION BY department), 1) AS dept_share_percent
FROM dept_stats
WHERE completed_ops > 0
ORDER BY department, completed_ops DESC;
