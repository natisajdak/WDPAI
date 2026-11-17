#!/bin/sh
# Składa database/cargo.sql z migracji i seedów, w kolejności nazw plików.
# Wynik odtwarza całą bazę na pustym serwerze i jest importowany przy pierwszym starcie kontenera db.
# Uruchamiaj po każdej zmianie w database/migrations lub database/seeds i commituj razem z nimi.
set -eu

root_dir=$(cd "$(dirname "$0")/.." && pwd)
output="$root_dir/database/cargo.sql"

{
    echo "-- Cargo: schemat bazy i dane demonstracyjne."
    echo "-- PLIK GENEROWANY przez bin/build-sql.sh. Nie edytuj ręcznie: zmieniaj database/migrations i database/seeds."
    echo "-- Import na pustej bazie: psql -v ON_ERROR_STOP=1 -d cargo -f database/cargo.sql"

    for file in "$root_dir"/database/migrations/*.sql "$root_dir"/database/seeds/*.sql; do
        echo
        echo "-- ============================================================================"
        echo "-- ${file#"$root_dir"/}"
        echo "-- ============================================================================"
        echo
        cat "$file"
    done
} > "$output"

echo "Zapisano ${output#"$root_dir"/}"
