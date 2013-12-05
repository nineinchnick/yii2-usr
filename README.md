Usr module
==========

Usr provides basic user actions like:

* logging in and out,
* password recovery and reset if expired
* registration with optional email verification,
* viewing and updating a minimal user profile along with changing password

It's goal is to be easier to integrate into current projects by not requiring to modify existing user database table and model.
Only the User class is used to provide all business logic by implementing few provided interfaces.

Key features:

* smaller codebase, easier to read/review
* use good password hashing
* no need to modify current tables and models
* built-in Hybridauth for logging using social site identities
* built-in Google Authenticator for two step authentication using one time passwords

Currently, there is no admin user managment provided and it is not planned. The reason for this is that the CRUDs vary much in every project and it should not be time-expensive to create another one for users utilizing interfaces implemented in User class for this module.
Actions provided by this module does not require any more authorization than checking if a user is logged in. An admin interface on the other hand requires to define auth items to check for access.

# Installation

Download and unpack in modules OR install via composer as nineinchnick/yii2-usr.

Enable the module in the config/web.php file:

~~~php
$config = [
    ......
	'aliases' => [
		'@nineinchnick/usr' => '@vendor/nineinchnick/yii2-usr',
	],
	'modules' => [
		'usr' => [
			'class' => 'nineinchnick\usr\Module',
		],
	],
]
~~~

Requirements for the identity (User) class are described in next chapter.

If your application is using path-format URLs with some customized URL rules, you may need to add
the following URLs in your application configuration in order to access UsrModule:

~~~php
	'components' => [
		'user' => [
			'identityClass' => 'app\models\User',
			'loginUrl' => ['usr/login'],
		],
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'rules' => [
			],
		],
	],
~~~

See Module.php file for full options reference.

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

A sample ExampleUser and ExampleUserUsedPassword models along with database migrations are provided respectively in the 'components', 'models' and 'migrations' folders.

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

