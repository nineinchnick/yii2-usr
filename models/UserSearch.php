<?php

/**
 * UserSearch class.
 * UserSearch is the data structure for keeping search form data used when fetching a data provider to display a list of identities.
 */
class UserSearch extends Model
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
            ['id, username, email, firstName, lastName, createdOn, updatedOn, lastVisitOn, emailVerified, isActive, isDisabled, anyText', 'trim'],
            ['id, username, email, firstName, lastName, createdOn, updatedOn, lastVisitOn, emailVerified, isActive, isDisabled, anyText', 'default'],
            ['id', 'numerical', 'integerOnly' => true, 'max' => 0x7FFFFFFF, 'min' => -0x8000000], // 32-bit integers
            ['createdOn, updatedOn, lastVisitOn', 'date', 'format' => ['yyyy-MM-dd', 'yyyy-MM-dd hh:mm', '?yyyy-MM-dd', '?yyyy-MM-dd hh:mm', '??yyyy-MM-dd', '??yyyy-MM-dd hh:mm']],
            ['emailVerified, isActive, isDisabled', 'boolean'],
        ];
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('UsrModule.manager', 'ID'),
            'username'        => Yii::t('UsrModule.manager', 'Username'),
            'email'            => Yii::t('UsrModule.manager', 'Email'),
            'firstName'        => Yii::t('UsrModule.manager', 'Firstname'),
            'lastName'        => Yii::t('UsrModule.manager', 'Lastname'),
            'createdOn'        => Yii::t('UsrModule.manager', 'Created On'),
            'updatedOn'        => Yii::t('UsrModule.manager', 'Updated On'),
            'lastVisitOn'    => Yii::t('UsrModule.manager', 'Last Visit On'),
            'emailVerified'    => Yii::t('UsrModule.manager', 'Email Verified'),
            'isActive'        => Yii::t('UsrModule.manager', 'Is Active'),
            'isDisabled'    => Yii::t('UsrModule.manager', 'Is Disabled'),
            'anyText'        => Yii::t('UsrModule.manager', 'Search'),
        ];
    }

    public function getIdentity($id = null)
    {
        if ($this->_identity === null) {
            $userIdentityClass = $this->userIdentityClass;
            $this->_identity = $userIdentityClass::find(['id' => $id !== null ? $id : Yii::app()->user->getId()]);
            if ($this->_identity !== null && !($this->_identity instanceof IManagedIdentity)) {
                throw new CException(Yii::t('UsrModule.usr', 'The {class} class must implement the {interface} interface.', ['{class}' => get_class($this->_identity), '{interface}' => 'IManagedIdentity']));
            }
        }

        return $this->_identity;
    }
}
