<?php

namespace App\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\User;
use GenTux\Jwt\JwtToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\UserToken;
use App\Services\UserService;
use Illuminate\Support\Facades\Session;
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
     */
    public function register(Request $request, User $userModel)
    {
        $rules = [
            'name'     => 'required',
            'email'    => 'required|email', //|unique:email
            'password' => 'required'
        ];

        $messages = [
            'name.required'  => 'Name empty',
            'email.required' => 'Email empty',
            'email.email'    => 'Email invalid',
            'password.required'    => 'Password empty'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ( ! $validator->passes()) {
            return $this->returnBadRequest($validator->errors());
        }

        $user = $userModel->register($request->name, $request->email, $request->password);

        if(!$user)
        {
            return $this->returnNotFound($user['error']);
        }
    }

    /**
     * Add user
     */
    public function addUser(Request $request)
    {
        $rules = [
            'name'     => 'required',
            'email'    => 'required|email',
            'password' => 'required'
        ];

        $messages = [
            'name.required'  => 'Name empty',
            'email.required' => 'Email empty',
            'email.email'    => 'Email invalid',
            'password.required' => 'Password empty'

        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ( ! $validator->passes()) {
            return $this->returnBadRequest();
        }

        $user = new User([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'status' => $request->input('status'),
            'role_id' => $request->input('role_id')
        ]);

        if($user->save()) {
            return $this->returnSuccess($user);
        }
        return $this->returnError('error');
    }

    /**
     * Edit user
     */
    public function editUser($id, Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ];

        $messages = [
            'name.required' => 'Name empty',
            'email.required' => 'Email empty',
            'email.email' => 'Email invalid',
            'password.required' => 'Password empty',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if (!$validator->passes()){
            return $this->returnBadRequest("Test passed");
        }

        $user = User::find($id);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->status = $request->input('status');
        $user->role_id = $request->input('role_id');

        if ($user->update()){
            return $this->returnSuccess($user);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        if($user->delete())
        {
            return $this->returnSuccess('User deleted');
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
                return $this->returnError('account not activated');
            }
            if ($user->updatedAt > Carbon::now()->subMinute()->format('Y-m-d H:i:s')) {
                return $this->returnError('resend cooldown');
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
                return $this->returnError('code invalid');
            }
            return $this->returnSuccess();
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

}