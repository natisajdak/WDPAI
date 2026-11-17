-- Cargo: schemat bazy i dane demonstracyjne.
-- PLIK GENEROWANY przez bin/build-sql.sh. Nie edytuj ręcznie: zmieniaj database/migrations i database/seeds.
-- Import na pustej bazie: psql -v ON_ERROR_STOP=1 -d cargo -f database/cargo.sql

-- ============================================================================
-- database/migrations/001_extensions.sql
-- ============================================================================

-- btree_gist pozwala połączyć w jednym ograniczeniu EXCLUDE równość (forklift_id)
-- z nakładaniem się przedziałów czasu (tstzrange). Pilnuje tego, by rezerwacje się nie nakładały.
CREATE EXTENSION IF NOT EXISTS btree_gist;

-- ============================================================================
-- database/migrations/002_schema.sql
-- ============================================================================

-- Schemat tabel. Uzasadnienie zachowań ON DELETE: PLAN.md, sekcja 5.2.
-- Statusy są tekstem z CHECK zamiast typu ENUM, bo łatwiej je potem rozszerzać i mapować na enumy PHP.

-- Użytkownicy i role ---------------------------------------------------------

CREATE TABLE roles (
    id        smallint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code      varchar(20)  NOT NULL UNIQUE,
    name_key  varchar(100) NOT NULL,
    CONSTRAINT roles_code_check CHECK (code IN ('OPERATOR', 'ADMIN'))
);

CREATE TABLE users (
    id             bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    role_id        smallint     NOT NULL REFERENCES roles (id) ON DELETE RESTRICT,
    email          varchar(254) NOT NULL UNIQUE,
    -- NULL do czasu ustawienia hasła z linku w e-mailu powitalnym.
    password_hash  varchar(255),
    first_name     varchar(100) NOT NULL,
    last_name      varchar(100) NOT NULL,
    locale         char(2)      NOT NULL DEFAULT 'pl',
    is_active      boolean      NOT NULL DEFAULT true,
    created_at     timestamptz  NOT NULL DEFAULT now(),
    last_login_at  timestamptz,
    -- Aplikacja zapisuje e-mail małymi literami, więc zwykły UNIQUE wystarcza do wykrycia duplikatów.
    CONSTRAINT users_email_lowercase_check CHECK (email = lower(email)),
    CONSTRAINT users_locale_check CHECK (locale IN ('pl', 'en'))
);

CREATE INDEX users_role_id_idx ON users (role_id);

CREATE TABLE password_reset_tokens (
    id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     bigint      NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    -- SHA-256 tokenu (hex). Sam token trafia tylko do e-maila.
    token_hash  char(64)    NOT NULL UNIQUE,
    expires_at  timestamptz NOT NULL,
    used_at     timestamptz,
    created_at  timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX password_reset_tokens_user_id_idx ON password_reset_tokens (user_id);
CREATE INDEX password_reset_tokens_expires_at_idx ON password_reset_tokens (expires_at);

-- Flota ----------------------------------------------------------------------

CREATE TABLE warehouses (
    id       int GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name     varchar(100) NOT NULL UNIQUE,
    address  varchar(255) NOT NULL
);

CREATE TABLE forklift_models (
    id                  int GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    manufacturer        varchar(100) NOT NULL,
    model_name          varchar(100) NOT NULL,
    fuel_type           varchar(10)  NOT NULL,
    max_load_kg         integer      NOT NULL,
    max_lift_height_mm  integer      NOT NULL,
    CONSTRAINT forklift_models_name_key UNIQUE (manufacturer, model_name),
    CONSTRAINT forklift_models_fuel_type_check CHECK (fuel_type IN ('ELECTRIC', 'LPG', 'DIESEL')),
    CONSTRAINT forklift_models_max_load_check CHECK (max_load_kg > 0),
    CONSTRAINT forklift_models_lift_height_check CHECK (max_lift_height_mm > 0)
);

CREATE TABLE forklifts (
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    model_id         int         NOT NULL REFERENCES forklift_models (id) ON DELETE RESTRICT,
    warehouse_id     int         REFERENCES warehouses (id) ON DELETE SET NULL,
    internal_code    varchar(20) NOT NULL UNIQUE,
    serial_number    varchar(50) NOT NULL UNIQUE,
    production_year  smallint    NOT NULL,
    -- „Zarezerwowany” nie jest statusem: wynika z tabeli reservations, więc go nie dublujemy.
    status           varchar(20) NOT NULL DEFAULT 'AVAILABLE',
    created_at       timestamptz NOT NULL DEFAULT now(),
    updated_at       timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT forklifts_status_check CHECK (status IN ('AVAILABLE', 'OUT_OF_SERVICE', 'RETIRED')),
    -- Górną granicę (bieżący rok) sprawdza aplikacja, bo CHECK nie może zależeć od now().
    CONSTRAINT forklifts_production_year_check CHECK (production_year BETWEEN 1950 AND 2100)
);

CREATE INDEX forklifts_model_id_idx ON forklifts (model_id);
CREATE INDEX forklifts_warehouse_id_idx ON forklifts (warehouse_id);
CREATE INDEX forklifts_status_idx ON forklifts (status);

-- Badania okresowe UDT -------------------------------------------------------

CREATE TABLE technical_inspections (
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    forklift_id      bigint      NOT NULL REFERENCES forklifts (id) ON DELETE CASCADE,
    inspection_date  date        NOT NULL,
    -- Badanie jest ważne do końca tego dnia włącznie.
    valid_until      date        NOT NULL,
    protocol_number  varchar(50) NOT NULL UNIQUE,
    notes            text,
    created_at       timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT technical_inspections_validity_check CHECK (valid_until > inspection_date)
);

CREATE INDEX technical_inspections_forklift_valid_idx ON technical_inspections (forklift_id, valid_until DESC);

CREATE TABLE udt_reminders (
    id                       bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    technical_inspection_id  bigint      NOT NULL REFERENCES technical_inspections (id) ON DELETE CASCADE,
    days_before              smallint    NOT NULL,
    sent_at                  timestamptz NOT NULL DEFAULT now(),
    -- Jedno przypomnienie danego typu na badanie: ponowne uruchomienie CRON nie wyśle duplikatu.
    CONSTRAINT udt_reminders_once_key UNIQUE (technical_inspection_id, days_before),
    CONSTRAINT udt_reminders_days_before_check CHECK (days_before > 0)
);

-- Rezerwacje -----------------------------------------------------------------

CREATE TABLE reservations (
    id            bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    forklift_id   bigint      NOT NULL REFERENCES forklifts (id) ON DELETE RESTRICT,
    user_id       bigint      NOT NULL REFERENCES users (id) ON DELETE RESTRICT,
    starts_at     timestamptz NOT NULL,
    ends_at       timestamptz NOT NULL,
    status        varchar(20) NOT NULL DEFAULT 'ACTIVE',
    created_at    timestamptz NOT NULL DEFAULT now(),
    cancelled_at  timestamptz,
    CONSTRAINT reservations_period_check CHECK (ends_at > starts_at),
    CONSTRAINT reservations_status_check CHECK (status IN ('ACTIVE', 'CANCELLED', 'COMPLETED')),
    CONSTRAINT reservations_cancelled_at_check CHECK ((status = 'CANCELLED') = (cancelled_at IS NOT NULL)),
    -- Ostatnia linia obrony przed wyścigiem dwóch równoczesnych rezerwacji (R3).
    -- Przedział [) sprawia, że rezerwacja 8:00–16:00 nie koliduje z 16:00–20:00.
    CONSTRAINT reservations_forklift_no_overlap EXCLUDE USING gist (
        forklift_id WITH =,
        tstzrange(starts_at, ends_at, '[)') WITH &&
    ) WHERE (status <> 'CANCELLED'),
    -- Operator nie prowadzi dwóch wózków naraz (R5).
    CONSTRAINT reservations_user_no_overlap EXCLUDE USING gist (
        user_id WITH =,
        tstzrange(starts_at, ends_at, '[)') WITH &&
    ) WHERE (status <> 'CANCELLED')
);

-- Dla zadania CRON zamykającego zakończone rezerwacje.
CREATE INDEX reservations_status_ends_at_idx ON reservations (status, ends_at);

-- Kontrola przed jazdą -------------------------------------------------------

CREATE TABLE checklist_items (
    id           int GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code         varchar(50)  NOT NULL UNIQUE,
    -- Klucz tłumaczenia z katalogu lang/, a nie tekst, żeby checklista działała w PL i EN.
    label_key    varchar(100) NOT NULL,
    is_critical  boolean      NOT NULL DEFAULT false,
    sort_order   smallint     NOT NULL DEFAULT 0,
    is_active    boolean      NOT NULL DEFAULT true
);

CREATE TABLE inspections (
    id              bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    -- UNIQUE: jedna kontrola na rezerwację (R6). Wózek i operator wynikają z rezerwacji.
    reservation_id  bigint      NOT NULL UNIQUE REFERENCES reservations (id) ON DELETE CASCADE,
    performed_at    timestamptz NOT NULL DEFAULT now(),
    notes           text
);

CREATE TABLE inspection_answers (
    inspection_id      bigint  NOT NULL REFERENCES inspections (id) ON DELETE CASCADE,
    checklist_item_id  int     NOT NULL REFERENCES checklist_items (id) ON DELETE RESTRICT,
    is_ok              boolean NOT NULL,
    comment            text,
    PRIMARY KEY (inspection_id, checklist_item_id),
    -- Odpowiedź „NIE OK” musi mieć opis problemu.
    CONSTRAINT inspection_answers_comment_check CHECK (is_ok OR (comment IS NOT NULL AND btrim(comment) <> ''))
);

CREATE INDEX inspection_answers_checklist_item_id_idx ON inspection_answers (checklist_item_id);

-- Usterki --------------------------------------------------------------------

CREATE TABLE faults (
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    forklift_id      bigint       NOT NULL REFERENCES forklifts (id) ON DELETE RESTRICT,
    reported_by      bigint       REFERENCES users (id) ON DELETE SET NULL,
    inspection_id    bigint       REFERENCES inspections (id) ON DELETE SET NULL,
    title            varchar(150) NOT NULL,
    description      text         NOT NULL,
    severity         varchar(10)  NOT NULL,
    status           varchar(20)  NOT NULL DEFAULT 'OPEN',
    created_at       timestamptz  NOT NULL DEFAULT now(),
    resolved_at      timestamptz,
    resolved_by      bigint       REFERENCES users (id) ON DELETE SET NULL,
    resolution_note  text,
    CONSTRAINT faults_severity_check CHECK (severity IN ('LOW', 'MEDIUM', 'CRITICAL')),
    CONSTRAINT faults_status_check CHECK (status IN ('OPEN', 'IN_PROGRESS', 'RESOLVED', 'REJECTED')),
    CONSTRAINT faults_resolved_at_check CHECK ((status IN ('RESOLVED', 'REJECTED')) = (resolved_at IS NOT NULL))
);

CREATE INDEX faults_forklift_status_idx ON faults (forklift_id, status);
CREATE INDEX faults_reported_by_idx ON faults (reported_by);
CREATE INDEX faults_resolved_by_idx ON faults (resolved_by);
CREATE INDEX faults_inspection_id_idx ON faults (inspection_id);

CREATE TABLE fault_photos (
    id             bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fault_id       bigint       NOT NULL REFERENCES faults (id) ON DELETE CASCADE,
    -- Ścieżka względem storage/uploads. Sam plik nigdy nie trafia do bazy.
    file_path      varchar(255) NOT NULL UNIQUE,
    original_name  varchar(255) NOT NULL,
    mime_type      varchar(50)  NOT NULL,
    size_bytes     integer      NOT NULL,
    uploaded_at    timestamptz  NOT NULL DEFAULT now(),
    CONSTRAINT fault_photos_mime_type_check CHECK (mime_type IN ('image/jpeg', 'image/png', 'image/webp')),
    CONSTRAINT fault_photos_size_check CHECK (size_bytes > 0)
);

CREATE INDEX fault_photos_fault_id_idx ON fault_photos (fault_id);

-- Log zadań cyklicznych ------------------------------------------------------

CREATE TABLE cron_runs (
    id             bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    job_name       varchar(100) NOT NULL,
    started_at     timestamptz  NOT NULL DEFAULT now(),
    finished_at    timestamptz,
    status         varchar(10)  NOT NULL DEFAULT 'RUNNING',
    affected_rows  integer,
    message        text,
    CONSTRAINT cron_runs_status_check CHECK (status IN ('RUNNING', 'SUCCESS', 'FAILED')),
    CONSTRAINT cron_runs_finished_check CHECK ((status = 'RUNNING') = (finished_at IS NULL))
);

CREATE INDEX cron_runs_job_started_idx ON cron_runs (job_name, started_at DESC);

-- ============================================================================
-- database/migrations/003_views_functions_triggers.sql
-- ============================================================================

-- Widoki, funkcje i wyzwalacze wykorzystywane przez aplikację.

-- Aktualizacja znacznika czasu ------------------------------------------------

CREATE FUNCTION fn_set_updated_at() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    NEW.updated_at := now();
    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_forklifts_set_updated_at
    BEFORE UPDATE ON forklifts
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

-- Usterka krytyczna wyłącza wózek (R8) ---------------------------------------
-- Robi to baza, a nie aplikacja, żeby wózek z niesprawnymi hamulcami nie pozostał dostępny
-- niezależnie od tego, którą ścieżką usterka trafiła do systemu.

CREATE FUNCTION fn_fault_critical_disable_forklift() RETURNS trigger
LANGUAGE plpgsql AS $$
BEGIN
    UPDATE forklifts
       SET status = 'OUT_OF_SERVICE'
     WHERE id = NEW.forklift_id
       AND status = 'AVAILABLE';

    -- Trwająca rezerwacja kończy się normalnie, anulujemy tylko te, które jeszcze się nie zaczęły.
    UPDATE reservations
       SET status = 'CANCELLED',
           cancelled_at = now()
     WHERE forklift_id = NEW.forklift_id
       AND status = 'ACTIVE'
       AND starts_at > now();

    RETURN NULL;
END;
$$;

CREATE TRIGGER trg_faults_critical_after_insert
    AFTER INSERT ON faults
    FOR EACH ROW
    WHEN (NEW.severity = 'CRITICAL')
    EXECUTE FUNCTION fn_fault_critical_disable_forklift();

-- Dostępność wózka w przedziale czasu (R1–R3) --------------------------------
-- Wywoływana w transakcji rezerwacji po zablokowaniu wiersza wózka (SELECT ... FOR UPDATE).

CREATE FUNCTION fn_forklift_is_available(p_forklift_id bigint, p_from timestamptz, p_to timestamptz)
RETURNS boolean
LANGUAGE sql STABLE AS $$
    SELECT EXISTS (
        SELECT 1
          FROM forklifts f
         WHERE f.id = p_forklift_id
           AND f.status = 'AVAILABLE'
           AND EXISTS (
                SELECT 1
                  FROM technical_inspections ti
                 WHERE ti.forklift_id = f.id
                   AND ti.valid_until >= p_to::date
           )
           AND NOT EXISTS (
                SELECT 1
                  FROM reservations r
                 WHERE r.forklift_id = f.id
                   AND r.status <> 'CANCELLED'
                   AND tstzrange(r.starts_at, r.ends_at, '[)') && tstzrange(p_from, p_to, '[)')
           )
    );
$$;

-- Przegląd floty: lista wózków i dashboard ----------------------------------

CREATE VIEW v_forklift_overview AS
SELECT f.id,
       f.internal_code,
       f.serial_number,
       f.production_year,
       f.status,
       f.warehouse_id,
       w.name AS warehouse_name,
       m.id AS model_id,
       m.manufacturer,
       m.model_name,
       m.fuel_type,
       m.max_load_kg,
       m.max_lift_height_mm,
       udt.valid_until AS udt_valid_until,
       COALESCE(udt.valid_until >= CURRENT_DATE, false) AS is_udt_valid,
       faults.open_count AS open_faults_count,
       faults.open_critical_count AS open_critical_faults_count,
       EXISTS (
           SELECT 1
             FROM reservations r
            WHERE r.forklift_id = f.id
              AND r.status = 'ACTIVE'
              AND now() >= r.starts_at
              AND now() < r.ends_at
       ) AS is_in_use_now
  FROM forklifts f
  JOIN forklift_models m ON m.id = f.model_id
  LEFT JOIN warehouses w ON w.id = f.warehouse_id
  LEFT JOIN LATERAL (
       SELECT max(ti.valid_until) AS valid_until
         FROM technical_inspections ti
        WHERE ti.forklift_id = f.id
  ) udt ON true
  LEFT JOIN LATERAL (
       SELECT count(*) AS open_count,
              count(*) FILTER (WHERE fa.severity = 'CRITICAL') AS open_critical_count
         FROM faults fa
        WHERE fa.forklift_id = f.id
          AND fa.status IN ('OPEN', 'IN_PROGRESS')
  ) faults ON true;

-- Raport miesięczny wykorzystania (panel admina) ----------------------------

CREATE VIEW v_monthly_usage AS
WITH usage AS (
    SELECT r.forklift_id,
           date_trunc('month', r.starts_at)::date AS month,
           count(*) AS reservations_count,
           sum(extract(epoch FROM r.ends_at - r.starts_at)) / 3600 AS hours_used
      FROM reservations r
     WHERE r.status = 'COMPLETED'
     GROUP BY r.forklift_id, date_trunc('month', r.starts_at)
),
fault_counts AS (
    SELECT fa.forklift_id,
           date_trunc('month', fa.created_at)::date AS month,
           count(*) AS faults_count
      FROM faults fa
     GROUP BY fa.forklift_id, date_trunc('month', fa.created_at)
)
SELECT f.id AS forklift_id,
       f.internal_code,
       m.manufacturer,
       m.model_name,
       COALESCE(u.month, fc.month) AS month,
       COALESCE(u.reservations_count, 0) AS reservations_count,
       round(COALESCE(u.hours_used, 0), 1) AS hours_used,
       COALESCE(fc.faults_count, 0) AS faults_count
  FROM usage u
  FULL JOIN fault_counts fc
    ON fc.forklift_id = u.forklift_id
   AND fc.month = u.month
  JOIN forklifts f ON f.id = COALESCE(u.forklift_id, fc.forklift_id)
  JOIN forklift_models m ON m.id = f.model_id;

-- ============================================================================
-- database/seeds/001_demo_data.sql
-- ============================================================================

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
