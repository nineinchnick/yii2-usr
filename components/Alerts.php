<?php

namespace nineinchnick\usr\components;

use Yii;

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
    private $_map = ['error' => 'danger'];
    /**
     * Renders the widget.
     */
    public function run()
    {
        if (($flashMessages = Yii::$app->session->getAllFlashes())) {
            echo '<div class="flashes">';
            foreach ($flashMessages as $key => $message) {
                $cssClasses = 'alert alert-'.(isset($this->_map[$key]) ? $this->_map[$key] : $key);
                echo '<div class="'.$cssClasses.'">'.$message.'</div>';
            }
            echo '</div>';
        }
    }
}
