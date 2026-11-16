-- Cargo: schemat i dane demonstracyjne.
-- W etapie 2 plik zostanie wygenerowany przez bin/export-db.sh z migracji i seedów.
-- Postgres wykonuje go przy pierwszym starcie kontenera db (pusty wolumen pgdata).

CREATE EXTENSION IF NOT EXISTS btree_gist;
