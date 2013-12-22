<?php

namespace nineinchnick\usr\tests\unit;

use nineinchnick\usr\tests\DatabaseTestCase as DatabaseTestCase;
use nineinchnick\usr\models;

class ProfileFormTest extends DatabaseTestCase
{
	public $fixtures=array(
		'users'=>'User',
	);

	public static function validDataProvider() {
		return array(
			array(
				'scenario' => '',
				'attributes' => array(
					'username'=>'trin',
					'email'=>'trinity@matrix.com',
					'firstName'=>'Trinity',
					'lastName'=>'Latex',
				),
			),
		);
	}

	public static function invalidDataProvider() {
		return array(
			array(
				'scenario' => 'register',
				'attributes' => array(
					'username'=>'neo',
					'email'=>'neo@matrix.com',
					'firstName'=>'Neo',
					'lastName'=>'Confused',
				),
				'errors ' => array(
					'username'=>array('neo has already been used by another user.'),
				),
			),
		);
	}

	public static function allDataProvider() {
		return array_merge(self::validDataProvider(), self::invalidDataProvider());
	}

	public function testWithBehavior()
	{
		$form = new models\ProfileForm;
		$formAttributes = $form->attributes();
		$formRules = $form->rules();
		$formLabels = $form->attributeLabels();
		$form->attachBehavior('captcha', array('class' => '\nineinchnick\usr\components\CaptchaFormBehavior'));
		$behaviorAttributes = $form->getBehavior('captcha')->attributes();
		$behaviorRules = $form->getBehavior('captcha')->rules();
		$behaviorLabels = $form->getBehavior('captcha')->attributeLabels();
		$this->assertEquals(array_merge($formAttributes, $behaviorAttributes), $form->attributes());
		$this->assertEquals(array_merge($behaviorRules, $formRules), $form->rules());
		$this->assertEquals(array_merge($formLabels, $behaviorLabels), $form->attributeLabels());
		$form->detachBehavior('captcha');
		$this->assertEquals($formAttributes, $form->attributes());
		$this->assertEquals($formAttributes, $form->attributes());
	}

	/**
	 * @dataProvider validDataProvider
	 */
	public function testValid($scenario, $attributes)
	{
		$form = new models\ProfileForm($scenario);
		$form->setAttributes($attributes);
		$this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(),true));
		$this->assertEmpty($form->getErrors());
	}


	/**
	 * @dataProvider invalidDataProvider
	 */
	public function testInvalid($scenario, $attributes, $errors)
	{
		$form = new models\ProfileForm;
		$form->setScenario($scenario);
		$form->setAttributes($attributes);
		$this->assertFalse($form->validate());
		$this->assertEquals($errors, $form->getErrors());
	}
}
