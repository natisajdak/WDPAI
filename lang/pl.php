<?php

declare(strict_types=1);

// Klucze muszą być identyczne w lang/pl.php i lang/en.php (pilnuje tego test TranslatorTest).
return [
    'app.name' => 'Cargo',

    'nav.skip_to_content' => 'Przejdź do treści',
    'nav.main_label' => 'Nawigacja główna',
    'nav.home' => 'Strona główna',

    'lang.switch_label' => 'Język',
    'lang.pl' => 'Polski',
    'lang.en' => 'English',

    'footer.text' => '© :year Cargo – zarządzanie flotą wózków widłowych',

    'home.title' => 'Cargo',
    'home.intro' => 'System do zarządzania flotą wózków widłowych: rezerwacje, kontrola przed jazdą, usterki i badania UDT.',

    'error.back_home' => 'Wróć na stronę główną',
    'error.generic.title' => 'Wystąpił błąd',
    'error.400.title' => 'Niepoprawne żądanie',
    'error.400.message' => 'Żądanie zawiera niepoprawne dane.',
    'error.401.title' => 'Wymagane logowanie',
    'error.401.message' => 'Zaloguj się, aby kontynuować.',
    'error.403.title' => 'Brak dostępu',
    'error.403.message' => 'Nie masz uprawnień do tej operacji.',
    'error.404.title' => 'Nie znaleziono strony',
    'error.404.message' => 'Strona, której szukasz, nie istnieje albo została przeniesiona.',
    'error.405.title' => 'Niedozwolona metoda',
    'error.405.message' => 'Ten adres nie obsługuje użytej metody HTTP.',
    'error.500.title' => 'Błąd serwera',
    'error.500.message' => 'Coś poszło nie tak po naszej stronie. Spróbuj ponownie za chwilę.',
    'error.invalid_id' => 'Identyfikator w adresie musi być dodatnią liczbą całkowitą.',
    'error.invalid_json' => 'Treść żądania nie jest poprawnym JSON-em.',
    'error.csrf' => 'Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.',
    'error.validation' => 'Popraw zaznaczone pola.',

    'validation.required' => 'To pole jest wymagane.',
    'validation.string' => 'Wartość musi być tekstem.',
    'validation.email' => 'Podaj poprawny adres e-mail.',
    'validation.length_min' => 'Minimalna liczba znaków: :min.',
    'validation.length_max' => 'Maksymalna liczba znaków: :max.',
    'validation.integer' => 'Podaj liczbę całkowitą.',
    'validation.int_range' => 'Podaj liczbę od :min do :max.',
    'validation.date' => 'Podaj poprawną datę.',
    'validation.in_list' => 'Wybierz jedną z dostępnych opcji.',
];
