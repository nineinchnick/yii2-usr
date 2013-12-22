<?php

namespace nineinchnick\usr\models;

use Yii;

/**
 * BaseUsrForm class.
 * BaseUsrForm is the base class for forms extensible using behaviors, which can add attributes and rules.
 */
abstract class BaseUsrForm extends \yii\base\Model
{
	/**
	 * @inheritdoc
	 */
	private $_behaviors=array();

	/**
	 * @inheritdoc
	 *
	 * Additionally, tracks attached behaviors to allow iterating over them.
	 */
	public function attachBehavior($name, $behavior)
	{
		$this->_behaviors[$name] = $name;
		return parent::attachBehavior($name, $behavior);
	}

	/**
	 * @inheritdoc
	 *
	 * Additionally, tracks attached behaviors to allow iterating over them.
	 */
	public function detachBehavior($name)
	{
		if (isset($this->_behaviors[$name]))
			unset($this->_behaviors[$name]);
		return parent::detachBehavior($name);
	}

	/**
	 * @inheritdoc
	 *
	 * Additionally, adds attributes defined in attached behaviors that extend FormModelBehavior.
	 */
	public function attributes()
	{
		$names=parent::attributes();
		foreach($this->_behaviors as $name=>$name) {
			if (($behavior=$this->getBehavior($name)) instanceof \nineinchnick\usr\components\FormModelBehavior)
				$names = array_merge($names, $behavior->attributes());
		}
		return $names;
	}

	/**
	 * Returns attribute labels defined in attached behaviors that extend FormModelBehavior.
	 * @return array attribute labels (name => label)
	 * @see Model::attributeLabels()
	 */
	public function getBehaviorLabels()
	{
		$labels = array();
		foreach($this->_behaviors as $name=>$foo) {
			if (($behavior=$this->getBehavior($name)) instanceof \nineinchnick\usr\components\FormModelBehavior)
				$labels = array_merge($labels, $behavior->attributeLabels());
		}
		return $labels;
	}

	/**
	 * Returns rules defined in attached behaviors that extend FormModelBehavior.
	 * @return array validation rules
	 * @see Model::rules()
	 */
	public function getBehaviorRules()
	{
		$rules = array();
		foreach($this->_behaviors as $name=>$foo) {
			if (($behavior=$this->getBehavior($name)) instanceof \nineinchnick\usr\components\FormModelBehavior)
				$rules = array_merge($rules, $behavior->rules());
		}
		return $rules;
	}
}
