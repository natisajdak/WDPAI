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
