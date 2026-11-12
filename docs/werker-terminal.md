# Werker-Terminal

Ein Werker sieht nach der Anmeldung nur das Terminal und die ihm zugeordneten, freigegebenen Arbeitsgänge. Admin, Meister oder Arbeitsvorbereitung ordnen auf der Auftragsdetailseite unter **Werker zuordnen** einen oder mehrere Werker je Arbeitsgang zu. Ohne Zuordnung kann ein Werker den Arbeitsgang auch durch direkte Aufrufe nicht starten. Bereits laufende oder unterbrochene Arbeit kann nicht entzogen werden.

Der Werker startet Rüsten oder Bearbeiten. Die Zeit wird automatisch dem Auftrag und Arbeitsgang zugeordnet. Bei einem Problem wählt er unter **Arbeit unterbrechen** einen Grund wie „Material fehlt“ und kann eine Bemerkung ergänzen. Beim Fortsetzen läuft die Zeiterfassung weiter; die Unterbrechungszeit zählt nicht als Arbeitszeit. Unabhängige Notizen lassen sich während der Arbeit oder Unterbrechung speichern und sind auch in den Auftragsdetails für die zuständigen Rollen sichtbar. Fertigmelden erfordert wie bisher eine erledigte Checkliste sowie Gutmenge und Ausschuss.

Für eine lokale Demonstration ist der optionale Seeder `WerkerDemoSeeder` vorgesehen. Nach den normalen Migrationen und dem Standard-Seeder im Ordner `laravel/`:

```sh
docker compose exec app php artisan db:seed --class=WerkerDemoSeeder
```

Er legt nur in `APP_ENV=local` einen Demo-Werker, einen Demo-Arbeitsplatz und einen freigegebenen Beispielauftrag an. Anmeldung: `demo.werker@fa-digital.local`, Passwort `WerkerDemo123!`. Das sind ausschließlich öffentliche Entwicklungsbeispiele. Der Seeder ändert ein bereits bestehendes Konto mit dieser E-Mail nicht und ersetzt keine vorhandenen Daten. Bereits vorhandene Werker erhalten nicht automatisch Zuordnungen; sie müssen bewusst zugewiesen werden. Nach dem Abschluss des Demo-Arbeitsgangs verschwindet er aus der offenen Arbeitsliste. Neue Benutzer können Admins weiterhin über **Benutzer** anlegen.

Die Ideen zu sichtbaren Aufträgen, einfach bedienbarer Zeiterfassung und Unterbrechungen mit Grund orientieren sich unter anderem an [MachineMetrics Job Tracking](https://docs.machinemetrics.com/docs/product-guides/production-tracking/job-tracking-guide/) und [Planitools Shop-Floor-Terminals](https://www.planitools.com/en/blog/planitools-blog-2/shop-floor-terminals-at-the-workstation-why-and-how-15). Die genaue Umsetzung folgt dem vorhandenen FA-Digital-Datenmodell.

## Auftrag mit Werkszeugnis und Messung

Bei einem Auftrag mit Werkszertifikat beendet der Werker zunächst die Fertigungsarbeit mit **Arbeit beenden und messen**. Die Fertigungszeit wird gestoppt und eine eigene Messseite geöffnet. Der Arbeitsgang bleibt auf **Messen**, bis alle hinterlegten Prüfpunkte gespeichert und mit **Messung abschließen** abgeschlossen wurden. Offene Messungen können über das Terminal wieder geöffnet werden. Bei mehreren Arbeitsgängen muss auch der letzte Arbeitsgang erledigt sein, bevor der Auftrag fertig wird. Ein Arbeitsgang ohne eigene Prüfpunkte kann ohne Messseite abgeschlossen werden, wenn der Auftrag an anderer Stelle Prüfpunkte hat. Hat ein Zertifikatsauftrag überhaupt keine Prüfpunkte, bleibt er offen und die Arbeitsvorbereitung muss den Arbeitsplan prüfen.

Die Messseite zeigt die im Arbeitsplan hinterlegten Prüfmerkmale; sie prüft keine vollständige Normkonformität eines Werkszeugnisses. Auch ein erfasstes Ergebnis „n. i. O.“ zählt als ausgefüllt, verhindert aber die gesonderte Freigabe des Zertifikats durch den Meister. Fertigmeldung und Zertifikatsfreigabe sind also zwei Schritte. Die Zeit auf der Messseite wird nicht als Fertigungszeit gestempelt.

Für ein zweites lokales Beispiel nach Migration und Standard-Testdaten:

```sh
docker compose exec app php artisan db:seed --class=ZertifikatWerkerDemoSeeder
```

Anmeldung: `demo.messung@fa-digital.local` / `MessungDemo123!` (nur Entwicklung). Der Beispielauftrag enthält eine Längenmessung mit dem Bereich 99,9–100,1 mm und eine Sichtprüfung. Der Seeder legt Daten nur in `APP_ENV=local` an und überschreibt keine vorhandenen Konten.
