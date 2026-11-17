-- Dane demonstracyjne. Wszystkie osoby i adresy są fikcyjne.
-- Hasło każdego konta demo: Cargo2026! (w bazie tylko hash bcrypt).
--
-- Daty są liczone względem chwili importu, więc demo zawsze ma trwające i przyszłe rezerwacje
-- oraz badanie UDT wygasające za 10 dni. Całość jest w jednej transakcji, dzięki czemu now()
-- ma tę samą wartość w każdym zapytaniu.

BEGIN;

INSERT INTO roles (id, code, name_key) OVERRIDING SYSTEM VALUE VALUES
    (1, 'OPERATOR', 'role.operator'),
    (2, 'ADMIN',    'role.admin');

INSERT INTO users (id, role_id, email, password_hash, first_name, last_name, locale, is_active, created_at) OVERRIDING SYSTEM VALUE VALUES
    (1, 2, 'admin@cargo.local',      '$2y$12$o0phE13sF0HX8kqJYVmAje15dKqv8EB0v6vpzDho/bivtK52nXKju', 'Katarzyna', 'Zielińska',   'pl', true,  now() - interval '400 days'),
    (2, 1, 'operator@cargo.local',   '$2y$12$rijXCFROp6vgVERkVC4Kyuk3zNcBLH.UpWBiX8CbNX3zGvmb4DauG', 'Marek',     'Kowalczyk',   'pl', true,  now() - interval '300 days'),
    (3, 1, 'operator2@cargo.local',  '$2y$12$rrvWaOxpIfTauMLsbWVWjOKtylYAqQwDyeyl4gJwFOUJeCfcMRLAO', 'Tom',       'Walker',      'en', true,  now() - interval '120 days'),
    (4, 1, 'nieaktywny@cargo.local', '$2y$12$gUimbgZxjyyOYzhN/F8ivuu9uirm/IgdZ/zV8e7vwlYpReYvwVRxm', 'Piotr',     'Lewandowski', 'pl', false, now() - interval '500 days');

INSERT INTO warehouses (id, name, address) OVERRIDING SYSTEM VALUE VALUES
    (1, 'Magazyn A – wysokie składowanie', 'ul. Przykładowa 1, 00-001 Miasto'),
    (2, 'Magazyn B – kompletacja',         'ul. Przykładowa 3, 00-001 Miasto');

INSERT INTO forklift_models (id, manufacturer, model_name, fuel_type, max_load_kg, max_lift_height_mm) OVERRIDING SYSTEM VALUE VALUES
    (1, 'Linde',        'E20',     'ELECTRIC', 2000, 5000),
    (2, 'Toyota',       'Tonero 25', 'LPG',    2500, 4700),
    (3, 'Jungheinrich', 'DFG 430', 'DIESEL',   3000, 4000),
    (4, 'Jungheinrich', 'ETV 216', 'ELECTRIC', 1600, 9500);

-- WD-04 trafia do bazy jako AVAILABLE: status OUT_OF_SERVICE nada mu trigger po wstawieniu usterki krytycznej.
INSERT INTO forklifts (id, model_id, warehouse_id, internal_code, serial_number, production_year, status) OVERRIDING SYSTEM VALUE VALUES
    (1, 1, 1, 'WD-01', 'LND-E20-58213',   2021, 'AVAILABLE'),
    (2, 1, 2, 'WD-02', 'LND-E20-60477',   2022, 'AVAILABLE'),
    (3, 2, 1, 'WD-03', 'TYT-T25-11902',   2020, 'AVAILABLE'),
    (4, 2, 2, 'WD-04', 'TYT-T25-12655',   2019, 'AVAILABLE'),
    (5, 3, 1, 'WD-05', 'JH-DFG430-7741',  2018, 'AVAILABLE'),
    (6, 3, 2, 'WD-06', 'JH-DFG430-7790',  2018, 'AVAILABLE'),
    (7, 4, 1, 'WD-07', 'JH-ETV216-3310',  2023, 'AVAILABLE'),
    (8, 4, 2, 'WD-08', 'JH-ETV216-1004',  2012, 'RETIRED');

-- Badania okresowe co rok. WD-05: ważne jeszcze 10 dni (demo CRON), WD-06: wygasłe (demo reguły R2).
INSERT INTO technical_inspections (id, forklift_id, inspection_date, valid_until, protocol_number, notes) OVERRIDING SYSTEM VALUE VALUES
    (1, 1, CURRENT_DATE - 500, CURRENT_DATE - 135, 'UDT/P/0001', NULL),
    (2, 1, CURRENT_DATE - 140, CURRENT_DATE + 225, 'UDT/P/0009', 'Bez zastrzeżeń.'),
    (3, 2, CURRENT_DATE - 200, CURRENT_DATE + 165, 'UDT/P/0004', NULL),
    (4, 3, CURRENT_DATE - 90,  CURRENT_DATE + 275, 'UDT/P/0011', NULL),
    (5, 4, CURRENT_DATE - 250, CURRENT_DATE + 115, 'UDT/P/0003', 'Zalecona wymiana opon przy najbliższym serwisie.'),
    (6, 5, CURRENT_DATE - 355, CURRENT_DATE + 10,  'UDT/P/0002', NULL),
    (7, 6, CURRENT_DATE - 380, CURRENT_DATE - 15,  'UDT/P/0005', NULL),
    (8, 7, CURRENT_DATE - 30,  CURRENT_DATE + 335, 'UDT/P/0012', NULL),
    (9, 8, CURRENT_DATE - 700, CURRENT_DATE - 335, 'UDT/P/0006', 'Ostatnie badanie przed wycofaniem z eksploatacji.');

INSERT INTO checklist_items (id, code, label_key, is_critical, sort_order) OVERRIDING SYSTEM VALUE VALUES
    (1,  'BRAKES',       'checklist.brakes',       true,  10),
    (2,  'STEERING',     'checklist.steering',     true,  20),
    (3,  'FORKS',        'checklist.forks',        true,  30),
    (4,  'MAST_CHAINS',  'checklist.mast_chains',  true,  40),
    (5,  'HORN',         'checklist.horn',         false, 50),
    (6,  'LIGHTS',       'checklist.lights',       false, 60),
    (7,  'TIRES',        'checklist.tires',        false, 70),
    (8,  'LEAKS',        'checklist.leaks',        false, 80),
    (9,  'ENERGY_LEVEL', 'checklist.energy_level', false, 90),
    (10, 'SEAT_BELT',    'checklist.seat_belt',    false, 100);

-- Rezerwacje: zakończone (historia i raport), anulowane, trwające teraz i przyszłe.
-- Rezerwacja 6 (operator@, WD-01) trwa i nie ma jeszcze kontroli: na niej pokazujemy checklistę.
INSERT INTO reservations (id, forklift_id, user_id, starts_at, ends_at, status, created_at, cancelled_at) OVERRIDING SYSTEM VALUE VALUES
    (1, 1, 2, date_trunc('hour', now()) - interval '7 days',  date_trunc('hour', now()) - interval '7 days' + interval '8 hours', 'COMPLETED', now() - interval '9 days', NULL),
    (2, 3, 2, date_trunc('hour', now()) - interval '3 days',  date_trunc('hour', now()) - interval '3 days' + interval '6 hours', 'COMPLETED', now() - interval '4 days', NULL),
    (3, 2, 3, date_trunc('hour', now()) - interval '2 days',  date_trunc('hour', now()) - interval '2 days' + interval '8 hours', 'COMPLETED', now() - interval '3 days', NULL),
    (4, 4, 3, date_trunc('hour', now()) - interval '1 day',   date_trunc('hour', now()) - interval '1 day' + interval '4 hours',  'CANCELLED', now() - interval '2 days', date_trunc('hour', now()) - interval '1 day' - interval '5 minutes'),
    (5, 7, 2, date_trunc('hour', now()) + interval '1 day',   date_trunc('hour', now()) + interval '1 day' + interval '8 hours',  'CANCELLED', now() - interval '1 day',  now() - interval '3 hours'),
    (6, 1, 2, date_trunc('hour', now()) - interval '1 hour',  date_trunc('hour', now()) + interval '7 hours',                     'ACTIVE',    now() - interval '1 day',  NULL),
    (7, 7, 3, date_trunc('hour', now()) - interval '2 hours', date_trunc('hour', now()) + interval '6 hours',                     'ACTIVE',    now() - interval '1 day',  NULL),
    (8, 2, 2, date_trunc('hour', now()) + interval '1 day',   date_trunc('hour', now()) + interval '1 day' + interval '8 hours',  'ACTIVE',    now() - interval '2 hours', NULL),
    (9, 3, 3, date_trunc('hour', now()) + interval '2 days',  date_trunc('hour', now()) + interval '2 days' + interval '8 hours', 'ACTIVE',    now() - interval '1 hour',  NULL);

INSERT INTO inspections (id, reservation_id, performed_at, notes) OVERRIDING SYSTEM VALUE VALUES
    (1, 1, date_trunc('hour', now()) - interval '7 days'  - interval '15 minutes', NULL),
    (2, 2, date_trunc('hour', now()) - interval '3 days'  - interval '10 minutes', NULL),
    (3, 3, date_trunc('hour', now()) - interval '2 days'  - interval '20 minutes', NULL),
    (4, 4, date_trunc('hour', now()) - interval '1 day'   - interval '10 minutes', 'Wózek odstawiony na stanowisko serwisowe.'),
    (5, 7, date_trunc('hour', now()) - interval '2 hours' - interval '15 minutes', NULL);

-- Każda kontrola ocenia wszystkie pozycje checklisty. Poza dwoma wyjątkami wszystko jest OK.
INSERT INTO inspection_answers (inspection_id, checklist_item_id, is_ok, comment)
SELECT i.id,
       c.id,
       (i.id, c.code) NOT IN ((2, 'HORN'), (4, 'BRAKES')),
       CASE
           WHEN (i.id, c.code) = (2, 'HORN')   THEN 'Brak sygnału po naciśnięciu przycisku.'
           WHEN (i.id, c.code) = (4, 'BRAKES') THEN 'Hamulec reaguje z opóźnieniem, pedał zapada się.'
       END
  FROM inspections i
 CROSS JOIN checklist_items c;

-- Usterka 2 (CRITICAL) uruchamia trg_faults_critical_after_insert i wyłącza WD-04.
INSERT INTO faults (id, forklift_id, reported_by, inspection_id, title, description, severity, status, created_at, resolved_at, resolved_by, resolution_note) OVERRIDING SYSTEM VALUE VALUES
    (1, 3, 2, 2,    'Nie działa sygnał dźwiękowy',
        'Podczas kontroli przed jazdą klakson nie reaguje na przycisk.',
        'MEDIUM', 'RESOLVED',
        date_trunc('hour', now()) - interval '3 days' - interval '10 minutes',
        date_trunc('hour', now()) - interval '2 days' - interval '4 hours', 1,
        'Wymieniono bezpiecznik obwodu klaksonu.'),
    (2, 4, 3, 4,    'Hamulec roboczy reaguje z opóźnieniem',
        'Pedał hamulca zapada się, droga hamowania wyraźnie dłuższa niż zwykle.',
        'CRITICAL', 'OPEN',
        date_trunc('hour', now()) - interval '1 day' - interval '10 minutes',
        NULL, NULL, NULL),
    (3, 7, 3, NULL, 'Pęknięta osłona tylnej lampy',
        'Osłona lampy pęknięta po otarciu o regał, lampa działa.',
        'LOW', 'IN_PROGRESS',
        date_trunc('hour', now()) - interval '1 hour',
        NULL, NULL, NULL),
    (4, 2, 2, NULL, 'Plama oleju pod masztem',
        'Na posadzce pod masztem widoczna plama oleju.',
        'MEDIUM', 'REJECTED',
        date_trunc('hour', now()) - interval '5 days',
        date_trunc('hour', now()) - interval '4 days', 1,
        'Plama pochodziła z innego pojazdu, wózek nie ma wycieku.');

-- Po wstawieniu rekordów z jawnymi id ustawiamy sekwencje, żeby kolejne INSERT-y z aplikacji nie kolidowały.
SELECT setval(pg_get_serial_sequence('roles', 'id'),                 (SELECT max(id) FROM roles));
SELECT setval(pg_get_serial_sequence('users', 'id'),                 (SELECT max(id) FROM users));
SELECT setval(pg_get_serial_sequence('warehouses', 'id'),            (SELECT max(id) FROM warehouses));
SELECT setval(pg_get_serial_sequence('forklift_models', 'id'),       (SELECT max(id) FROM forklift_models));
SELECT setval(pg_get_serial_sequence('forklifts', 'id'),             (SELECT max(id) FROM forklifts));
SELECT setval(pg_get_serial_sequence('technical_inspections', 'id'), (SELECT max(id) FROM technical_inspections));
SELECT setval(pg_get_serial_sequence('checklist_items', 'id'),       (SELECT max(id) FROM checklist_items));
SELECT setval(pg_get_serial_sequence('reservations', 'id'),          (SELECT max(id) FROM reservations));
SELECT setval(pg_get_serial_sequence('inspections', 'id'),           (SELECT max(id) FROM inspections));
SELECT setval(pg_get_serial_sequence('faults', 'id'),                (SELECT max(id) FROM faults));

COMMIT;
