# Cargo

System do zarządzania flotą wózków widłowych w magazynie: rezerwacje na zmianę, kontrola przed jazdą, zgłaszanie usterek ze zdjęciami i pilnowanie terminów badań UDT.

> Projekt w budowie. Plan i lista zadań: [PLAN.md](PLAN.md). Sekcje oznaczone *(w przygotowaniu)* będą uzupełniane w kolejnych etapach.

**Wdrożenie:** *(w przygotowaniu)*

## Autorzy i zakres odpowiedzialności

| Osoba | Zakres |
|---|---|
| *(do uzupełnienia)* | |

## Opis problemu i główne funkcje

W magazynie kilkunastu operatorów korzysta z kilku wózków. Bez systemu trudno ustalić, kto i kiedy jeździ danym wózkiem i czy wykonał kontrolę przed jazdą. Usterki zgłasza się ustnie, a terminy badań UDT łatwo przegapić.

Główne funkcje:

- rezerwacja wózka na zmianę z podglądem dostępności,
- checklista kontroli przed jazdą,
- zgłaszanie usterek ze zdjęciami; usterka krytyczna automatycznie wyłącza wózek z eksploatacji,
- obsługa usterek, zarządzanie wózkami, badaniami UDT i użytkownikami (panel kierownika),
- przypomnienia e-mail o wygasających badaniach UDT.

## Zrzuty ekranu

*(w przygotowaniu: co najmniej 5 widoków mobilnych i 5 desktopowych)*

## Architektura

*(w przygotowaniu)*

## Diagram ERD bazy danych

*(w przygotowaniu)*

## Routing / endpointy

*(w przygotowaniu)*

## Uruchomienie przez Docker

### Wymagania

- Docker z Docker Compose v2 (Docker Desktop, OrbStack lub Docker Engine)
- Git

### Kroki

```bash
git clone <adres-repozytorium> cargo
```

```bash
cd cargo
```

```bash
cp .env.example .env
```

```bash
docker compose up --build
```

Przy pierwszym starcie kontener `app` instaluje zależności Composera, a `db` importuje `database/cargo.sql`.

### Usługi i porty

| Usługa | Rola | Adres na hoście |
|---|---|---|
| `web` | Nginx, serwuje `public/` i przekazuje żądania do PHP | http://localhost:8080 (`WEB_PORT`) |
| `app` | PHP 8.4-FPM z aplikacją | tylko w sieci Dockera (`app:9000`) |
| `cron` | Ten sam obraz PHP, uruchamia zadania cykliczne przez supercronic | brak |
| `db` | PostgreSQL 17, dane w wolumenie `pgdata` | `localhost:5433` (`DB_FORWARD_PORT`) |
| `mailpit` | Lokalna skrzynka do podglądu wysłanych e-maili | http://localhost:8025 (`MAILPIT_UI_PORT`) |

Kontenery komunikują się po nazwach usług: aplikacja łączy się z `db:5432` i wysyła pocztę na `mailpit:1025`.

### Przydatne polecenia

Odtworzenie bazy od zera (usuwa wolumeny z danymi i uploadami):

```bash
docker compose down -v
```

Konsola PHP w kontenerze aplikacji:

```bash
docker compose exec app sh
```

## Zmienne środowiskowe

Wszystkie zmienne są opisane w [.env.example](.env.example). Plik `.env` nie trafia do repozytorium.

| Zmienna | Opis |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_DEFAULT_LOCALE` | Tryb pracy, adres aplikacji, domyślny język |
| `WEB_PORT`, `MAILPIT_UI_PORT`, `DB_FORWARD_PORT` | Porty wystawione na hoście |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Połączenie z PostgreSQL (te same wartości inicjalizują kontener `db`) |
| `SESSION_NAME`, `SESSION_SECURE`, `SESSION_LIFETIME` | Ustawienia cookie sesji |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Serwer SMTP |
| `UPLOAD_MAX_BYTES` | Maksymalny rozmiar jednego zdjęcia |
| `UDT_REMINDER_DAYS` | Ile dni przed końcem ważności badania UDT wysyłać przypomnienia |

## Import bazy i dane demonstracyjne

*(w przygotowaniu)*

## Testy

*(w przygotowaniu)*

## Raport WCAG

*(w przygotowaniu)*

## Security Bingo

*(w przygotowaniu)*

## Zadanie CRON i wysyłka e-mail

*(w przygotowaniu)*

## Deploy

*(w przygotowaniu)*
