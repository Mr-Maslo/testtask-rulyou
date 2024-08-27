<?php

$connection = new \PDO('mysql:host=<host>;dbname=testdatabase', 'login', 'password');

// Распределяем по HR новых кандидатов
$hrs = [];
$query = $connection->prepare("
    SELECT 
        e.id, 
        e.fio, 
        e.efficiency,
        e.attached_candidates_count, 
        COUNT(DISTINCT CASE WHEN c.date_test > 0 THEN CONCAT(c.id, '#', c.city_id) ELSE NULL END) as candidates_with_test_count
    FROM 
        employees as e LEFT JOIN
        candidate_to_employee_assign as ce ON (ce.employee_id = e.id) LEFT JOIN
        candidates as c ON (ce.candidate_id = c.id AND ce.city_id = c.city_id)
    WHERE
        e.role = 'рекрутер'
    GROUP BY
        e.id
    ORDER BY
        e.efficiency DESC
");
$query->execute();
while ($row = $query->fetch()) {
    $hrs[$row['id']] = $row;
    $hrs[$row['id']]['new_attached_candidates_count'] = $row['attached_candidates_count'] ?? 0;
}

$query = $connection->prepare("
    SELECT 
        COUNT(DISTINCT CONCAT(c.id, '#', c.city_id)) as candidates_cnt
    FROM 
        candidates as c LEFT JOIN
        candidate_to_employee_assign as ce ON (ce.candidate_id = c.id AND ce.city_id = c.city_id) LEFT JOIN
        employees as e ON (ce.employee_id = e.id AND e.role IN ('рекрутер', 'разработчик'))
    WHERE
        (c.date_test IS NULL OR c.date_test = 0) AND 
        e.id IS NULL
");
$query->execute();

$candidatesCount = ($query->fetch())['candidates_cnt'] ?? 0;
$hrsCount = count($hrs);

// Здесь сделано равномерное распределение на рекрутеров, иначе все кандидаты упадут на
// самого эффективного рекрутера, т.к. у нас нет условия по ограничению количества кандидатов
// на одного рекрутера

// Количество рекрутеров, которым на одного человека больше распределяем
$moreCount = $candidatesCount % $hrsCount;
$batchCount = floor($candidatesCount / $hrsCount);

$done = 0;
foreach ($hrs as &$hr) {
    $limit = $done < $moreCount
        ? $batchCount + 1
        : $batchCount
    ;
    $query = $connection->prepare("
        INSERT INTO candidate_to_employee_assign
            (candidate_id, city_id, employee_id, created_at)
        SELECT DISTINCT 
            c.id as candidate_id, 
            c.city_id as city_id,
            :employee_id as employee_id,
            NOW()
        FROM 
            candidates as c LEFT JOIN
            candidate_to_employee_assign as ce ON (ce.candidate_id = c.id AND ce.city_id = c.city_id) LEFT JOIN
            employees as e ON (ce.employee_id = e.id AND e.role IN ('рекрутер', 'разработчик'))
        WHERE
            (c.date_test IS NULL OR c.date_test = 0) AND 
            e.id IS NULL
        LIMIT :limit
    ");
    $query->bindParam(':limit', $limit, \PDO::PARAM_INT);
    $query->bindParam(':employee_id', $hr['id'], \PDO::PARAM_INT);
    $query->execute();
    ++$done;
    $hr['new_attached_candidates_count'] += $batchCount;
} unset($hr);

// Распределяем по разработчикам кандидатов, у которых уже есть тестовые задания
$developers = [];
$developersQuery = $connection->prepare("
    SELECT 
        e.id, 
        e.fio, 
        e.efficiency,
        e.attached_candidates_count
    FROM 
        employees as e
    WHERE
        e.role = 'разработчик'
    ORDER BY
        e.efficiency DESC
");
$developersQuery->execute();
while ($row = $developersQuery->fetch()) {
    $developers[$row['id']] = $row;
    $developers[$row['id']]['new_attached_candidates_count'] = $row['attached_candidates_count'] ?? 0;
}

$updateQuery = $connection->prepare("
    UPDATE 
        candidate_to_employee_assign 
    SET 
        employee_id = :new_employee_id
    WHERE
        candidate_id = :candidate_id AND 
        city_id = :city_id AND
        employee_id = :employee_id
");
$insertQuery = $connection->prepare("
    INSERT INTO candidate_to_employee_assign
        (candidate_id, city_id, employee_id, created_at)
    VALUES 
        (:candidate_id, :city_id, :employee_id, NOW())
");
$query = $connection->prepare("
    SELECT 
        c.id, 
        c.city_id,
        e.id as employee_id
    FROM 
        candidates as c LEFT JOIN
        candidate_to_employee_assign as ce ON (ce.candidate_id = c.id AND ce.city_id = c.city_id) LEFT JOIN
        employees as e ON (ce.employee_id = e.id AND e.role IN ('рекрутер', 'разработчик'))
    WHERE
        FROM_UNIXTIME(c.date_test) >= '2024-06-03 00:00:00' AND
        (e.id IS NULL OR e.role <> 'разработчик')
    ORDER BY
        c.date_test DESC 
");
$query->execute();

// В принципе, здесь можно для оптимизации провернуть пакетное обновление данных
while ($candidate = $query->fetch()) {
    // Здесь можно использовать и foreach или for с двойным перебором,
    // но в данном случае, на мой взгляд, удобнее использовать итератор
    $developer = current($developers);
    while ($developer['new_attached_candidates_count'] >= 3000) {
        $developer = next($developers);
        if ($developer === false) {
            // Больше нет разработчиков со свободными ресурсами для проверки тестовых - выходим
            break 2;
        }
    }

    if ($candidate['employee_id']) {
        $updateQuery->execute([
            'candidate_id' => $candidate['id'],
            'city_id' => $candidate['city_id'],
            'employee_id' => $candidate['employee_id'],
            'new_employee_id' => $developer['id'],
        ]);
    } else {
        $insertQuery->execute([
            'candidate_id' => $candidate['id'],
            'city_id' => $candidate['city_id'],
            'employee_id' => $developer['id'],
        ]);
    }
    ++$developers[key($developers)]['new_attached_candidates_count'];
}

// Сохраним новые счетчики и сформируем отчеты
$employeeUpdateQuery = $connection->prepare("
    UPDATE employees SET attached_candidates_count = :new_attached_candidates_count WHERE id = :id    
");

$hrReport = fopen(__DIR__ . '/Отчет по Рекрутерам.csv', 'w');
fputcsv($hrReport, [
    'ФИО рекрутера',
    'Кол-во кандидатов до распределения вашим скриптом',
    'Кол-во кандидатов, которых довел до тестового задания рекрутер',
    'Кол-во кандидатов после распределения',
]);
foreach ($hrs as $hr) {
    $employeeUpdateQuery->execute([
        'id' => $hr['id'],
        'new_attached_candidates_count' => $hr['new_attached_candidates_count'],
    ]);

    fputcsv($hrReport, [
        $hr['fio'],
        $hr['attached_candidates_count'],
        $hr['candidates_with_test_count'],
        $hr['new_attached_candidates_count'],
    ]);
}
fclose($hrReport);

$developersReport = fopen(__DIR__ . '/Отчет по Разработчикам.csv', 'w');
fputcsv($developersReport, [
    'ФИО разработчика',
    'Кол-во переданных Кандидатов до распределения вашим скриптом',
    'Кол-во кандидатов после распределения',
    'Сколько кандидатов и их тестовых заданий еще нужно проверить разработчику',
]);
foreach ($developers as $developer) {
    $employeeUpdateQuery->execute([
        'id' => $developer['id'],
        'new_attached_candidates_count' => $developer['new_attached_candidates_count'],
    ]);

    fputcsv($developersReport, [
        $developer['fio'],
        $developer['attached_candidates_count'],
        $developer['new_attached_candidates_count'],
        $developer['new_attached_candidates_count'] - $developer['attached_candidates_count'],
    ]);
}
fclose($developersReport);
