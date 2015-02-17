Usr module
==========

Usr provides basic user actions like:

* Logging in and out.
* Password recovery and reset if expired.
* Registration with optional email verification.
* Viewing and updating a minimal user profile along with changing password.
* Use good password hashing.
* User managment.

Advanced features:

* Captcha on registration and recovery forms.
* Passphrase generator to help users choose secure passwords.
* Easier to integrate into current projects by not requiring to modify existing user database table and model. Example models and migrations are provided.
* Support for Google Authenticator for two step authentication using one time passwords.
* Support for OAuth for logging using social site identities.

See [the demo](http://demo2.niix.pl).

# Installation

1. Install [Yii2](https://github.com/yiisoft/yii2/tree/master/apps/basic) using your preferred method
2. Install package via [composer](http://getcomposer.org/download/)
  * Run `php composer.phar require nineinchnick/yii2-usr "dev-master"` OR add to composer.json require section `"nineinchnick/yii2-usr": "dev-master"`
  * If one time passwords will be used, also install "sonata-project/google-authenticator"
  * If OAuth will be used, also install "yiisoft/yii2-authclient"
3. Update config file *config/web.php* as shown below. Note the _from_ key in messageConfig property of the mail component. Check out the Module for more available options.
4. Use provided example User model or implement required interfaces in existing User model. These are described in next chapter.


Example config (see Module.php file for full options reference):

~~~php
$config = [
    // .........
	'aliases' => [
		'@nineinchnick/usr' => '@vendor/nineinchnick/yii2-usr',
	],
	'modules' => [
		'usr' => [
			'class' => 'nineinchnick\usr\Module',
		],
	],
	'components' => [
		'user' => [
			'identityClass' => 'app\models\User',
			'loginUrl' => ['usr/login'],
		],
		'mail' => [
			'class' => 'yii\swiftmailer\Mailer',
			'useFileTransport' => YII_DEBUG,
			'messageConfig' => [
				'from' => 'noreply@yoursite.com',
			],
		],
		// ..........
	],
]
~~~


Requirements for the identity (User) class are described in next chapter.

# User interfaces 

To be able to use all features of the Usr module, the identity (User) class must implement some or all of the following interfaces.

## Editable

This interface allows to create new identities (register) and update existing ones.

## Active/disabled and email verification

This interface allows:

* finding existing identities using one of its attributes.
* generating and verifying an activation key used to verify email and send a recovery link

Remember to invalidate the email if it changes in the save() method from the Editable interface.

## Password history

This interface allows password reset with optional tracking of used passwords. This allows to detect expired passwords and avoid reusing old passwords by users.

See the ExpiredPasswordBehavior description below.

## OAuth

This interface allows finding local identity associated with a remote one (from an external social site) and creating such associations.

## One Time Password

This interface allow saving and retrieving a secret used to generate one time passwords. Also, last used password and counter used to generate last password are saved and retrieve to protect against reply attacks.

See the OneTimePasswordFormBehavior description below.

## Profile Pictures

Allows users to upload a profile picture. The example identity uses [Gravatar](http://gravatar.com/) to provide a default picture.

## Managable

Allows to manage users:

* update their profiles (and pictures)
* change passwords
* assign authorization roles
* activate/disable and mark email as verified
* see details as timestamps of account creation, last profile update and last visit

# Custom login behaviors

The login action can be extended by attaching custom behaviors to the LoginForm. This is done by configuring the UsrModule.loginFormBehaviors property.

There are two such behaviors provided by yii-usr module:

* ExpiredPasswordBehavior
* OneTimePasswordFormBehavior

### ExpiredPasswordBehavior

Validates if current password has expired and forces the users to change it before logging in.

Options:

* passwordTimeout - number of days after which user is requred to reset his password after logging in

### OneTimePasswordFormBehavior

Two step authentication using one time passwords.

Options:

* authenticator - if null, set to a new instance of GoogleAuthenticator class.
* mode - if set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords. Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android. Counter mode uses codes generated using a sequence and sends them to user's email.
* required - should the user be allowed to log in even if a secret hasn't been generated yet (is null). This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
* timeout - Number of seconds for how long is the last verified code valid.

## Example usage

~~~php
'loginFormBehaviors' => array(
    'expiredPasswordBehavior' => array(
        'class' => 'ExpiredPasswordBehavior',
        'passwordTimeout' => 10,
    ),
    'oneTimePasswordBehavior' => array(
        'class' => 'OneTimePasswordFormBehavior',
        'mode' => OneTimePasswordFormBehavior::OTP_TIME,
        'required' => true,
        'timeout' => 123,
    ),
    // ... other behaviors
),
~~~

# User model example

A sample ExampleUser and ExampleUserUsedPassword models along with database migrations are provided respectively in the 'models' and 'migrations' folders.

They could be used as-is by extending from or copying to be modified to better suit a project.

To use the provided migrations it's best to copy them to your migrations directory and adjust the filenames and classnames to current date and time. Also, they could be modified to remove not needed features.

# Diceware aka password generator

A simple implementation of a Diceware Passphrase generator is provided to aid users when they need to create a good, long but also easy to remember passphrase.

Install the `nineinchnick/diceware` composer package to use it.

Read more at [the Diceware Passphrase homepage](http://world.std.com/~reinhold/diceware.html).

# Usage scenarios

Varios scenarios can be created by enabling or disabling following features:

* registration
* email verification
* account activation

Implementing those scenarios require some logic outside the scope of this module.

## Public site

Users can register by themselves. Their accounts are activated instantly or after verifying email.

## Moderated site

Users can register, but to allow them to log in an administrator must activate their accounts manually, optionally assigning an authorization profile.
Email verification is optional and activation could trigger an email notification.

# License

MIT or BSD

