<?php

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/RepositoryException.php';
require_once __DIR__ . '/src/AbstractRepository.php';
require_once __DIR__ . '/src/EmployeeRepository.php';
require_once __DIR__ . '/src/ClientRepository.php';
require_once __DIR__ . '/src/OperationRepository.php';
require_once __DIR__ . '/src/CreditHistoryRepository.php';
require_once __DIR__ . '/src/BookingRepository.php';

echo "<!DOCTYPE html><html><head><title>Banking System Demo</title><style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
pre { background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd; overflow-x: auto; }
h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
h3 { color: #16a085; margin-top: 25px; }
.success { color: #27ae60; font-weight: bold; }
.error { color: #e74c3c; font-weight: bold; }
hr { margin: 30px 0; }
</style></head><body>";

echo "<h1>Банковская система онлайн-записи — Демонстрация работы репозиториев</h1>";

try {
    Database::loadConfig(__DIR__ . '/config.php');
    $db = Database::getConnection();
    
    $employeeRepo = new EmployeeRepository($db);
    $clientRepo = new ClientRepository($db);
    $operationRepo = new OperationRepository($db);
    $creditRepo = new CreditHistoryRepository($db);
    $bookingRepo = new BookingRepository($db);
    
    echo "<h2>1. Выбор всех сотрудников</h2>";
    $employees = $employeeRepo->findAll([], [], 'last_name', 'ASC');
    echo "<pre>";
    foreach ($employees as $e) {
        echo "{$e['employee_id']}. {$e['last_name']} {$e['first_name']} — {$e['position']} ({$e['department']})\n";
    }
    echo "</pre>";
    
    echo "<h2>2. Выбор сотрудника по ID (id=1)</h2>";
    $employee = $employeeRepo->findById(1);
    echo "<pre>";
    print_r($employee);
    echo "</pre>";
    
    echo "<h2>3. Добавление нового клиента</h2>";
    try {
        $newClientId = $clientRepo->create([
            'last_name' => 'Тестов',
            'first_name' => 'Петр',
            'patronymic' => 'Иванович',
            'passport_number' => '9999 999999',
            'phone' => '+7(999)888-7777',
            'email' => 'test@demo.ru',
            'birth_date' => '1990-05-15',
            'registration_address' => 'г. Москва, ул. Тестовая, д.1'
        ]);
        echo "<p class='success'>✓ Клиент успешно добавлен. ID: $newClientId</p>";
    } catch (RepositoryException $e) {
        echo "<p class='error'>✗ Ошибка: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>4. Добавление кредитной истории для клиента</h2>";
    try {
        $creditId = $creditRepo->create([
            'client_id' => 1,
            'credit_score' => 750,
            'default_count' => 0,
            'total_debt' => 0,
            'last_check_date' => '2026-05-20'
        ]);
        echo "<p class='success'>✓ Кредитная история добавлена. ID: $creditId</p>";
    } catch (RepositoryException $e) {
        echo "<p class='error'>✗ Ошибка: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>5. Создание новой записи на обслуживание (успешный сценарий)</h2>";
    try {
        $bookingId = $bookingRepo->create([
            'client_id' => 1,
            'employee_id' => 1,
            'operation_id' => 1,
            'booking_date' => '2026-06-15',
            'booking_time' => '10:00:00',
            'amount' => 500000,
            'status' => 'запланировано',
            'notes' => 'Пробная запись'
        ]);
        echo "<p class='success'>✓ Запись создана. ID: $bookingId</p>";
    } catch (RepositoryException $e) {
        echo "<p class='error'>✗ Ошибка: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>6. Попытка создания записи с конфликтом времени</h2>";
    try {
        $bookingRepo->create([
            'client_id' => 2,
            'employee_id' => 1,
            'operation_id' => 2,
            'booking_date' => '2026-06-15',
            'booking_time' => '10:00:00',
            'amount' => null
        ]);
        echo "<p class='error'>✗ Ошибка должна была возникнуть, но запись создалась</p>";
    } catch (RepositoryException $e) {
        echo "<p class='error'>✗ Ошибка (ожидаемо): " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>7. Попытка создания крупного вклада для клиента с плохой историей</h2>";
    try {
        $bookingRepo->create([
            'client_id' => 2,
            'employee_id' => 3,
            'operation_id' => 1,
            'booking_date' => '2026-06-20',
            'booking_time' => '14:00:00',
            'amount' => 1500000
        ]);
        echo "<p class='error'>✗ Ошибка должна была возникнуть</p>";
    } catch (RepositoryException $e) {
        echo "<p class='error'>✗ Ошибка (ожидаемо): " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>8. Изменение статуса записи</h2>";
    $bookingRepo->updateStatus(1, 'проведено');
    echo "<p class='success'>✓ Статус записи ID=1 изменён на 'проведено'</p>";
    
    echo "<h2>9. Отмена записи с использованием транзакции</h2>";
    $bookingRepo->cancelBooking(1, 'Клиент передумал');
    echo "<p class='success'>✓ Запись ID=1 отменена</p>";
    
    echo "<h2>10. Получение свободных слотов для сотрудника</h2>";
    $freeSlots = $bookingRepo->getFreeSlots(1, '2026-06-15');
    echo "<pre>Свободные слоты для сотрудника ID=1 на 2026-06-15:\n";
    print_r($freeSlots);
    echo "</pre>";
    
    echo "<h2>11. Топ сотрудников за май 2026</h2>";
    $topPerformers = $employeeRepo->getTopPerformers('2026-05-01', '2026-05-31', 5);
    echo "<pre>";
    foreach ($topPerformers as $p) {
        echo "{$p['full_name']} — клиентов: {$p['unique_clients']}, операций: {$p['total_operations']}\n";
    }
    echo "</pre>";
    
    echo "<h2>12. Записи на конкретную дату</h2>";
    $bookings = $bookingRepo->findByDate('2026-05-10');
    echo "<pre>";
    foreach ($bookings as $b) {
        echo "{$b['booking_time']} — {$b['client_name']} → {$b['employee_name']} ({$b['operation_name']})\n";
    }
    echo "</pre>";
    
    echo "<h2>13. Удаление тестового клиента</h2>";
    if (isset($newClientId)) {
        $clientRepo->delete($newClientId);
        echo "<p class='success'>✓ Клиент ID=$newClientId удалён</p>";
    }
    
} catch (PDOException $e) {
    echo "<pre class='error'>Ошибка базы данных: " . $e->getMessage() . "</pre>";
} catch (RepositoryException $e) {
    echo "<pre class='error'>Ошибка репозитория: " . $e->getMessage() . "</pre>";
} catch (Exception $e) {
    echo "<pre class='error'>Общая ошибка: " . $e->getMessage() . "</pre>";
}

echo "<hr><p><strong>Демонстрация завершена</strong></p>";
echo "</body></html>";
