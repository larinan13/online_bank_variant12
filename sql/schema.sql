DROP DATABASE IF EXISTS bank_variant12;
CREATE DATABASE bank_variant12 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;
USE bank_variant12;

-- ==================== СПРАВОЧНИКИ ====================

-- Отделы банка
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Должности сотрудников
CREATE TABLE positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== ОСНОВНЫЕ ТАБЛИЦЫ ====================

-- Сотрудники
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
    position_id INT NOT NULL,
    department_id INT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    FOREIGN KEY (position_id) REFERENCES positions(position_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Клиенты
CREATE TABLE clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    birth_date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Кредитные истории
CREATE TABLE credit_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    rating ENUM('good', 'bad') NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Типы операций
CREATE TABLE operations (
    operation_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('consultation', 'deposit', 'credit') NOT NULL,
    is_large_deposit BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Записи на обслуживание
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    employee_id INT NOT NULL,
    operation_id INT NOT NULL,
    appointment_datetime DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
    FOREIGN KEY (operation_id) REFERENCES operations(operation_id),
    UNIQUE KEY unique_slot (employee_id, appointment_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==================== ТРИГГЕР ====================

-- Запрет записи на крупный вклад при плохой кредитной истории
DELIMITER $$
CREATE TRIGGER check_credit_before_deposit
BEFORE INSERT ON appointments
FOR EACH ROW
BEGIN
    DECLARE v_rating VARCHAR(10);
    DECLARE v_is_large BOOLEAN;
    
    SELECT is_large_deposit INTO v_is_large 
    FROM operations 
    WHERE operation_id = NEW.operation_id;
    
    IF v_is_large = TRUE THEN
        SELECT rating INTO v_rating 
        FROM credit_history 
        WHERE client_id = NEW.client_id;
        
        IF v_rating = 'bad' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Клиент с плохой кредитной историей не может открыть крупный вклад';
        END IF;
    END IF;
END$$
DELIMITER ;

-- ==================== ИНДЕКСЫ ====================

CREATE INDEX idx_appointments_datetime ON appointments(appointment_datetime);
CREATE INDEX idx_appointments_client ON appointments(client_id);
CREATE INDEX idx_appointments_employee ON appointments(employee_id);

-- ==================== ТЕСТОВЫЕ ДАННЫЕ ====================

-- Отделы
INSERT INTO departments (name, description) VALUES
('Отдел вкладов', 'Работа с вкладами и депозитами'),
('Отдел кредитования', 'Кредиты и ипотека'),
('Консультации', 'Общие консультации клиентов');

-- Должности
INSERT INTO positions (title) VALUES
('Менеджер'),
('Старший менеджер'),
('Руководитель отдела');

-- Сотрудники
INSERT INTO employees (last_name, first_name, patronymic, position_id, department_id, phone, email) VALUES
('Соколова', 'Ольга', 'Михайловна', 2, 1, '+74951112233', 'sokolova@bank.ru'),
('Кузнецов', 'Иван', 'Сергеевич', 1, 3, '+74951112234', 'kuznetsov@bank.ru'),
('Васильева', 'Татьяна', 'Андреевна', 3, 1, '+74951112235', 'vasilieva@bank.ru'),
('Попов', 'Александр', 'Владимирович', 1, 3, '+74951112236', 'popov@bank.ru');

-- Клиенты
INSERT INTO clients (last_name, first_name, patronymic, phone, email, birth_date) VALUES
('Иванов', 'Александр', 'Петрович', '+79161234567', 'ivanov@example.com', '1985-03-15'),
('Петрова', 'Мария', 'Сергеевна', '+79162345678', 'petrova@example.com', '1990-07-22'),
('Сидоров', 'Дмитрий', 'Алексеевич', '+79163456789', 'sidorov@example.com', '1978-11-05'),
('Козлова', 'Елена', 'Владимировна', '+79164567890', 'kozlova@example.com', '1995-02-18'),
('Морозов', 'Андрей', 'Игоревич', '+79165678901', 'morozov@example.com', '1982-09-30');

-- Кредитные истории
INSERT INTO credit_history (client_id, rating) VALUES
(1, 'good'),
(2, 'good'),
(3, 'bad'),
(4, 'good'),
(5, 'good');

-- Операции
INSERT INTO operations (name, type, is_large_deposit) VALUES
('Консультация', 'consultation', FALSE),
('Открытие стандартного вклада', 'deposit', FALSE),
('Открытие крупного вклада', 'deposit', TRUE),
('Оформление кредита', 'credit', FALSE);

-- Записи
INSERT INTO appointments (client_id, employee_id, operation_id, appointment_datetime, status) VALUES
(1, 1, 2, '2026-05-20 10:00:00', 'completed'),
(1, 3, 1, '2026-05-22 14:00:00', 'completed'),
(2, 2, 1, '2026-05-21 11:30:00', 'completed'),
(2, 1, 2, '2026-05-25 09:00:00', 'scheduled'),
(4, 2, 3, '2026-05-23 15:00:00', 'completed'),
(5, 1, 2, '2026-05-24 10:00:00', 'completed'),
(5, 3, 1, '2026-05-26 11:00:00', 'scheduled'),
(1, 2, 1, '2026-05-27 16:00:00', 'completed');
