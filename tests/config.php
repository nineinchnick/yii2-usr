<?php
return [
	'databases' => [
		'sqlite' => [
			'dsn' => 'sqlite::memory:',
			'fixture' => __DIR__ . '/fixtures/sqlite.sql',
		],
	],
];
