<?php

// Deutsche Fehlermeldungen für die Regeln, die FA-Digital verwendet.
// Die Feldnamen stehen im Nominativ, z. B. „Der Liefertermin fehlt.“

return [
    'after_or_equal' => ':Attribute darf nicht in der Vergangenheit liegen.',
    'array' => ':Attribute ist ungültig.',
    'boolean' => ':Attribute ist ungültig.',
    'date' => ':Attribute ist kein gültiges Datum.',
    'email' => ':Attribute ist keine gültige E-Mail-Adresse.',
    'exists' => ':Attribute ist ungültig.',
    'in' => ':Attribute ist ungültig.',
    'integer' => ':Attribute muss eine ganze Zahl sein.',
    'max' => [
        'numeric' => ':Attribute darf höchstens :max sein.',
        'string' => ':Attribute darf höchstens :max Zeichen haben.',
        'array' => ':Attribute darf höchstens :max Einträge haben.',
    ],
    'min' => [
        'numeric' => ':Attribute muss mindestens :min sein.',
        'string' => ':Attribute muss mindestens :min Zeichen haben.',
        'array' => ':Attribute muss mindestens :min Einträge haben.',
    ],
    'numeric' => ':Attribute muss eine Zahl sein.',
    'required' => ':Attribute fehlt.',
    'string' => ':Attribute ist ungültig.',
    'unique' => ':Attribute ist schon vergeben.',

    'attributes' => [
        'agBezeichnung' => 'die Bezeichnung',
        'agNummer' => 'die AG-Nummer',
        'arbeitsplatzId' => 'der Arbeitsplatz',
        'artikelId' => 'der Artikel',
        'artikelnummer' => 'die Artikel-Nr.',
        'bemerkung' => 'die Bemerkung',
        'bezeichnung' => 'die Bezeichnung',
        'email' => 'die E-Mail-Adresse',
        'kostenstelle' => 'die Kostenstelle',
        'kunde' => 'der Kunde',
        'liefertermin' => 'der Liefertermin',
        'menge' => 'die Menge',
        'nachname' => 'der Nachname',
        'neuesPasswort' => 'das neue Passwort',
        'nummer' => 'die Nummer',
        'passwort' => 'das Passwort',
        'password' => 'das Passwort',
        'passwortFuer' => 'der Benutzer',
        'personalnummer' => 'die Personalnummer',
        'rolle' => 'die Rolle',
        'ruestzeit' => 'die Rüstzeit',
        'stueckzeit' => 'die Stückzeit',
        'stundensatz' => 'der Stundensatz',
        'vorname' => 'der Vorname',
        'zertifikate.*' => 'das Zertifikat',
    ],
];
