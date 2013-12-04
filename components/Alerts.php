<?php

namespace nineinchnick\usr\components;

use Yii;
use yii\helpers\Html;

/**
 * Alerts displays flash messages.
 *
 * ~~~
 * // $this is the view object currently being used
 * echo Alerts::widget();
 * ~~~
 *
 * @author Jan Waś <jwas@nets.com.pl>
 */
class Alerts extends \yii\base\Widget
{
	/**
	 * Renders the widget.
	 */
	public function run()
	{
		if (($flashMessages = Yii::$app->session->getAllFlashes())) {
			echo '<ul class="flashes">';
			foreach($flashMessages as $key => $message) {
				echo '<li><div class="alert alert-'.$key.'">'.$message.'</div></li>';
			}
			echo '</ul>';
		}
	}
}
