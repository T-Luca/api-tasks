<?php
namespace App\Services;
use Illuminate\Support\Facades\Mail;
use App\User;
/**
 * Class EmailService
 *
 * @package App\Services
 */
class EmailService
{
    /**
     * Send code on email for forgot password
     *
     * @param User $user
     */
    public function sendForgotPassword(User $user)
    {
        Mail::send('emails.forgot', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->subject('React start app - Forgot password code');
            $message->to($user->email);
        });
    }
    /**
     * Send code on email for account activation
     *
     * @param User $user
     */
    public function sendActivationCode(User $user)
    {
        Mail::send('emails.activation', ['user' => $user], function ($message) use ($user) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $message->subject('React start app - Activate account');
            $message->to($user->email);
        });
    }
}