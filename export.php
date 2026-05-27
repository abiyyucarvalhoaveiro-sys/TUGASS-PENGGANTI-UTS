<?php

session_start();

require_once '../src/Auth.php';
require_once '../src/DosenRepository.php';

Auth::check();

$repo = new DosenRepository();

$data = $repo->paginate();

header('Content-Type: text/csv');

header(
    'Content-Disposition: attachment; filename=data_dosen.csv'
);

$output = fopen('php://output', 'w');

fputcsv(
    $output,
    ['NIDN', 'Nama', 'Email']
);

foreach ($data as $d) {

    fputcsv($output, [
        $d['nidn'],
        $d['nama'],
        $d['email']
    ]);
}

fclose($output);