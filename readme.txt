=== Checkout-Felder – Checkout Field Editor für WooCommerce ===
Contributors: andrekipphard
Tags: woocommerce, checkout, checkout fields, checkout manager, custom fields
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bearbeite, sortiere und ergänze die WooCommerce-Bestellfelder ohne Code.

== Description ==

**Checkout-Felder** gibt dir vollständige Kontrolle über die Checkout-Felder deines WooCommerce-Shops – ohne eine einzige Zeile Code.

= Was du damit machen kannst =

* **Felder aktivieren/deaktivieren** – blende Standard-WooCommerce-Felder aus die du nicht brauchst.
* **Beschriftungen und Platzhalter anpassen** – passe jedes Feld-Label und den Platzhaltertext an.
* **Pflichtfelder steuern** – mache Felder verpflichtend oder optional.
* **Reihenfolge bestimmen** – ordne Felder per Positionsnummer frei an.
* **Eigene Felder hinzufügen** – ergänze benutzerdefinierte Textfelder und Textbereiche.
* **Werte in Bestellungen** – alle Werte werden mit der Bestellung gespeichert und im Admin sowie in E-Mails angezeigt.

= Kostenlos =

Feldtypen: Text, Textbereich (Textarea).

= Checkout-Felder Pro =

[Jetzt upgraden](https://products.kipphard.com/checkout-felder) für erweiterte Funktionen:

* **Erweiterte Feldtypen**: Dropdown (Select), Checkbox, Radio-Buttons, Datumswähler, Zahlenfeld.
* **Bedingte Logik**: Felder dynamisch ein- und ausblenden basierend auf dem Wert eines anderen Felds.

= Sprachen =

Der Plugin-Text liegt in Deutsch vor. Übersetzungen können über translate.wordpress.org beigesteuert werden.

== Installation ==

1. Plugin-Ordner in `/wp-content/plugins/` hochladen oder über das WordPress-Plugin-Verzeichnis installieren.
2. Plugin unter „Plugins" aktivieren.
3. Einstellungen unter **WooCommerce → Checkout-Felder** vornehmen.

**Voraussetzung:** WooCommerce muss installiert und aktiv sein.

== Frequently Asked Questions ==

= Funktioniert das Plugin mit meinem Theme? =

Ja. Das Plugin nutzt ausschließlich WooCommerce-Standardhooks und ist daher mit allen WooCommerce-kompatiblen Themes kompatibel.

= Werden bestehende Bestellungen beeinflusst? =

Nein. Änderungen am Feldeditor wirken sich nur auf zukünftige Bestellungen aus.

= Was passiert beim Deinstallieren? =

Beim Deinstallieren werden die Optionen `ckf_fields` und `ckf_settings` aus der Datenbank gelöscht. Bestellmeta-Einträge (`_ckf_*`) bleiben erhalten.

== Screenshots ==

1. Feldeditor – Überblick aller Checkout-Felder nach Abschnitt.
2. Eigenes Feld hinzufügen.
3. Einstellungsseite mit Pro-Teaser.

== Changelog ==

= 0.1.0 =
* Erste Veröffentlichung.

== Upgrade Notice ==

= 0.1.0 =
Erste Version.
