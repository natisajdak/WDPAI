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
