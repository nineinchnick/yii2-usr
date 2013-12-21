Usr module
==========

Usr provides basic user actions like:

* Logging in and out.
* Password recovery and reset if expired.
* Registration with optional email verification.
* Viewing and updating a minimal user profile along with changing password.
* Use good password hashing.

Advanced features:

* Captcha on registration and recovery forms.
* Passphrase generator to help users choose secure passwords.
* Easier to integrate into current projects by not requiring to modify existing user database table and model. Example models and migrations are provided.
* Support for Google Authenticator for two step authentication using one time passwords.
* Support for Hybridauth for logging using social site identities.

Currently, there is no admin user managment provided and it is not planned. The reason for this is that the CRUDs vary much in every project and it should not be time-expensive to create another one for users utilizing interfaces implemented in User class for this module.
Actions provided by this module does not require any more authorization than checking if a user is logged in. An admin interface on the other hand requires to define auth items to check for access.

See [the demo](http://demo2.niix.pl).

# Installation

1. Install [Yii2](https://github.com/yiisoft/yii2/tree/master/apps/basic) using your preferred method
2. Install package via [composer](http://getcomposer.org/download/)
  * Run `php composer.phar require nineinchnick/yii2-usr "dev-master"` OR add to composer.json require section `"nineinchnick/yii2-usr": "dev-master"`
  * If one time passwords will be used, also install "sonata-project/google-authenticator"
  * If Hybridauth will be used, also install "hybridauth/hybridauth"
3. Update config file *config/web.php* as shown below. Check out the Module for more available options.
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

## Hybridauth

This interface allows finding local identity associated with a remote one (from an external social site) and creating such associations.

## One Time Password

This interface allow saving and retrieving a secret used to generate one time passwords. Also, last used password and counter used to generate last password are saved and retrieve to protect against reply attacks.

# User model example

A sample ExampleUser and ExampleUserUsedPassword models along with database migrations are provided respectively in the 'models' and 'migrations' folders.

They could be used as-is by extending from or copying to be modified to better suit a project.

To use the provided migrations it's best to copy them to your migrations directory and adjust the filenames and classnames to current date and time. Also, they could be modified to remove not needed features.

# Diceware aka password generator

A simple implementation of a Diceware Passphrase generator is provided to aid users when they need to create a good, long but also easy to remember passphrase.

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

