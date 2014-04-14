<?php

namespace nineinchnick\usr\models;

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

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    private $_userIdentityClass;

    public function getUserIdentityClass()
    {
        return $this->_userIdentityClass;
    }

    public function setUserIdentityClass($value)
    {
        $this->_userIdentityClass = $value;
    }

    public function rules()
    {
        return [
            [['id', 'username', 'email', 'firstName', 'lastName', 'createdOn', 'updatedOn', 'lastVisitOn', 'emailVerified', 'isActive', 'isDisabled'], 'filter', 'filter'=>'trim'],
            [['id', 'username', 'email', 'firstName', 'lastName', 'createdOn', 'updatedOn', 'lastVisitOn', 'emailVerified', 'isActive', 'isDisabled'], 'default'],
            ['id', 'number', 'integerOnly'=>true, 'max'=>0x7FFFFFFF, 'min'=>-0x8000000], // 32-bit integers
            [['createdOn', 'updatedOn', 'lastVisitOn'], 'date', 'format'=>array('yyyy-MM-dd', 'yyyy-MM-dd hh:mm', '?yyyy-MM-dd', '?yyyy-MM-dd hh:mm', '??yyyy-MM-dd', '??yyyy-MM-dd hh:mm')],
            [['emailVerified', 'isActive', 'isDisabled'], 'boolean'],
        ];
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'id'			=> Yii::t('manager', 'ID'),
            'username'		=> Yii::t('manager', 'Username'),
            'email'			=> Yii::t('manager', 'Email'),
            'firstName'		=> Yii::t('manager', 'Firstname'),
            'lastName'		=> Yii::t('manager', 'Lastname'),
            'createdOn'		=> Yii::t('manager', 'Created On'),
            'updatedOn'		=> Yii::t('manager', 'Updated On'),
            'lastVisitOn'	=> Yii::t('manager', 'Last Visit On'),
            'emailVerified'	=> Yii::t('manager', 'Email Verified'),
            'isActive'		=> Yii::t('manager', 'Is Active'),
            'isDisabled'	=> Yii::t('manager', 'Is Disabled'),
        ];
    }

    public function getIdentity($id=null)
    {
        if ($this->_identity===null) {
            $userIdentityClass = $this->userIdentityClass;
            $this->_identity = $userIdentityClass::find()->onCondition(['id'=>$id !== null ? $id : Yii::$app->user->getId()])->one();
            if ($this->_identity !== null && !($this->_identity instanceof ManagedIdentity)) {
                throw new Exception(Yii::t('usr','The {class} class must implement the {interface} interface.',['class'=>get_class($this->_identity),'interface'=>'ManagedIdentity']));
            }
        }

        return $this->_identity;
    }
}
