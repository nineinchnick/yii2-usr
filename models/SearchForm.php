<?php

namespace nineinchnick\usr\models;

use nineinchnick\usr\components\IdentityInterface;
use Yii;

/**
 * SearchForm class.
 * SearchForm is the data structure for keeping search form data used when fetching a data provider to display a list of identities.
 */
class SearchForm extends \yii\base\Model
{
    public $id;
    public $username;
    public $email;
    public $firstName;
    public $lastName;
    public $createdOn;
    public $updatedOn;
    public $lastVisitOn;
    public $emailVerified;
    public $isActive;
    public $isDisabled;

    public $anyText;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    public function rules()
    {
        return [
            [['id', 'username', 'email', 'firstName', 'lastName', 'createdOn', 'updatedOn', 'lastVisitOn', 'emailVerified', 'isActive', 'isDisabled', 'anyText'], 'trim'],
            [['id', 'username', 'email', 'firstName', 'lastName', 'createdOn', 'updatedOn', 'lastVisitOn', 'emailVerified', 'isActive', 'isDisabled', 'anyText'], 'default'],
            ['id', 'number', 'integerOnly' => true, 'max' => 0x7FFFFFFF, 'min' => -0x8000000], // 32-bit integers
            [['createdOn', 'updatedOn', 'lastVisitOn'], 'date', 'format' => ['yyyy-MM-dd', 'yyyy-MM-dd hh:mm', '?yyyy-MM-dd', '?yyyy-MM-dd hh:mm', '??yyyy-MM-dd', '??yyyy-MM-dd hh:mm']],
            [['emailVerified', 'isActive', 'isDisabled'], 'boolean'],
        ];
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('manager', 'ID'),
            'username'        => Yii::t('manager', 'Username'),
            'email'            => Yii::t('manager', 'Email'),
            'firstName'        => Yii::t('manager', 'Firstname'),
            'lastName'        => Yii::t('manager', 'Lastname'),
            'createdOn'        => Yii::t('manager', 'Created On'),
            'updatedOn'        => Yii::t('manager', 'Updated On'),
            'lastVisitOn'    => Yii::t('manager', 'Last Visit On'),
            'emailVerified'    => Yii::t('manager', 'Email Verified'),
            'isActive'        => Yii::t('manager', 'Is Active'),
            'isDisabled'    => Yii::t('manager', 'Is Disabled'),
            'anyText'        => Yii::t('manager', 'Search'),
        ];
    }

    public function getIdentity($id = null)
    {
        if ($this->_identity === null) {
            $identityClass = Yii::$app->user->identityClass;
            $this->_identity = $identityClass::findIdentity(['id' => $id !== null ? $id : Yii::$app->user->getId()]);
            if ($this->_identity !== null && !($this->_identity instanceof \nineinchnick\usr\components\ManagedIdentityInterface)) {
                throw new \yii\web\ServerErrorHttpException(Yii::t('usr', 'The {class} class must implement the {interface} interface.', ['{class}' => get_class($this->_identity), '{interface}' => 'ManagedIdentityInterface']));
            }
        }

        return $this->_identity;
    }
}
