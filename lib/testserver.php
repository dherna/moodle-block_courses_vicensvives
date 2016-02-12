<?php

header('Content-type: application/json');

$data = array(
    '/oauth' => array(
        'client_name' => 'Client de proves',
        'access_token' => '000000000000000000000000000000',
        'token_type' => 'Bearer',
        'expires_in' => 3600000,
    ),
    '/levels' => array(
        array('idLevel' => 1, 'shortname' => "1r d'ESO"),
        array('idLevel' => 2, 'shortname' => "2n d'ESO"),
        array('idLevel' => 3, 'shortname' => "3r d'ESO"),
        array('idLevel' => 4, 'shortname' => "4t d'ESO"),
    ),
    '/subjects' => array(
        array('idSubject' => 1, 'name' => 'Llengua'),
        array('idSubject' => 2, 'name' => 'Matemàtiques'),
        array('idSubject' => 3, 'name' => 'Socials'),
        array('idSubject' => 4, 'name' => 'Naturals'),
    ),
    '/books' => array(),
    '/books/1' => json_decode(file_get_contents('testbook.json')),
);

foreach ($data['/subjects'] as $subject) {
    foreach ($data['/levels'] as $level) {
        $data['/books'][] = array(
            'idBook' => count($data['/books']) + 1,
            'fullname' => $subject['name'] . ' ' . $level['idLevel'],
            'shortname' => $subject['idSubject'] . '-' . $level['idLevel'],
            'idLevel' => $level['idLevel'],
            'idSubject' => $subject['idSubject'],
            'lang' => 'ca',
            'isbn' => sprintf('%013d', count($data['/books']) + 1),
        );
    }
}

if (isset($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} elseif (isset($_GET['file'])) {
    $path = $_GET['file'];
} else {
    $path = '';
}

if (isset($data[$path])) {
    echo json_encode($data[$path]);
    die;
}

header('HTTP/1.1 404 Not found', true, 404);