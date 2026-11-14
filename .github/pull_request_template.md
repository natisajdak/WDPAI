## Co zmienia ten PR

<!-- Krótko: jaka funkcja / poprawka i dlaczego. -->

## Kryteria

<!-- Których kryteriów z PLAN.md dotyczy, np. [K4] [K15]. -->

## Jak sprawdzić

1.
2.

## Checklista

- [ ] Brak SQL w kontrolerach i widokach, zapytania z parametrami
- [ ] Wyjście w szablonach przez `e()`, teksty przez `t()` (PL i EN)
- [ ] Uprawnienia sprawdzane w backendzie
- [ ] Brak sekretów, `var_dump`, `console.log` i zakomentowanego kodu
- [ ] Testy przechodzą (`docker compose exec app composer test`)
