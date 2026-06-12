# WooCommerce NIP Field

Lekka wtyczka WordPress/WooCommerce dodająca opcjonalne pole NIP do danych rozliczeniowych klienta i klasycznego checkoutu WooCommerce.

## Zakres

Wtyczka obsługuje wyłącznie polski NIP w danych rozliczeniowych WooCommerce i klasycznym checkoutcie. Nie integruje się z GUS/REGON, nie pobiera danych firmy automatycznie i nie dodaje konfiguracji w panelu administracyjnym.

## Funkcje

- Dodaje pole `billing_nip` w danych rozliczeniowych.
- Pole NIP jest opcjonalne.
- Jeśli NIP jest wpisany, wymagana jest też nazwa firmy.
- Waliduje polski NIP: dokładnie 10 cyfr, poprawna suma kontrolna i brak wartości placeholderowych typu `1111111111`.
- Dodaje NIP do edycji adresu rozliczeniowego w panelu klienta.
- Podpowiada zapisany NIP w checkoutcie dla zalogowanego klienta.
- Zapisuje NIP w profilu klienta po checkoutcie.
- Zapisuje NIP w meta zamówienia jako `_billing_nip`.
- Pokazuje NIP w szczegółach zamówienia w panelu WooCommerce.
- Pokazuje NIP w adresie rozliczeniowym zamówienia, np. na `order-received`, w szczegółach zamówienia w koncie klienta i w mailach WooCommerce.
- Pokazuje NIP w podglądzie adresu rozliczeniowego w koncie klienta.
- Deklaruje zgodność z WooCommerce HPOS.
- Ładuje minimalny JavaScript tylko na stronie checkout.

## Wymagania

- WordPress
- WooCommerce
- Klasyczny checkout WooCommerce
- PHP 8.0 lub nowszy

## Pliki

- `woocommerce-nip.php` - cały kod wtyczki: rejestracja hooków, pola rozliczeniowe, walidacja, zapis meta, wyświetlanie w adresach, adminie i mailach.
- `README.md` - dokumentacja projektu.
- `LICENSE` - licencja GPL-2.0-or-later.
- `.gitignore` - lokalne ignorowane pliki.

## Instalacja

1. Skopiuj katalog `woocommerce-nip` do `wp-content/plugins/`.
2. Włącz wtyczkę `WooCommerce NIP Field` w panelu WordPress.
3. Upewnij się, że sklep używa klasycznego checkoutu WooCommerce.
4. Przetestuj checkout z pustym NIP, poprawnym NIP i błędnym NIP.

## Zachowanie checkoutu

Pole NIP jest dodawane do sekcji rozliczeniowej po polu `billing_company`. Jeśli pole firmy nie istnieje, NIP jest dodawany na końcu pól rozliczeniowych.

Puste pole NIP nie blokuje zamówienia. Jeśli klient wpisze NIP:

- wartość musi zawierać dokładnie 10 cyfr,
- numer musi mieć poprawną sumę kontrolną,
- pole `billing_company` musi być uzupełnione.

Błędy walidacji są przypisane do pól `billing_nip` i `billing_company`, aby WooCommerce mógł wskazać klientowi właściwe miejsce formularza.

Dla zalogowanego klienta checkout podpowiada NIP zapisany wcześniej w `billing_nip`. Skrypt checkoutu ogranicza NIP do cyfr i 10 znaków oraz przywraca zapisane wartości pól rozliczeniowych, jeśli zewnętrzny skrypt motywu wyczyści je po załadowaniu strony.

## Konto klienta

Pole NIP jest dodawane do edycji adresu rozliczeniowego w „Moje konto”. Przy zapisie adresu używana jest ta sama walidacja NIP i firmy co w checkoutcie.

Zapisany NIP jest widoczny w podglądzie adresu rozliczeniowego w koncie klienta.

## Dane zamówienia

Po poprawnym złożeniu zamówienia NIP jest zapisywany w meta zamówienia:

```text
_billing_nip
```

Wartość jest widoczna:

- w szczegółach zamówienia w panelu WooCommerce,
- w adresie rozliczeniowym zamówienia na stronie `order-received`,
- w adresie rozliczeniowym zamówienia w koncie klienta,
- w adresie rozliczeniowym w mailach WooCommerce.

## Hooki i funkcje

Wtyczka rejestruje się po `plugins_loaded`, ale kończy działanie, jeśli klasa `WooCommerce` nie jest dostępna.

Główne punkty integracji:

- `woocommerce_checkout_fields` - dodanie pola NIP.
- `woocommerce_checkout_get_value` - wczytanie zapisanego NIP do checkoutu.
- `woocommerce_billing_fields` - dodanie pola NIP do formularza adresu rozliczeniowego klienta.
- `woocommerce_my_account_my_address_formatted_address` - pokazanie NIP w podglądzie adresu rozliczeniowego klienta.
- `woocommerce_order_formatted_billing_address` - pokazanie NIP w adresie rozliczeniowym zamówienia.
- `woocommerce_order_get_formatted_billing_address` - awaryjne dopisanie NIP do finalnego adresu rozliczeniowego.
- `woocommerce_after_checkout_validation` - walidacja NIP i firmy.
- `woocommerce_after_save_address_validation` - walidacja NIP przy zapisie adresu rozliczeniowego w koncie.
- `woocommerce_checkout_create_order` - zapis `_billing_nip`.
- `woocommerce_checkout_update_user_meta` - zapis `billing_nip` w profilu klienta po checkoutcie.
- `woocommerce_admin_order_data_after_billing_address` - wyświetlanie NIP w adminie.
- `wp_enqueue_scripts` - inline JS tylko na checkoutcie.
- `before_woocommerce_init` - deklaracja zgodności z HPOS.

Najważniejsze funkcje:

- `woocommerce_nip_add_checkout_field()` - buduje i wstawia pole `billing_nip`.
- `woocommerce_nip_get_checkout_field_value()` - podpowiada zapisany NIP w checkoutcie.
- `woocommerce_nip_add_billing_address_field()` - dodaje NIP do formularza adresu rozliczeniowego.
- `woocommerce_nip_add_my_account_address_nip()` - pokazuje NIP w podglądzie adresu rozliczeniowego klienta.
- `woocommerce_nip_add_order_billing_address_nip()` - pokazuje NIP w adresie rozliczeniowym zamówienia.
- `woocommerce_nip_add_formatted_order_billing_address_nip()` - dopisuje NIP do finalnego adresu, jeśli nie trafił tam wcześniej.
- `woocommerce_nip_validate_checkout_field()` - waliduje relację NIP/firma.
- `woocommerce_nip_get_validation_errors()` - współdzieli reguły walidacji dla checkoutu i konta klienta.
- `woocommerce_nip_validate_billing_address_field()` - waliduje NIP przy zapisie adresu rozliczeniowego.
- `woocommerce_nip_is_valid()` - sprawdza format i sumę kontrolną polskiego NIP.
- `woocommerce_nip_save_order_meta()` - zapisuje poprawny NIP w meta zamówienia.
- `woocommerce_nip_save_customer_billing_nip()` - zapisuje poprawny NIP w profilu klienta.
- `woocommerce_nip_enqueue_checkout_script()` - ogranicza wpisywanie NIP do cyfr i 10 znaków oraz chroni wypełnione pola checkoutu przed wyczyszczeniem przez frontend.

## Testowanie ręczne

Minimalna lista kontroli przed wydaniem:

1. Włącz WooCommerce i klasyczny checkout.
2. Złóż zamówienie bez NIP - zamówienie powinno przejść.
3. Złóż zamówienie z poprawnym NIP i nazwą firmy - NIP powinien zapisać się w zamówieniu.
4. Złóż zamówienie z poprawnym NIP bez nazwy firmy - checkout powinien pokazać błąd firmy.
5. Złóż zamówienie z błędnym NIP - checkout powinien pokazać błąd NIP.
6. Sprawdź, czy NIP jest widoczny w panelu zamówienia.
7. Sprawdź, czy NIP jest widoczny na stronie `order-received`.
8. Sprawdź, czy NIP jest widoczny w szczegółach zamówienia w koncie klienta.
9. Sprawdź, czy NIP jest widoczny w adresie rozliczeniowym maila WooCommerce, a nie jako osobne pole meta.
10. Zapisz NIP w adresie rozliczeniowym konta i sprawdź, czy podpowiada się w checkoutcie.
11. Sprawdź checkout z włączonym HPOS.

Przykładowy poprawny NIP testowy:

```text
8567346215
```

## Sprawdzenie składni

Przed commitem uruchom:

```bash
php -l woocommerce-nip.php
```

## Wydanie

1. Zaktualizuj nagłówek `Version` w `woocommerce-nip.php`, jeśli zmienia się wersja.
2. Uruchom `php -l woocommerce-nip.php`.
3. Przejdź checklistę testów ręcznych.
4. Zacommituj zmianę.
5. Wypchnij branch do GitHuba.
6. Opcjonalnie dodaj tag wersji.

## Zasady zmian

- Utrzymuj wtyczkę bez zależności zewnętrznych, chyba że jest to konieczne.
- Nie dodawaj integracji GUS/REGON bez osobnej decyzji projektowej.
- Zachowuj zgodność z klasycznym checkoutem WooCommerce.
- Przy zmianach walidacji testuj puste pole, błędny NIP i poprawny NIP.
- Przy zmianach danych zamówienia zachowaj meta key `_billing_nip`, jeśli nie ma zaplanowanej migracji.

## Licencja

GPL-2.0-or-later
