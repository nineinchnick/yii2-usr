<?php

namespace nineinchnick\usr\components;

use Yii;
use yii\base\Action;
use nineinchnick\diceware\Diceware;

class DicewareAction extends Action
{
	/**
	 * @var integer Number of words in password generated using the diceware component.
	 */
	public $length;
	/**
	 * @var boolean Should an extra digit be added in password generated using the diceware component.
	 */
	public $extraDigit;
	/**
	 * @var integer Should an extra random character be added in password generated using the diceware component.
	 */
	public $extraChar;

	public function run() {
		$diceware = new Diceware(Yii::$app->language);
		$password = $diceware->get_phrase($this->dicewareLength, $this->dicewareExtraDigit, $this->dicewareExtraChar);
		echo json_encode($password);
	}
}