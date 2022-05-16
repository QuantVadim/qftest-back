<?php
    $GB_TRANSIT = [
        'procent'=>
        [
            'name'=> 'procent',
            'fullName'=> '100% система оценивания',
            'label'=> '100-процентное',
            'body'=> [] //Особое оценивание. параметры не нужны
        ],
        'point5'=>
        [
            'name'=> 'point5',
            'label'=> '5-бальное',
            'fullName'=> '5-бальная система оценивания',
            'body'=> [
                ['value'=> 0, 'label'=> '2', 'description'=> 'Неуд.'],
                ['value'=> 60, 'label'=> '3', 'description'=> 'Удовлет.'],
                ['value'=> 80, 'label'=> '4', 'description'=> 'Хорошо'],
                ['value'=> 90, 'label'=> '5', 'description'=> 'Отлично']
            ]
        ],
        'star3'=>
        [
            'name'=> 'star3',
            'label'=> '3-х звёздочное',
            'fullName'=> '3-х звёздочная система оценивания',
            'body'=> [
                ['value'=> 0, 'label'=> '⭕', 'description'=> 'Неуд.'],
                ['value'=> 60, 'label'=> '👍', 'description'=> 'Удовлет.'],
                ['value'=> 80, 'label'=> '👍👍', 'description'=> 'Хорошо'],
                ['value'=> 90, 'label'=> '👍👍👍', 'description'=> 'Отлично']
            ]
        ],
    ];
?>