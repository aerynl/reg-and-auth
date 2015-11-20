# reg-and-auth
Simple package for registering, activation, authentication and sending password reminders

## Installing
* `composer require aerynl/reg-and-auth "dev-master"`
* `php artisan vendor:publish --provider="Aerynl\RegAuth\RegAuthServiceProvider"`
* `php artisan migrate`

2 and 3 will update database fields according to package requirements. It will insert the following fields (if they are not already present): `username`, `activated`, `activation_code`, `reset_password_code`. Also if `activated` field is just created, it will set all the current users as activated.

## How to use

### Login

#### by username and password

`$result = Aerynl\RegAuth\RegAuth::login($username, $password, $remember);`

`$username` and `$password` are required parameters, `$remember` is not required.

This function will check `$username` and `$password` for non-emptiness, find user by username or email, check if user is activated and log him in if everything is ok. 

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true)` in case of success.

#### by id

`$result = Aerynl\RegAuth\RegAuth::simpleLoginById($id);`

This function will check `$id` for non-emptiness, find user by id, check if user is activated and log him in if everything is ok. 

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true)` in case of success.

### Get User

`$user = Aerynl\RegAuth\RegAuth::getUser();`

Fetches currently authenticated user

Returns null if there is no user in session.

### Logout

`Aerynl\RegAuth\RegAuth::logout();`

Logs out currently authenticated user

### Register

`$result = Aerynl\RegAuth\RegAuth::register($user_data);`

`$user_data` is array, which must have `email`, `password` and `password_confirmation`. It also may have another fields, which will be stored to `users` table.

This function will do the following:
* check `$user_data` for validity
* check if `password` and `password_confirmation` match
* check if there is no user with the same email or username
* register user
* generate activation_code

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true, 'user' => $user)` in case of success.

### Activate

`$result = Aerynl\RegAuth\RegAuth::activate($hash);`

`$hash` is a string, containing activation code.

This function search for not activated user with `activation_code = $hash` and activates him.

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true, 'user' => $user)` in case of success.

### Forgot password

`$result = Aerynl\RegAuth\RegAuth::generateForgotPassHash($username);`

This function will check `$username` for non-emptiness, find user by username or email and generate `reset_password_code`.

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true, 'user' => $user)` in case of success.

### Forgot password step 2

`$result = Aerynl\RegAuth\RegAuth::processForgotPassHash($hash);`

This function search for user with `reset_password_code = $hash` and generates a new password for him.

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true, 'user' => $user, 'new_pass' => $new_pass)` in case of success.

### Password generator

`$pass = Aerynl\RegAuth\RegAuth::generatePass();`

Generates simple 6-number password 

### Change password

`$result = Aerynl\RegAuth\RegAuth::changePass($pass_data, $user_id);`

`$pass_data` is array, which must have `old_password`, `new_password` and `password_confirmation`.

This function search for user with `id = $user_id`, checks if `$pass_data['old_password']` is correct, checks if  `$pass_data['new_password']` and `$pass_data['password_confirmation']` match and saves new password if everything is ok.

It will return `array('success' => false, 'message' => '...')` in case of fail and `array('success' => true)` in case of success.
