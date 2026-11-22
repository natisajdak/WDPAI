-- Próby logowania do ograniczania ataków na hasła (limit nieudanych prób na e-mail i adres IP).
-- Bez klucza obcego do users celowo: liczymy także próby na nieistniejące konta,
-- a e-mail może nie należeć do żadnego użytkownika.

CREATE TABLE login_attempts (
    id            bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email         varchar(254) NOT NULL,
    ip_address    inet         NOT NULL,
    succeeded     boolean      NOT NULL,
    attempted_at  timestamptz  NOT NULL DEFAULT now()
);

CREATE INDEX login_attempts_email_ip_time_idx ON login_attempts (email, ip_address, attempted_at DESC);
