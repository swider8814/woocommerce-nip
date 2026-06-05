# WooCommerce NIP Field

Lekka wtyczka WordPress/WooCommerce dodająca opcjonalne pole NIP do klasycznego checkoutu WooCommerce.

## Funkcje

- Dodaje pole `billing_nip` w danych rozliczeniowych.
- Pole NIP jest opcjonalne.
- Jeśli NIP jest wpisany, wymagana jest też nazwa firmy.
- Waliduje polski NIP: dokładnie 10 cyfr i poprawna suma kontrolna.
- Zapisuje NIP w meta zamówienia jako `_billing_nip`.
- Pokazuje NIP w szczegółach zamówienia w panelu WooCommerce.
- Dodaje NIP do maili WooCommerce.
- Deklaruje zgodność z WooCommerce HPOS.
- Ładuje minimalny JavaScript tylko na stronie checkout.

## Wymagania

- WordPress
- WooCommerce
- Klasyczny checkout WooCommerce
- PHP 8.0 lub nowszy

## Instalacja

1. Skopiuj katalog `woocommerce-nip` do `wp-content/plugins/`.
2. Włącz wtyczkę w panelu WordPress.
3. Sprawdź checkout WooCommerce.

## Walidacja

Puste pole NIP nie blokuje zamówienia.

Jeśli klient wpisze NIP:

- wartość musi zawierać dokładnie 10 cyfr,
- numer musi mieć poprawną sumę kontrolną,
- pole „Nazwa firmy” musi być uzupełnione.

## Brak integracji z GUS

Wtyczka celowo nie pobiera danych z GUS/REGON. Pozostaje lekka i nie wymaga kluczy API, zewnętrznych requestów ani dodatkowej konfiguracji.

## Licencja

GPL-2.0-or-later
