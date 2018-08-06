<?php
namespace App\Services;
use App\User;
use App\UserToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


/**
 * Class UserService
 *
 * @package App\Services
 */
class UserService
{

    /**
     * Validate request on forgot password
     *
     * @param Request $request
     *
     * @return Validator
     */
    public function validateForgotPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:user'
        ];
        $messages = [
            'email.required' => 'email required',
            'email.email' => 'email invalid',
            'email.exists' => 'email not registered',
        ];
        return Validator::make($request->all(), $rules, $messages);
    }
    /**
     * Send code on email for forgot password
     *
     * @param User $user
     */
    public function sendForgotCode(User $user)
    {
        $user->forgotPasswordCode = strtoupper(str_random(6));
        $user->generatedForgotPassword = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
        /** @var EmailService $emailService */
        $emailService = new EmailService();
        $emailService->sendForgotPassword($user);
        $user->updatedAt = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
    }
    /**
     * Validate request on forgot change password
     *
     * @param Request $request
     *
     * @return Validator
     */
    public function validateChangePassword(Request $request)
    {
        $rules = [
            'code' => 'required',
            'password' => 'required|min:6',
            'retypePassword' => 'required_with:password|same:password'
        ];
        $messages = [
            'code.required' => 'code required',
            'password.required' => 'password required',
            'password.min' => 'password min',
            'retypePassword.required_with' => 'retype password required',
            'retypePassword.same' => 'retype password same',
        ];
        return Validator::make($request->all(), $rules, $messages);
    }
}