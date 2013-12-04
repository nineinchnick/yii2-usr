<?php

/**
 * BaseUsrForm class.
 * BaseUsrForm is the base class for forms extensible using behaviors, which can add attributes and rules.
 */
abstract class BaseUsrForm extends CFormModel
{
	private static $_names=array();
	private $_behaviors=array();
	private $_userIdentityClass;

	public function getUserIdentityClass()
	{
		return $this->_userIdentityClass;
	}

	public function setUserIdentityClass($value)
	{
		$this->_userIdentityClass = $value;
	}

	public function attachBehavior($name, $behavior)
	{
		$this->_behaviors[$name] = $name;
		unset(self::$_names[get_class($this)]);
		return parent::attachBehavior($name, $behavior);
	}

	public function detachBehavior($name)
	{
		if (isset($this->_behaviors[$name]))
			unset($this->_behaviors[$name]);
		unset(self::$_names[get_class($this)]);
		return parent::detachBehavior($name);
	}

	public function attributeNames()
	{
		$className=get_class($this);
		if(!isset(self::$_names[$className]))
		{
			$class=new ReflectionClass(get_class($this));
			$names=array();
			foreach($class->getProperties() as $property)
			{
				$name=$property->getName();
				if($property->isPublic() && !$property->isStatic())
					$names[]=$name;
			}
			foreach($this->_behaviors as $name=>$name) {
				if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
					$names = array_merge($names, $behavior->attributeNames());
			}
			return self::$_names[$className]=$names;
		}
		else
			return self::$_names[$className];
	}

	public function getBehaviorLabels()
	{
		$labels = array();
		foreach($this->_behaviors as $name=>$foo) {
			if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
				$labels = array_merge($labels, $behavior->attributeLabels());
		}
		return $labels;
	}

	public function getBehaviorRules()
	{
		$rules = array();
		foreach($this->_behaviors as $name=>$foo) {
			if (($behavior=$this->asa($name)) instanceof FormModelBehavior)
				$rules = array_merge($rules, $behavior->rules());
		}
		return $rules;
	}
}
