<?php /*
@var $this DefaultController
@var $model ProfileForm */
nineinchnick\usr\components\PopupAsset::register($this);
?>
        <ul>
<?php foreach ($this->context->module->hybridauthProviders as $provider => $settings): if (!$settings['enabled']) {
     continue;
 } ?>
            <li>
                <?php if (Yii::$app->user->isGuest): ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/popup', ['provider' => $provider]); ?>"
                    onclick="return PopupCenter($(this).attr('href'), 'Hybridauth', 400, 550);">
                    <?php echo Yii::t('UsrModule.usr', 'Log in using {provider}', ['{provider}' => $provider]); ?>
                </a>
                <?php elseif (isset($model) && $model->getIdentity()->hasRemoteIdentity(strtolower($provider))): ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/logout', ['provider' => $provider]); ?>">
                    <?php echo Yii::t('UsrModule.usr', 'Disconnect with {provider}', ['{provider}' => $provider]); ?>
                </a>
                <?php else: ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/popup', ['provider' => $provider]); ?>"
                    onclick="return PopupCenter($(this).attr('href'), 'Hybridauth', 400, 550);">
                    <?php echo Yii::t('UsrModule.usr', 'Associate this profile with {provider}', ['{provider}' => $provider]); ?>
                </a>
                <?php endif; ?>
            </li>
<?php endforeach; ?>
        </ul>
