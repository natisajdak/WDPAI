# AGENTS.md

## Cel projektu
System zarządzania flotą. Architektura MVC, czysty PHP 8.4, brak zewnętrznych frameworków backendowych i frontendowych.

## Struktura
- `public/index.php` — punkt wejścia, router i dispatcher.
- `public/views/` — widoki HTML z rozszerzeniem `.html.php`.
- `public/css/style.css` — główny plik stylów (zmienne CSS, mobile-first).
- `src/Controller/` — kontrolery aplikacji.

## Zasady pracy
- Zawsze używaj `declare(strict_types=1)` w plikach PHP.
- Nie dodawaj bibliotek (npm, composer) bez zgody.
- Kontroler przygotowuje dane, widok odpowiada tylko za prezentację HTML.
- Dane wyświetlane w widokach zabezpieczaj przez `htmlspecialchars()`.
- Wprowadzaj zmiany w małych iteracjach i zawsze pytaj przed edycją.

## Uruchamianie i weryfikacja
- Start środowiska: `docker compose up -d`
- Zawsze pokaż podsumowanie zmian (`git diff`) przed zakończeniem zadania.

## Bezpieczeństwo
- Nie czytaj ani nie edytuj pliku `.env`.
- Nie wykonuj destrukcyjnych poleceń w terminalu.