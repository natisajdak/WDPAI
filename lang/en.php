<?php

declare(strict_types=1);

// Keys must match lang/pl.php exactly (checked by TranslatorTest).
return [
    'app.name' => 'Cargo',

    'nav.skip_to_content' => 'Skip to content',
    'nav.main_label' => 'Main navigation',
    'nav.home' => 'Home',

    'lang.switch_label' => 'Language',
    'lang.pl' => 'Polski',
    'lang.en' => 'English',

    'footer.text' => '© :year Cargo – forklift fleet management',

    'home.title' => 'Cargo',
    'home.intro' => 'Forklift fleet management: reservations, pre-shift checks, faults and technical inspections.',

    'error.back_home' => 'Back to the home page',
    'error.generic.title' => 'Something went wrong',
    'error.400.title' => 'Bad request',
    'error.400.message' => 'The request contains invalid data.',
    'error.401.title' => 'Sign-in required',
    'error.401.message' => 'Please sign in to continue.',
    'error.403.title' => 'Access denied',
    'error.403.message' => 'You do not have permission to do this.',
    'error.404.title' => 'Page not found',
    'error.404.message' => 'The page you are looking for does not exist or has been moved.',
    'error.405.title' => 'Method not allowed',
    'error.405.message' => 'This address does not support the HTTP method used.',
    'error.500.title' => 'Server error',
    'error.500.message' => 'Something went wrong on our side. Please try again in a moment.',
    'error.invalid_id' => 'The identifier in the address must be a positive integer.',
    'error.invalid_json' => 'The request body is not valid JSON.',
    'error.csrf' => 'The form session has expired. Refresh the page and try again.',
    'error.validation' => 'Please correct the highlighted fields.',

    'validation.required' => 'This field is required.',
    'validation.string' => 'The value must be text.',
    'validation.email' => 'Enter a valid email address.',
    'validation.length_min' => 'Enter at least :min characters.',
    'validation.length_max' => 'Enter at most :max characters.',
    'validation.integer' => 'Enter a whole number.',
    'validation.int_range' => 'Enter a number between :min and :max.',
    'validation.date' => 'Enter a valid date.',
    'validation.in_list' => 'Choose one of the available options.',
];
