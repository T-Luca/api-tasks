<?php
/**
 * Created by PhpStorm.
 * User: iongh
 * Date: 8/1/2018
 * Time: 3:37 PM
 */

namespace App\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\User;
use GenTux\Jwt\JwtToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\UserToken;
use App\Services\UserService;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Login User
     *
     * @param Request $request
     * @param User $userModel
     * @param JwtToken $jwtToken
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \GenTux\Jwt\Exceptions\NoTokenException
     */
    public function login(Request $request, User $userModel, JwtToken $jwtToken)
    {
        $rules = [
            'email'    => 'required|email',
            'password' => 'required'
        ];

        $messages = [
            'email.required' => 'Email empty',
            'email.email'    => 'Email invalid',
            'password.required'    => 'Password empty'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ( ! $validator->passes()) {
            return $this->returnBadRequest();
        }

        $user = $userModel->login($request->email, $request->password);

        if ( ! $user) {
            return $this->returnNotFound('User sau parola gresite');
        }

        $token = $jwtToken->createToken($user);

        $data = [
            'user' => $user,
            'jwt'  => $token->token()
        ];

        return $this->returnSuccess($data);
    }

    /**
     * Register user
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:user',
            'password' => 'required|min:6',
            'retypePassword' => 'required_with:password|same:password'
        ];
        $messages = [
            'name.required' => 'name required',
            'email.required' => 'email required',
            'email.email' => 'email invalid',
            'email.unique' => 'email registered',
            'password.required' => 'password required',
            'password.min' => 'password min',
            'retypePassword.required_with' => 'retype password required',
            'retypePassword.same' => 'retype password same',
        ];



        $request->merge(['password' => Hash::make($request->get('password'))]);

        return $this->returnSuccess('User inregistrat');
    }

    /**
     * Return user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        try {
            $user = $this->validateSession();
            if (!$user) {
                return $this->returnError('error.token');
            }
            return $this->returnSuccess($user);
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
    /**
     * Forgot password, generate and send code
     *
     * @param Request $request
     * @param User $userModel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request, User $userModel)
    {
        if ($request->has('code')) {
            return $this->changePassword($request, $userModel);
        }
        try {
            /** @var UserService $userService */
            $userService = new UserService();
            $validator = $userService->validateForgotPassword($request);
            if (!$validator->passes()) {
                return $this->returnBadRequest($validator->messages());
            }
            $user = $userModel::where('email', $request->get('email'))->get()->first();
            if ($user->status === User::STATUS_UNCONFIRMED) {
                return $this->returnError('error.account_not_activated');
            }
            if ($user->updatedAt > Carbon::now()->subMinute()->format('Y-m-d H:i:s')) {
                return $this->returnError('error.resend_cooldown');
            }
            $userService->sendForgotCode($user);
            return $this->returnSuccess();
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }
    /**
     * Change user password
     *
     * @param Request $request
     * @param User $userModel
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function changePassword(Request $request, User $userModel)
    {
        try {
            /** @var UserService $userService */
            $userService = new UserService();
            $validator = $userService->validateChangePassword($request);
            if (!$validator->passes()) {
                return $this->returnBadRequest($validator->messages());
            }
            $request->merge(['password' => Hash::make($request->password)]);
            if (!$user = $userModel->changePassword($request->only('code', 'password'))) {
                return $this->returnError('error.code_invalid');
            }
            return $this->returnSuccess();
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

}