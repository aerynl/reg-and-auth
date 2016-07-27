<?php namespace Aerynl\RegAuth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class RegAuth {

    public static function getUser() {
        return Auth::user();
    }

    public static function login($username, $password, $remember = false) {
        if (empty($username) || empty($password)) {
            return array('success' => false, 'message' => trans('regauth::messages.all_fields_are_required'));
        }

        $user_model = forward_static_call(array(config('auth.providers.users.model'), 'where'), 'username', $username);
        $user = $user_model->orWhere('email', $username)->first();

        if (!$user) return array('success' => false, 'message' => trans('regauth::messages.no_such_user'));
        if (!$user->activated) return array('success' => false, 'message' => trans('regauth::messages.user_not_activated'));

        // to skip mutations
        $user_attrs = $user->getAttributes();

        if (!Auth::attempt(['email' => $user_attrs['email'], 'password' => $password, 'activated' => 1], $remember == 1)) {
            return array('success' => false, 'message' => trans('regauth::messages.wrong_login_password'));
        }

        $user->last_login = date('Y-m-d H:i:s');
        $user->update();

        return array('success' => true);
    }

    public static function simpleLoginById($id) {
        if (empty($id)) {
            return array('success' => false, 'message' => trans('regauth::messages.all_fields_are_required'));
        }

        $user = forward_static_call(array(config('auth.model'), 'find'), $id);
        if (!$user) return array('success' => false, 'message' => trans('regauth::messages.no_such_user'));

        if (!$user->activated) return array('success' => false, 'message' => trans('regauth::messages.user_not_activated'));

        if (!Auth::loginUsingId($id)) {
            return array('success' => false, 'message' => trans('regauth::messages.wrong_login_password'));
        }

        return array('success' => true);
    }

    public static function logout() {
        Auth::logout();
    }

    public static function register($user_data) {
        if(empty($user_data) || !is_array($user_data) || empty($user_data['email']) || empty($user_data['password']) || empty($user_data['password_confirmation'] ))
            return array('success' => false, 'message' => trans('regauth::messages.error_occurred'));

        if($user_data['password_confirmation'] != $user_data['password']) return array('success' => false, 'message' => trans('regauth::messages.pass_and_confirm_match'));

        if(!empty($user_data['username'])) {
            $user_model = forward_static_call(array(config('auth.model'), 'where'), 'username', $user_data['username']);
            $existing_user = $user_model->orWhere('email', $user_data['email'])->first();
        } else {
            $user_model = forward_static_call(array(config('auth.model'), 'where'), 'email', $user_data['email']);
            $existing_user = $user_model->first();
        }

        if ($existing_user) return array('success' => false, 'message' => trans('regauth::messages.user_exists'));

        $validator = Validator::make($user_data, forward_static_call(array(config('auth.model'), 'rules')));

        if (!$validator->passes()) {
            return array('success' => false, 'message' => trans('regauth::messages.validation_errors_occurred'), 'error' => $validator->messages());
        }

        try {
            if(empty($user_data['activated'])) {
                $user_data['activated'] = 0;
            }
            $user_data['password'] = Hash::make($user_data['password']);
            if(isset($user_data['password_confirmation'])) unset($user_data['password_confirmation']);
            $user = forward_static_call(array(config('auth.model'), 'create'), $user_data);

            if(empty($user_data['activated'])) {
                $user->activation_code = str_random(16);
                $user->update();
            }

            return array('success' => true, 'user' => $user);
        }
        catch (\Exception $e) {
            Log::error($e->getMessage());
            return array('success' => false, 'message' => trans('regauth::messages.error_occurred'));
        }
    }

    public static function activate($hash) {
        if (empty($hash)) return Response::json(array('success' => false, 'message' => trans('regauth::messages.error_occurred')));
        $user_model = forward_static_call(array(config('auth.model'), 'where'), 'activation_code', $hash);
        $user = $user_model->where('activated', '=', '0')->first();
        if(!$user) return array('success' => false, 'message' => trans('regauth::messages.invalid_activation_code'));

        $user->activation_code = null;
        $user->activated = 1;
        $user->activated_at = date('Y-m-d H:i:s');
        $user->update();

        return array('success' => true, 'user' => $user);
    }

    public static function generateForgotPassHash($username) {
        if(empty($username)) return array('success' => false, 'message' => trans('regauth::messages.all_fields_are_required'));

        $user_model = forward_static_call(array(config('auth.model'), 'where'), 'username', $username);
        $user = $user_model->orWhere('email', $username)->first();
        if (!$user) return array('success' => false, 'message' => trans('regauth::messages.no_such_user'));

        $user->reset_password_code = str_random(16);
        $user->update();

        return array('success' => true, 'user' => $user);
    }

    public static function processForgotPassHash($hash) {
        if (empty($hash)) return Response::json(array('success' => false, 'message' => trans('regauth::messages.error_occurred')));

        $user_model = forward_static_call(array(config('auth.model'), 'where'), 'reset_password_code', $hash);
        $user = $user_model->first();
        if(!$user) return array('success' => false, 'message' => trans('regauth::messages.invalid_password_reset'));

        $user->reset_password_code = null;
        $new_pass = self::generatePass();
        $user->password = Hash::make($new_pass);
        $user->update();

        return array('success' => true, 'user' => $user, 'new_pass' => $new_pass);
    }

    public static function generatePass() {
        return rand(100000, 999999);
    }

    public static function changePass($pass_data, $user_id) {
        if (empty($user_id) || empty($pass_data)) return array('success' => false, 'message' => trans('regauth::messages.error_occurred'));

        $user = forward_static_call(array(config('auth.model'), 'find'), $user_id);
        if (empty($user)) return array('success' => false, 'message' => trans('regauth::messages.no_such_user'));

        if (!Hash::check($pass_data['old_password'], $user->password)) {
            return array('success' => false, 'message' => trans('regauth::messages.invalid_old_password'));
        }

        if(empty($pass_data['new_password']) || $pass_data['new_password'] != $pass_data['password_confirmation']) {
            return array('success' => false, 'message' => trans('regauth::messages.invalid_new_password'));
        }

        $user->password = Hash::make($pass_data['new_password']);
        $user->update();

        return array('success' => true);
    }

}