USE bank_variant12;

-- 1. Клиенты с плохой кредитной историей
SELECT 
    CONCAT(c.last_name, ' ', c.first_name) AS client_name,
    c.phone,
    ch.rating
FROM clients c
JOIN credit_history ch ON c.client_id = ch.client_id
WHERE ch.rating = 'bad';

-- 2. Клиенты, которым будет отказано в крупном вкладе
SELECT 
    CONCAT(c.last_name, ' ', c.first_name) AS client_name,
    ch.rating,
    o.name AS operation_name
FROM clients c
JOIN credit_history ch ON c.client_id = ch.client_id
CROSS JOIN operations o
WHERE ch.rating = 'bad'
    AND o.is_large_deposit = TRUE;
