<?php

$migrationPath=Yii::getPathOfAlias('vendors.nineinchnick.yii-usr.migrations');

$handle=opendir($migrationPath);
while(($file=readdir($handle))!==false)
{
	if($file==='.' || $file==='..')
		continue;
	$path=$migrationPath.DIRECTORY_SEPARATOR.$file;
	if(preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/',$file,$matches) && is_file($path) && !isset($applied[$matches[2]]))
		$migrations[]=$matches[1];
}
closedir($handle);
sort($migrations);

foreach($migrations as $class) {
	$file=$migrationPath.DIRECTORY_SEPARATOR.$class.'.php';
	require_once($file);
	$migration=new $class;
	$migration->setDbConnection($this->getDbConnection());
	if($migration->up()===false) {
		echo 'something went terribly wrong!';
	}
		
}
