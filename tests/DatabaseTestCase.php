<?php

namespace nineinchnick\usr\tests;

abstract class DatabaseTestCase extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $appConfig = $this->getParam('app');
        $appConfig['components']['db'] = $this->getParam('db');
        $this->mockApplication($appConfig, '\yii\console\Application');
        $this->runMigrations();
        $this->runFixtures();
    }

    protected function tearDown()
    {
        $this->destroyApplication();
    }

    protected function getDbConnection()
    {
        return \Yii::$app->get('db');
    }

    protected function runMigrations()
    {
        $migrationPath = \Yii::getAlias('@nineinchnick/usr/migrations');
        $migrations = [];

        $handle = opendir($migrationPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $migrationPath.DIRECTORY_SEPARATOR.$file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path)) {
                $migrations[] = $matches[1];
            }
        }
        closedir($handle);
        sort($migrations);

        ob_start();
        foreach ($migrations as $class) {
            $file = $migrationPath.DIRECTORY_SEPARATOR.$class.'.php';
            require_once $file;
            $migration = new $class(['db' => $this->getDbConnection()]);

            if ($migration->up() === false) {
                echo "something went terribly wrong!\n";
                echo ob_get_contents();
                echo "\n";
            }
        }
        ob_end_clean();
    }

    protected function runFixtures()
    {
        $fixturesPath = \Yii::getAlias('@nineinchnick/usr/tests/fixtures');
        $fixtures = [];

        $handle = opendir($fixturesPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $fixturesPath.DIRECTORY_SEPARATOR.$file;
            if (preg_match('/^(.*)\.php$/', $file, $matches) && is_file($path)) {
                $fixtures[] = $matches[1];
            }
        }
        closedir($handle);
        sort($fixtures);

        foreach ($fixtures as $file) {
            $fixture = require $fixturesPath.DIRECTORY_SEPARATOR.$file.'.php';

            foreach ($fixture as $row) {
                $this->getDbConnection()->createCommand()->insert($file, $row)->execute();
            }
        }
    }
}
