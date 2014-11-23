<?php

namespace nineinchnick\usr\components;

use yii\web\AssetBundle;

class PopupAsset extends AssetBundle
{
    public $sourcePath = 'nineinchnick/usr';
    public $basePath = '@webroot';
    public $css = [
        'components/assets/zocial.css',
    ];
    public $js = [
        'components/assets/popup.js',
    ];
}
