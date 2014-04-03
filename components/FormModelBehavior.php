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
 * @property CFormModel $owner The owner model that this behavior is attached to.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
abstract class FormModelBehavior extends \yii\base\Behavior
{
    private $_ruleOptions = [];

    /**
     * Validation rules for attributes of this behavior, that should be merged with rules in the owner model.
     * @return array validation rules
     *               @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [];
    }

    /**
     * Labels for attributes of this behavior, that should be merged with labels in the owner model.
     * @return array attribute labels (name => label)
     *               @see \yii\base\Model::attributeLabels()
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
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->getName() != 'owner') {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * Adds current rule options to the given set of rules.
     * @param  array $rules
     * @return array
     */
    public function applyRuleOptions($rules)
    {
        foreach ($rules as $key=>$rule) {
            foreach ($this->_ruleOptions as $name=>$value) {
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
