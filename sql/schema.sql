DROP DATABASE IF EXISTS online_banking_variant12;
CREATE DATABASE online_banking_variant12 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_banking_variant12;

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2) CHECK (salary > 0)
);

CREATE TABLE clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
    passport_number VARCHAR(20) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    birth_date DATE NOT NULL,
    registration_address TEXT,
    CHECK (birth_date <= CURDATE() - INTERVAL 18 YEAR)
);

CREATE TABLE operations (
    operation_id INT AUTO_INCREMENT PRIMARY KEY,
    operation_name VARCHAR(100) NOT NULL UNIQUE,
    operation_type ENUM('вклад','консультация','кредит','другое') NOT NULL,
    min_amount DECIMAL(15,2) DEFAULT 0,
    max_amount DECIMAL(15,2)
);

CREATE TABLE credit_histories (
    credit_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    credit_score INT NOT NULL CHECK (credit_score BETWEEN 300 AND 850),
    is_bad_history BOOLEAN DEFAULT FALSE,
    default_count INT DEFAULT 0 CHECK (default_count >= 0),
    total_debt DECIMAL(15,2) DEFAULT 0 CHECK (total_debt >= 0),
    last_check_date DATE NOT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    employee_id INT NOT NULL,
    operation_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    amount DECIMAL(15,2),
    status ENUM('запланировано','проведено','отменено') DEFAULT 'запланировано',
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE RESTRICT,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE RESTRICT,
    FOREIGN KEY (operation_id) REFERENCES operations(operation_id) ON DELETE RESTRICT,
    UNIQUE KEY unique_slot (employee_id, booking_date, booking_time)
);

DELIMITER //
CREATE TRIGGER check_credit_before_booking
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
    DECLARE bad_history BOOLEAN;
    DECLARE op_type VARCHAR(50);
    SELECT operation_type INTO op_type FROM operations WHERE operation_id = NEW.operation_id;
    IF op_type = 'вклад' AND NEW.amount >= 1000000 THEN
        SELECT is_bad_history INTO bad_history FROM credit_histories WHERE client_id = NEW.client_id;
        IF bad_history THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Плохая кредитная история – крупный вклад запрещён';
        END IF;
    END IF;
END //
DELIMITER ;

INSERT INTO employees (last_name, first_name, patronymic, position, department, phone, email, hire_date, salary) VALUES
('Иванов','Сергей','Александрович','Старший менеджер','Отдел физ.лиц','+74951112233','ivanov@bank.ru','2018-03-15',85000),
('Петрова','Ольга','Владимировна','Менеджер','Отдел физ.лиц','+74951112244','petrova@bank.ru','2019-06-20',65000),
('Сидоров','Алексей','Игоревич','Специалист','Депозитный отдел','+74951112255','sidorov@bank.ru','2020-01-10',75000);

INSERT INTO clients (last_name, first_name, patronymic, passport_number, phone, email, birth_date, registration_address) VALUES
('Абрамов','Виктор','Иванович','4501123456','+79161234567','abramov@mail.ru','1985-03-20','Москва, Тверская,15'),
('Васильев','Константин','Петрович','4503345678','+79163456789','vasiliev@mail.ru','1978-11-05','Москва, Мира,25');

INSERT INTO operations (operation_name, operation_type, min_amount, max_amount) VALUES
('Вклад "Доходный"','вклад',10000,5000000),
('Консультация по кредитам','консультация',NULL,NULL);

INSERT INTO credit_histories (client_id, credit_score, is_bad_history, default_count, total_debt, last_check_date) VALUES
(1,750,FALSE,0,0,'2026-05-01'),
(2,450,TRUE,5,250000,'2026-05-01');

INSERT INTO bookings (client_id, employee_id, operation_id, booking_date, booking_time, amount, status) VALUES
(1,1,1,'2026-05-10','10:00:00',500000,'запланировано'),
(2,2,1,'2026-05-11','11:00:00',1500000,'запланировано');
