<?php

return [
    
    'createTablePeople' => "create table if not exists people
(
    pk_people                INTEGER PRIMARY KEY,
    nom                      TEXT                   not null,
    prenom                   TEXT                   null,
    adresse                  TEXT                   null,
    code_postal              TEXT                   null,
    ville                    TEXT                   null,
    telephone                TEXT                   null,
    portable                 TEXT                   null,
    fax                      TEXT                   null,
    email                    TEXT                   null,
    fk_entreprise            int                                 null,
    actif                    int default 1,
    adresse_2                TEXT                      null,
    adresse_3                TEXT                      null,
    horo                     DATETIME DEFAULT CURRENT_TIMESTAMP
);
",
    'createViewPeople'  =>
        "CREATE VIEW IF NOT EXISTS v_people as select * from people;",
];
