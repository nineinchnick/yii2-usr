<?php
/**
 * FormModelBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

namespace nineinchnick\usr\components;

/**
 * FormModelBehavior is a base class for behaviors that are attached to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * @property \yii\base\Model $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
abstract class FormModelBehavior extends \yii\base\Behavior
{
    private static $_names = [];

    private $_ruleOptions = [];

    /**
     * Adds validation rules for attributes of this behavior or removes rules from the owner model.
     * @return array validation rules
     * @see \yii\base\Model::rules()
     */
    public function filterRules($rules = [])
    {
        return $rules;
    }

    /**
     * Labels for attributes of this behavior, that should be merged with labels in the owner model.
     * @return array attribute labels (name => label)
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $className = get_class($this);
        if (!isset(self::$_names[$className])) {
            $class = new \ReflectionClass(get_class($this));
            $names = [];
            foreach ($class->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isPublic() && !$property->isStatic()) {
                    $names[] = $name;
                }
            }

            return self::$_names[$className] = $names;
        } else {
            return self::$_names[$className];
        }
    }

    /**
     * Lists valid model scenarios.
     * @return array
     */
    public function scenarios()
    {
        return [];
    }

    /**
     * Adds current rule options to the given set of rules.
     * @param  array $rules
     * @return array
     */
    public function applyRuleOptions($rules)
    {
        foreach ($rules as $key => $rule) {
            foreach ($this->_ruleOptions as $name => $value) {
                $rules[$key][$name] = $value;
            }
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function getRuleOptions()
    {
        return $this->_ruleOptions;
    }

    /**
     * @param $value array
     */
    public function setRuleOptions(array $value)
    {
        $this->_ruleOptions = $value;
    }
}
