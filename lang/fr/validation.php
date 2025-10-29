<?php

return [
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'after' => 'Le champ :attribute doit être une date postérieure à :date.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],

    'attributes' => [
        'dateDebutBlocage' => 'date de début de blocage',
        'dateFinBlocage' => 'date de fin de blocage',
        'raison' => 'raison',
    ],

    'custom' => [
        'dateDebutBlocage' => [
            'after_or_equal' => 'La date de début de blocage doit être égale ou postérieure à aujourd\'hui.',
        ],
        'dateFinBlocage' => [
            'after' => 'La date de fin de blocage doit être postérieure à la date de début.',
        ],
    ],
];
