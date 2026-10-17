<?php

$today = date('Y-m-d');
$vacation = date('Y-m-d', strtotime('+54 days'));

$data = [
    'kids' => [
        ['id' => 1, 'name' => 'Sam', 'initial' => 'S', 'color' => '#ec5281', 'sort' => 1],
        ['id' => 2, 'name' => 'Jordan', 'initial' => 'J', 'color' => '#03a9f4', 'sort' => 2],
        ['id' => 3, 'name' => 'Riley', 'initial' => 'R', 'color' => '#a78bfa', 'sort' => 3],
        ['id' => 4, 'name' => 'Casey', 'initial' => 'C', 'color' => '#4ade80', 'sort' => 4],
    ],
    'chores' => [
        $today => [
            ['id' => 101, 'kid_id' => 1, 'title' => 'Make the bed', 'emoji' => '🛏️', 'done' => false],
            ['id' => 102, 'kid_id' => 1, 'title' => 'Feed the dog', 'emoji' => '🐕', 'done' => true],
            ['id' => 103, 'kid_id' => 2, 'title' => 'Unload dishwasher', 'emoji' => '🍽️', 'done' => false],
            ['id' => 104, 'kid_id' => 2, 'title' => 'Take out trash', 'emoji' => '🗑️', 'done' => true],
            ['id' => 105, 'kid_id' => 3, 'title' => 'Tidy bedroom', 'emoji' => '🧹', 'done' => false],
            ['id' => 106, 'kid_id' => 4, 'title' => 'Put away toys', 'emoji' => '🧸', 'done' => false],
        ],
    ],
    'chore_templates' => [],
    'schedules' => [
        $today => [
            ['id' => 107, 'time_start' => '07:30', 'time_end' => '08:00', 'title' => 'Breakfast', 'emoji' => '🥞', 'sort' => 0],
            ['id' => 108, 'time_start' => '08:00', 'time_end' => '14:30', 'title' => 'School', 'emoji' => '📚', 'sort' => 1],
            ['id' => 109, 'time_start' => '15:00', 'time_end' => '16:00', 'title' => 'Outside time', 'emoji' => '☀️', 'sort' => 2],
            ['id' => 110, 'time_start' => '18:00', 'time_end' => '19:00', 'title' => 'Family dinner', 'emoji' => '🍲', 'sort' => 3],
        ],
    ],
    'countdowns' => [
        ['id' => 111, 'label' => 'Family Vacation', 'emoji' => '🏔️', 'target_date' => $vacation],
    ],
    'verses' => [
        ['id' => 112, 'text' => 'As for me and my house, we will serve the Lord.', 'ref' => 'Joshua 24:15'],
    ],
    'active_verse_id' => 112,
    'timer' => ['active' => false, 'end_time' => null, 'label' => ''],
    'data_version' => 1,
    'nextId' => 113,
];

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
