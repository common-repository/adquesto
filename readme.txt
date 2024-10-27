=== Adquesto ===
Contributors: adquesto
Tags: ads, advertising, paywall, membership, subscriptions, monetization
Requires at least: 3.4.0
Tested up to: 5.3.0
Stable tag: trunk
Requires PHP: 5.4

Adquesto to pierwsza bezpłatna bramka treści („darmowy paywall”), która zamiast opłaty wymaga rozwiązania questu reklamowego.

== Description ==

Dzięki [Adquesto](https://adquesto.com/pl/) Wydawcy mogą wreszcie w pełni zmonetyzować swój potencjał, bez zarzucania Użytkowników masą tradycyjnych reklam. Stają się też bardziej niezależni od reklamodawców, nie musząc zabiegać o kolejne teksty sponsorowane czy programy afiliacyjne.

Co zyskujesz, dołączając do Adquesto? Mnóstwo!
✔ Wielokrotnie większe zyski niż w innych systemach reklamowych.
✔ Efektywna monetyzacja całego ruchu, także mobilnego!
✔ Pełna niezależność względem reklamodawców.
✔ Możliwość wyświetlania reklam także odbiorcom używającym adblocków!
✔ Dodatkowe zyski z płatnych subskrypcji za treść pozbawioną reklam.

**[Dowiedz się więcej o Adquesto »](https://adquesto.com/)**

== Installation ==

Aby korzystać z Adquesto musisz wcześniej założyć darmowe konto w serwisie. [Załóż konto teraz »](https://system.adquesto.com/publisher/signup)

1. Zainstaluj wtyczkę Adquesto wchodząc na podstronę Wtyczki w panelu administracyjnym WordPressa lub przesyłając pliki do katalogu /wp-content/plugins/ na Twoim serwerze.
2. W celu aktywacji wtyczki przejdź na podstronę Wtyczki w panelu administracyjnym.
3. Aby skonfigurować wtyczkę wejdź w zakładkę Adquesto z poziomu głównego menu w panelu administracyjnym WordPressa. Tam wprowadź klucze konfiguracji, które znajdziesz w panelu Adquesto.
4. Możesz sprawdzić, jak będą wyglądać questy na Twoich wpisach. W tym celu wejdź w edycji dowolnego wpisu i kliknij przycisk Podejrzyj. W podglądzie wpisu wyświetlony zostanie quest testowy.

Jeśli potrzebujesz pomocy lub masz pytania, [skontaktuj się z nami](https://adquesto.com/pl/kontakt/).

== Changelog ==

= 1.1.50 =
* Naprawa problemu z deaktywacją pluginu.

= 1.1.49 =
* Weryfikacja poprawności działania z WordPress 5.3.0

= 1.1.48 =
* Usprawnienie w przechowywaniu pluginu JavaScript.

= 1.1.47 =
* Nowy endpoint umożliwiający dostęp do ustawień wtyczki.

= 1.1.46 =
* Poprawa funkcjonalności umożliwiającej wykupienie subskrypcji.

= 1.1.45 =
* Poprawa funkcjonalności przesyłającej ustawienia pluginu.

= 1.1.44 =
* Naprawienie problemu z wykonywaniem wp-cron.php na php >= 7.0.16 poprzez aktualizację zależności

= 1.1.43 =
* Poprawa funkcjonalności przesyłającej ustawienia pluginu.
* Usunięcie funkcji curl_*

= 1.1.42 =
* Aktualizacja adquesto-php-sdk do wersji 0.4.7 (naprawa problemu braku galerii NextGEN przy włączonym pluginie adquesto)

= 1.1.41 =
* Poprawa zachowania skryptu w przypadku problemów połączenia z serwerem.

= 1.1.40 =
* Użycie wp_remote_get i wp_remote_post zamiast curl.
* Migracje dla tabeli bazy danych.

= 1.1.39 =
* Podniesienie wersji SDK Adquesto z 0.3.x do 0.4.x.
* Kompatybliność z PHP 7.3.

= 1.1.38 =
* Logowanie informacji o deaktywacji pluginu i aktywnych pluginach.
