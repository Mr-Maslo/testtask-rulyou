<h1>ТЕСТОВОЕ ЗАДАНИЕ (BACKEND DEVELOPER)</h1>

<h4>ТЕРМИНЫ, ИСПОЛЬЗУЕМЫЕ В ЗАДАНИИ:</h4>
<u><i>Найм</i></u> – это же отбор, процесс с помощью которого компания отбирает кандидатов на определенную должность
<br><u><i>Рекрутер</i></u> – это  специалист, который занимается поиском и наймом кандидатов
<br><u><i>Разработчик</i></u> – это специалист, который занимаются разработкой программного обеспечения, предназначенного для работы в различных операционных системах
<br><u><i>Кандидат</i></u> – это претендент, который имеет возможность получить работу и определенным образом соответствует установленным требованиям

<h4>Приходит Директор и говорит:</h4>
«Чтобы компания начала расти как ракета, нам нужно еще больше крутых сотрудников в IT отдел!»
<br>Все сотрудники вдохновились и принялись искать с гигантской скоростью новых коллег, но сразу договорились на том, что каждый Кандидат должен быть закреплен за сотрудником не зависимо от его этапа найма, чтобы уделялось внимание для каждого потенциального Кандидата и не упустить лучших.

<p>Определились на том, что в найме Кандидатов будут принимать участие Рекрутеры и Разработчики и будет это происходить в 2 этапа:
<br><i>1 этап</i> – участвуют только Рекрутеры.  Задача Рекрутеров: находить Кандидатов, собеседовать и выдавать им тестовые задания.
<br><i>2 этап</i> – участвуют Разработчики. Задача Разработчиков: проверять тестовые задания Кандидатов и проводить технические собеседования.
</p>

<p>Также все договорились на том:
<br>1. Что когда завершится найм, компания вознаградит самых продуктивных Рекрутеров и Разработчиков, поэтому начали измерять их эффективность
<br>2. Чтобы найм был очень быстрым, необходимо передавать самому эффективному сотруднику (Если это 1-й этап, то закрепляться Кандидаты сначала должны за самым эффективным Рекрутером, потом за менее эффективным и.т.д.
Если Кандидат выполнил тестовое задание, то он переходит на второй этап и должен быть закреплен сначала за самым эффективным Разработчиком, потом за менее эффективным и.т.д.)
<br>3. Максимальное количество Кандидатов, которых один Разработчик может проверить и отсобеседовать составляет 3000.
</p>

Процесс найма начался и все было очень быстро, но неожиданно выяснилось, что из-за сбоя в CRM, часть Кандидатов не привязывались за Рекрутерами, и что не все Кандидаты были переданы Разработчикам ☹.
Чтобы процесс найма не останавливался и мог быть продолжен, Вас попросили исправить эту проблему и предоставили Вам доступ к данным этой CRM. Отдельно попросили Разработчики, чтобы вы передали Кандидатов, только тех, кто сделал тестовое задание  3 июня 2024 или позже, остальных они уже не успеют проверить ☹

В БД есть следующие данные:
- “candidate_to_employee_assign” - Связь Кандидатов и сотрудников в данный момент.
<br><i>Структура таблицы:</i>
<br>candidate_id - ID Кандидата
<br>city_id – ID города
<br>employee_id - ID Сотрудника
<br>created_at  - Дата/время назначения сотрудника Кандидату


- Таблица “employees” - Сотрудники и их роли, эффективность и кол-во прикрепленных Кандидатов
<i>Структура таблицы:</i>
<br>id - ID Сотрудника
<br>fio - ФИО Сотрудника
<br>role - Роль Сотрудника
<br>efficiency - Эффективность сотрудника
<br>attached_candidates_count  - Кол-во прикрепленных Кандидатов за сотрудником


- Таблица “Candidates” - Данные по Кандидатам и их активности
<i>Структура таблицы:</i>
<br>id – ID Кандидата
<br>city_id – ID города
<br>fio – ФИО Кандидата
<br>phone – телефон Кандидата
<br>date_test - дата/время прохождения тестового задания Кандидатом


<h4>Важные моменты:</h4>
- ID Кандидата не является уникальным в CRM, но ID Кандидата в паре с городом являются уникальным.

<h4>Что необходимо сделать?</h4>

1. Необходимо написать скрипт, который восстановит порядок в данных и распределит в БД Кандидатов среди сотрудников, участвующих в найме, с учетом всех договоренностей между ними.
 
2. После распределения Кандидатов также скриптом необходимо, чтобы сформировались два отчета в CSV:
<br>1-й отчет: «Отчет по Рекрутерам», следующие столбцы в отчете: ФИО Рекрутера, Кол-во Кандидатов до распределения вашим скриптом, Кол-во Кандидатов, которых довел до тестового задания рекрутер, Кол-во Кандидатов после распределения.
<br>2-отчет: «Отчет по Разработчикам», следующие столбцы в отчете: ФИО разработчика, кол-во переданных Кандидатов до распределения вашим скриптом, кол-во Кандидатов после распределения, Сколько Кандидатов и их тестовых заданий еще нужно проверить разработчику.

3. Ответить на вопросы:
- Сколько Кандидатов сделали тестовое задание и при этом были закреплены за рекрутерами до сбоя в CRM?
- Какому «Разработчику» после вашего распределения больше всего досталось новых Кандидатов и их тестовых заданий? И сколько ему досталось?
  
<h4>Требования:</h4>
 
1. Скрипт единый, где вся логика в коде БЕЗ ручных манипуляций с данными, т.е. запустили, сделали распределение (отвязку/привязку среди сотрудников) и получили требуемые 2 отчета.

2. Отчеты в формате Excel (Допустимые форматы: csv, xls, xlsx).

3. Максимальное время на выполнение задания дается в 2,5 часа. Отчет идет с момента того, как только Вам скинул данное задание HR менеджер нашей компании.
