<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required', 'string', 'email',
            'token' => 'required', 'string',
            'password' => 'required', 'string', 'min:8', 'confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }

    /**
     * Forgot Password Send Email
     *
     * @param  Request  $request
     * @return Response
     */
    public function forgot_password(Request $request){
        if($request->email == ''){
            return response()->json(['status' => false,'msg' => [__('lang.t-email_must_required')]], 401);
        }
        if(User::where([['email', $request->email]])->count() != 1){
            return response()->json(['status' => false,'msg' => [__('lang.t-this_email_not_exists')]], 401);
        }else{
            $code = Helper::GetCode();
            User::where([['email', $request->email]])->update(array('remember_token' => $code));
            $user = User::where([['email', $request->email]])->first();
            $status = Helper::Send_mail(['code' => $code,"name" => $user->name],$request->email,__('lang.t-password_reset_title'),'mail.reset-password-code');
            if($status){
                return response()->json(['status' => true,'msg' => [__('lang.t-please_check_your_mailbox')]], 200);
            }else{
                return response()->json(['status' => false,'msg' => [__('lang.t-try_again')]], 401);
            }
        }
    }

    /**
     * Reset Password Verify
     *
     * @param  Request  $request
     * @return Response
     */
    public function reset_password_verify(Request $request){
        if(isset($request->code)){
            $user = User::where([['remember_token', $request->code]])->get();
            if(isset($user[0])){
                return response()->json(['status' => true], 200);
            }else{
                return response()->json(['status' => false,'msg' => [__('lang.t-this_code_is_not_valid')]], 401);
            }
        }else{
            return response()->json(['status' => false,'msg' => [__('lang.t-this_code_is_not_valid')]], 401);
        }
    }

    /**
     * Reset Password
     *
     * @param  Request  $request
     * @return Response
     */
    public function reset_password(Request $request){
        if(!$request->code){
            return abort('404');
        }
        $status = User::where([['remember_token', $request->code]])->get();
        if(isset($status[0])){
            $request->validate([
                'password' => 'required', 'string',
                'confirm_password' => 'required','same:password'
            ]);
            $user = User::find($status[0]->id);
            $user->password = Hash::make($request->password);
            $user->remember_token = null;
            if($user->save()){
                return response()->json(['status' => true, 'msg' => __('lang.t-password_reset_success')], 200);
            }else{
                return response()->json(['status' => false,'msg' => [__('lang.t-password_reset_fail')]], 401);
            }
        }else{
            return abort('404');
        }
    }

}
