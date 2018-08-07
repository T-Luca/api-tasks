<?php

namespace App;

use GenTux\Jwt\JwtPayloadInterface;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JwtPayloadInterface
{
    use Authenticatable, Authorizable;

    /** @var int */
    const STATUS_UNCONFIRMED = 0;
    /** @var int */

    const STATUS_CONFIRMED = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email','password','role_id', 'status'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function getPayload()
    {
        return [
            'id' => $this->id,
            'exp' => time() + 7200,
            'context' => [
                'email' => $this->email
            ]
        ];
    }

    public function role()
    {
        return $this->belongsTo('App/Role');
    }

    /**
     * Login user
     *
     * @param $userEmail
     * @param $userPassword
     *
     * @return bool
     */
    public function login($userEmail, $userPassword)
    {
        $user = $this->where([
            'email' => $userEmail,
        ])->get()->first();

        if (!$user) {
            return false;
        }

        $password = $user->password;

        if (app('hash')->check($userPassword, $password)) {
            return $user;
        }

        return false;
    }

    /**
     * Register User
     * @param $userName
     * @param $userEmail
     * @param $userPassword
     * @return bool
     */
    public function register($userName, $userEmail, $userPassword)
    {
        $user = $this->where([
            'email' => $userEmail
        ])->get()->first();

        if (!$user) {
            return false;
        }

        $user = $this->create([
            'name'     => $userName,
            'email'    => $userEmail,
            'password' => Hash::make($userPassword),
        ]);
        return $user;
    }

    /**
     * Change user password
     *
     * @param $userDetails
     *
     * @return User|bool
     */
    public function changePassword($userDetails)
    {
        /** @var User $user */
        $user = $this->where('forgot_password_code', $userDetails['code'])
            ->where('generated_forgot_password', '<', Carbon::now()->addHour()->format('Y-m-d H:i:s'))
            ->get()->first();
        if (!$user) {
            return false;
        }
        $user->forgotPasswordCode = '';
        $user->password = $userDetails['password'];
        $user->save();
        return $user;
    }
}
