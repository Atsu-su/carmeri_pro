[1mdiff --git a/src/app/Http/Controllers/HomeController.php b/src/app/Http/Controllers/HomeController.php[m
[1mindex 8d0d958..8f7bdfa 100644[m
[1m--- a/src/app/Http/Controllers/HomeController.php[m
[1m+++ b/src/app/Http/Controllers/HomeController.php[m
[36m@@ -29,7 +29,9 @@[m [mpublic function index()[m
                 ->orderBy('item_id', 'desc')[m
                 ->get();[m
 [m
[31m-            return view('index', compact('items', 'likedItems'));[m
[32m+[m[32m            $message = MessageSession::exists('message');[m
[32m+[m
[32m+[m[32m            return view('index', compact('items', 'likedItems', 'message'));[m
         } else {[m
             $items = Item::orderBy('id', 'desc')->get();[m
             return view('index', compact('items'));[m
[1mdiff --git a/src/app/Http/Controllers/UserController.php b/src/app/Http/Controllers/UserController.php[m
[1mindex c2af2e8..0df77b6 100644[m
[1m--- a/src/app/Http/Controllers/UserController.php[m
[1m+++ b/src/app/Http/Controllers/UserController.php[m
[36m@@ -2,16 +2,23 @@[m
 [m
 namespace App\Http\Controllers;[m
 [m
[32m+[m[32m// use App\Auth\Events\Registered;[m
 use App\Messages\Message;[m
 use App\Messages\Session as MessageSession;[m
 use App\Models\Comment;[m
 use App\Models\Item;[m
 use App\Models\Like;[m
 use App\Models\User;[m
[32m+[m[32muse App\Http\Requests\ActivateUserRequest;[m
[32m+[m[32muse App\Http\Requests\PasswordRequest;[m
 use App\Traits\DeleteItem;[m
 use Exception;[m
[32m+[m[32muse Illuminate\Auth\Events\Registered;[m
[32m+[m[32muse Illuminate\Http\Request;[m
[32m+[m[32muse Laravel\Fortify\Contracts\RegisterResponse;[m
 use Illuminate\Support\Facades\Auth;[m
 use Illuminate\Support\Facades\DB;[m
[32m+[m[32muse Illuminate\Support\Facades\Hash;[m
 use Illuminate\Support\Facades\Log;[m
 use Illuminate\Support\Facades\Storage;[m
 [m
[36m@@ -27,7 +34,10 @@[m [mpublic function deactivateUser()[m
             DB::beginTransaction();[m
 [m
             // ユーザを無効化[m
[31m-            $user->update(['is_active' => 0]);[m
[32m+[m[32m            $user->update([[m
[32m+[m[32m                'is_active' => 0,[m
[32m+[m[32m                'email_verified_at' => null,[m
[32m+[m[32m            ]);[m
 [m
             // 出品商品（未購入）の全削除[m
             // カテゴリー、コメント、いいねも同時に削除される[m
[36m@@ -69,4 +79,51 @@[m [mpublic function deactivateUser()[m
                 ->with('message', Message::get('user.deactivate.failed'));[m
         }[m
     }[m
[32m+[m
[32m+[m[32m    public function inputEmail()[m
[32m+[m[32m    {[m
[32m+[m[32m        request()->offsetUnset('headerType');[m
[32m+[m[32m        return view('auth.input_email');[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    public function activateUser([m
[32m+[m[32m            Request $request,[m
[32m+[m[32m            ActivateUserRequest $activateUserRequest[m
[32m+[m[32m        )[m
[32m+[m[32m    {[m
[32m+[m[32m        $request->merge(['activate' => true]);[m
[32m+[m[32m        $user = User::where('email', $activateUserRequest->input('email'))[m
[32m+[m[32m            ->where('is_active', 0)[m
[32m+[m[32m            ->first();[m
[32m+[m
[32m+[m[32m        event(new Registered($user));[m
[32m+[m
[32m+[m[32m        $user->update(['is_active' => 1]);[m
[32m+[m[32m        Auth::guard('web')->login($user);[m
[32m+[m
[32m+[m[32m        return app(RegisterResponse::class);[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    public function editPassword()[m
[32m+[m[32m    {[m
[32m+[m[32m        request()->offsetUnset('headerType');[m
[32m+[m[32m        return view('auth.input_password');[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    public function updatePassword(PasswordRequest $request)[m
[32m+[m[32m    {[m
[32m+[m[32m        $user = auth()->user();[m
[32m+[m[32m        $password = Hash::make($request->input('password'));[m
[32m+[m[32m        try {[m
[32m+[m[32m            $user->fill(['password' => $password])->save();[m
[32m+[m[32m            return redirect()[m
[32m+[m[32m                ->route('register.profile.edit')[m
[32m+[m[32m                ->with('message', Message::get('profile.password.updated.success'));[m
[32m+[m[32m        } catch (Exception $e) {[m
[32m+[m[32m            Log::error($e->getMessage());[m
[32m+[m[32m            return redirect()[m
[32m+[m[32m                ->route('index')[m
[32m+[m[32m                ->with('message', Message::get('profile.password.updated.failed'));[m
[32m+[m[32m        }[m
[32m+[m[32m    }[m
 }[m
[1mdiff --git a/src/app/Http/Requests/ActivateUserRequest.php b/src/app/Http/Requests/ActivateUserRequest.php[m
[1mnew file mode 100644[m
[1mindex 0000000..618ca82[m
[1m--- /dev/null[m
[1m+++ b/src/app/Http/Requests/ActivateUserRequest.php[m
[36m@@ -0,0 +1,47 @@[m
[32m+[m[32m<?php[m
[32m+[m
[32m+[m[32mnamespace App\Http\Requests;[m
[32m+[m
[32m+[m[32muse App\Rules\CheckExistence;[m
[32m+[m[32muse Illuminate\Foundation\Http\FormRequest;[m
[32m+[m[32muse Laravel\Fortify\Fortify;[m
[32m+[m[32muse Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;[m
[32m+[m
[32m+[m[32mclass ActivateUserRequest extends FormRequest[m
[32m+[m[32m{[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Determine if the user is authorized to make this request.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return bool[m
[32m+[m[32m     */[m
[32m+[m[32m    public function authorize()[m
[32m+[m[32m    {[m
[32m+[m[32m        return true;[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Get the validation rules that apply to the request.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return array[m
[32m+[m[32m     */[m
[32m+[m[32m    public function rules()[m
[32m+[m[32m    {[m
[32m+[m[32m        return [[m
[32m+[m[32m            'email' => [[m
[32m+[m[32m                'required',[m
[32m+[m[32m                'email',[m
[32m+[m[32m                'exists:users,email',[m
[32m+[m[32m                new CheckExistence(),[m
[32m+[m[32m            ],[m
[32m+[m[32m        ];[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    public function messages()[m
[32m+[m[32m    {[m
[32m+[m[32m        return [[m
[32m+[m[32m            'email.required' => 'メールアドレスを入力してください',[m
[32m+[m[32m            'email.email' => 'メールアドレスの形式で入力してください',[m
[32m+[m[32m            'email.exists' => 'このメールアドレスは登録されていません',[m
[32m+[m[32m        ];[m
[32m+[m[32m    }[m
[32m+[m[32m}[m
[1mdiff --git a/src/app/Http/Requests/PasswordRequest.php b/src/app/Http/Requests/PasswordRequest.php[m
[1mnew file mode 100644[m
[1mindex 0000000..3a0ca38[m
[1m--- /dev/null[m
[1m+++ b/src/app/Http/Requests/PasswordRequest.php[m
[36m@@ -0,0 +1,41 @@[m
[32m+[m[32m<?php[m
[32m+[m
[32m+[m[32mnamespace App\Http\Requests;[m
[32m+[m
[32m+[m[32muse Illuminate\Foundation\Http\FormRequest;[m
[32m+[m
[32m+[m[32mclass PasswordRequest extends FormRequest[m
[32m+[m[32m{[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Determine if the user is authorized to make this request.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return bool[m
[32m+[m[32m     */[m
[32m+[m[32m    public function authorize()[m
[32m+[m[32m    {[m
[32m+[m[32m        return true;[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Get the validation rules that apply to the request.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return array[m
[32m+[m[32m     */[m
[32m+[m[32m    public function rules()[m
[32m+[m[32m    {[m
[32m+[m[32m        return [[m
[32m+[m[32m            'password' => 'required|string|min:8',[m
[32m+[m[32m            'confirm_password' => 'required|same:password',[m
[32m+[m[32m        ];[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    public function messages()[m
[32m+[m[32m    {[m
[32m+[m[32m        return [[m
[32m+[m[32m            'password.required' => 'パスワードを入力してください',[m
[32m+[m[32m            'password.min' => 'パスワードは8文字以上で入力してください',[m
[32m+[m[32m            'confirm_password.required' => '確認用パスワードを入力してください',[m
[32m+[m[32m            'confirm_password.same' => 'パスワードと一致しません'[m
[32m+[m[32m        ];[m
[32m+[m[32m    }[m
[32m+[m[32m}[m
[1mdiff --git a/src/app/Http/Responses/CustomVerifyEmailResponse.php b/src/app/Http/Responses/CustomVerifyEmailResponse.php[m
[1mnew file mode 100644[m
[1mindex 0000000..bdca80e[m
[1m--- /dev/null[m
[1m+++ b/src/app/Http/Responses/CustomVerifyEmailResponse.php[m
[36m@@ -0,0 +1,29 @@[m
[32m+[m[32m<?php[m
[32m+[m
[32m+[m[32mnamespace App\Http\Responses;[m
[32m+[m
[32m+[m[32muse Illuminate\Http\JsonResponse;[m
[32m+[m[32muse Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;[m
[32m+[m[32muse Laravel\Fortify\Fortify;[m
[32m+[m
[32m+[m[32mclass CustomVerifyEmailResponse implements VerifyEmailResponseContract[m
[32m+[m[32m{[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Create an HTTP response that represents the object.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @param  \Illuminate\Http\Request  $request[m
[32m+[m[32m     * @return \Symfony\Component\HttpFoundation\Response[m
[32m+[m[32m     */[m
[32m+[m[32m    public function toResponse($request)[m
[32m+[m[32m    {[m
[32m+[m[32m        if ($request->query('activate')) {[m
[32m+[m[32m            return $request->wantsJson()[m
[32m+[m[32m            ? new JsonResponse('', 204)[m
[32m+[m[32m            : redirect()->intended(Fortify::redirects('activated').'?verified=1');[m
[32m+[m[32m        }[m
[32m+[m
[32m+[m[32m        return $request->wantsJson()[m
[32m+[m[32m            ? new JsonResponse('', 204)[m
[32m+[m[32m            : redirect()->intended(Fortify::redirects('verified').'?verified=1');[m
[32m+[m[32m    }[m
[32m+[m[32m}[m
[1mdiff --git a/src/app/Messages/Message.php b/src/app/Messages/Message.php[m
[1mindex e093ccb..f30bd38 100644[m
[1m--- a/src/app/Messages/Message.php[m
[1m+++ b/src/app/Messages/Message.php[m
[36m@@ -119,6 +119,23 @@[m [mclass Message[m
                     'お手数ですが、しばらく時間をおいて再度お試しください'[m
                 ],[m
             ],[m
[32m+[m[32m            'password' => [[m
[32m+[m[32m                'updated' => [[m
[32m+[m[32m                    'success' => [[m
[32m+[m[32m                        'status' => self::SUCCESS,[m
[32m+[m[32m                        'title' => 'パスワード変更完了',[m
[32m+[m[32m                        'contents' => ['パスワードを変更しました'],[m
[32m+[m[32m                    ],[m
[32m+[m[32m                    'failed' => [[m
[32m+[m[32m                        'status' => self::ERROR,[m
[32m+[m[32m                        'title' => 'パスワードの変更に失敗しました',[m
[32m+[m[32m                        'contents' => [[m
[32m+[m[32m                            '申し訳ございません',[m
[32m+[m[32m                            'お手数ですが、しばらく時間をおいて再度お試しください'[m
[32m+[m[32m                        ],[m
[32m+[m[32m                    ][m
[32m+[m[32m                ][m
[32m+[m[32m            ][m
         ],[m
 [m
         'list' => [[m
[1mdiff --git a/src/app/Models/User.php b/src/app/Models/User.php[m
[1mindex 72feed3..0b16cc0 100644[m
[1m--- a/src/app/Models/User.php[m
[1m+++ b/src/app/Models/User.php[m
[36m@@ -3,6 +3,7 @@[m
 namespace App\Models;[m
 [m
 use App\Notifications\CustomVerifyEmail;[m
[32m+[m[32muse App\Notifications\ActivateCustomVerifyEmail;[m
 use Illuminate\Contracts\Auth\MustVerifyEmail;[m
 use Illuminate\Database\Eloquent\Factories\HasFactory;[m
 use Illuminate\Foundation\Auth\User as Authenticatable;[m
[1mdiff --git a/src/app/Notifications/ActivateCustomVerifyEmail.php b/src/app/Notifications/ActivateCustomVerifyEmail.php[m
[1mnew file mode 100644[m
[1mindex 0000000..caa061e[m
[1m--- /dev/null[m
[1m+++ b/src/app/Notifications/ActivateCustomVerifyEmail.php[m
[36m@@ -0,0 +1,29 @@[m
[32m+[m[32m<?php[m
[32m+[m
[32m+[m[32mnamespace App\Notifications;[m
[32m+[m
[32m+[m[32muse Illuminate\Auth\Notifications\VerifyEmail;[m
[32m+[m[32muse Illuminate\Notifications\Messages\MailMessage;[m
[32m+[m
[32m+[m[32mclass ActivateCustomVerifyEmail extends VerifyEmail[m
[32m+[m[32m{[m
[32m+[m[32m    private $user;[m
[32m+[m
[32m+[m[32m    public function __construct($user)[m
[32m+[m[32m    {[m
[32m+[m[32m        $this->user = $user;[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    protected function buildMailMessage($url)[m
[32m+[m[32m    {[m
[32m+[m[32m        return (new MailMessage)[m
[32m+[m[32m            ->subject('CoachTech メールアドレス確認')[m
[32m+[m[32m            ->greeting('おかえりなさい！'.$this->user->name. ' さん')[m
[32m+[m[32m            ->line('以下のボタンをクリックしてメールアドレスを確認してください。')[m
[32m+[m[32m            ->line('ボタンを押すと自動的にログインします。')[m
[32m+[m[32m            ->action('メールアドレスを確認', $url)[m
[32m+[m[32m            ->line('もし心当たりがない場合は、このメールを破棄してください。')[m
[32m+[m[32m            ->salutation('ご確認よろしくお願いします。');[m
[32m+[m
[32m+[m[32m    }[m
[32m+[m[32m}[m
\ No newline at end of file[m
[1mdiff --git a/src/app/Notifications/CustomVerifyEmail.php b/src/app/Notifications/CustomVerifyEmail.php[m
[1mindex 371b0de..f55efcb 100644[m
[1m--- a/src/app/Notifications/CustomVerifyEmail.php[m
[1m+++ b/src/app/Notifications/CustomVerifyEmail.php[m
[36m@@ -2,8 +2,12 @@[m
 [m
 namespace App\Notifications;[m
 [m
[32m+[m[32muse GuzzleHttp\Psr7\Request;[m
 use Illuminate\Auth\Notifications\VerifyEmail;[m
 use Illuminate\Notifications\Messages\MailMessage;[m
[32m+[m[32muse Illuminate\Support\Carbon;[m
[32m+[m[32muse Illuminate\Support\Facades\Config;[m
[32m+[m[32muse Illuminate\Support\Facades\URL;[m
 [m
 class CustomVerifyEmail extends VerifyEmail[m
 {[m
[36m@@ -16,14 +20,34 @@[m [mpublic function __construct($user)[m
 [m
     protected function buildMailMessage($url)[m
     {[m
[32m+[m[32m        $greeting = request()->input('activate') ?[m
[32m+[m[32m            'おかえりなさい！ '.$this->user->name.' さん' :[m
[32m+[m[32m            $this->user->name . ' さん';[m
[32m+[m
         return (new MailMessage)[m
             ->subject('CoachTech メールアドレス確認')[m
[31m-            ->greeting($this->user->name . ' さん')[m
[32m+[m[32m            ->greeting($greeting)[m
             ->line('以下のボタンをクリックしてメールアドレスを確認してください。')[m
             ->line('ボタンを押すと自動的にログインします。')[m
             ->action('メールアドレスを確認', $url)[m
             ->line('もし心当たりがない場合は、このメールを破棄してください。')[m
             ->salutation('ご確認よろしくお願いします。');[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    protected function verificationUrl($notifiable)[m
[32m+[m[32m    {[m
[32m+[m[32m        if (static::$createUrlCallback) {[m
[32m+[m[32m            return call_user_func(static::$createUrlCallback, $notifiable);[m
[32m+[m[32m        }[m
 [m
[32m+[m[32m        return URL::temporarySignedRoute([m
[32m+[m[32m            'verification.verify',[m
[32m+[m[32m            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),[m
[32m+[m[32m            [[m
[32m+[m[32m                'id' => $notifiable->getKey(),[m
[32m+[m[32m                'hash' => sha1($notifiable->getEmailForVerification()),[m
[32m+[m[32m                'activate' => request()->input('activate') ? true : false,[m
[32m+[m[32m            ][m
[32m+[m[32m        );[m
     }[m
 }[m
\ No newline at end of file[m
[1mdiff --git a/src/app/Providers/FortifyServiceProvider.php b/src/app/Providers/FortifyServiceProvider.php[m
[1mindex 93a51cd..cc2c7fe 100644[m
[1m--- a/src/app/Providers/FortifyServiceProvider.php[m
[1m+++ b/src/app/Providers/FortifyServiceProvider.php[m
[36m@@ -6,6 +6,7 @@[m
 use App\Actions\Fortify\ResetUserPassword;[m
 use App\Actions\Fortify\UpdateUserPassword;[m
 use App\Actions\Fortify\UpdateUserProfileInformation;[m
[32m+[m[32muse App\Http\Responses\CustomVerifyEmailResponse;[m
 use App\Http\Requests\LoginRequest;[m
 use App\Http\Responses\LogoutResponse;[m
 use App\Models\User;[m
[36m@@ -17,7 +18,7 @@[m
 use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;[m
 use Laravel\Fortify\Fortify;[m
 use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;[m
[31m-[m
[32m+[m[32muse Laravel\Fortify\Http\Responses\VerifyEmailResponse;[m
 [m
 class FortifyServiceProvider extends ServiceProvider[m
 {[m
[36m@@ -27,6 +28,7 @@[m [mclass FortifyServiceProvider extends ServiceProvider[m
     public function register(): void[m
     {[m
         app()->bind(FortifyLoginRequest::class, LoginRequest::class);[m
[32m+[m[32m        app()->bind(VerifyEmailResponse::class, CustomVerifyEmailResponse::class);[m
     }[m
 [m
     /**[m
[1mdiff --git a/src/app/Rules/CheckExistence.php b/src/app/Rules/CheckExistence.php[m
[1mnew file mode 100644[m
[1mindex 0000000..a86451a[m
[1m--- /dev/null[m
[1m+++ b/src/app/Rules/CheckExistence.php[m
[36m@@ -0,0 +1,43 @@[m
[32m+[m[32m<?php[m
[32m+[m
[32m+[m[32mnamespace App\Rules;[m
[32m+[m
[32m+[m[32muse App\Models\User;[m
[32m+[m[32muse Illuminate\Contracts\Validation\Rule;[m
[32m+[m
[32m+[m[32mclass CheckExistence implements Rule[m
[32m+[m[32m{[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Create a new rule instance.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return void[m
[32m+[m[32m     */[m
[32m+[m[32m    public function __construct()[m
[32m+[m[32m    {[m
[32m+[m[32m        //[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Determine if the validation rule passes.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @param  string  $attribute[m
[32m+[m[32m     * @param  mixed  $value[m
[32m+[m[32m     * @return bool[m
[32m+[m[32m     */[m
[32m+[m[32m    public function passes($attribute, $value)[m
[32m+[m[32m    {[m
[32m+[m[32m        return User::where('email', $value)[m
[32m+[m[32m            ->where('is_active', false)[m
[32m+[m[32m            ->first() ? true : false;[m
[32m+[m[32m    }[m
[32m+[m
[32m+[m[32m    /**[m
[32m+[m[32m     * Get the validation error message.[m
[32m+[m[32m     *[m
[32m+[m[32m     * @return string[m
[32m+[m[32m     */[m
[32m+[m[32m    public function message()[m
[32m+[m[32m    {[m
[32m+[m[32m        return 'このユーザは有効です';[m
[32m+[m[32m    }[m
[32m+[m[32m}[m
[1mdiff --git a/src/config/fortify.php b/src/config/fortify.php[m
[1mindex 5aa686d..a05a18a 100644[m
[1m--- a/src/config/fortify.php[m
[1m+++ b/src/config/fortify.php[m
[36m@@ -160,9 +160,12 @@[m
     'redirects' => [[m
         'login' => RouteServiceProvider::HOME,[m
         'logout' => RouteServiceProvider::HOME,[m
[31m-        'register' => function() {[m
[31m-            return route('register.profile.edit');[m
[31m-        },[m
[32m+[m[32m        // 'register' => function() {[m
[32m+[m[32m        //     return route('register.profile.edit');[m
[32m+[m[32m        // },[m
[32m+[m[32m        'register' => 'email/verify',[m
[32m+[m[32m        'verified' => 'register/profile',[m
[32m+[m[32m        'activated' => 'activate/profile/password',[m
     ][m
 [m
 ];[m
[1mdiff --git a/src/public/css/style.css b/src/public/css/style.css[m
[1mindex edc4a29..8c54c61 100644[m
[1m--- a/src/public/css/style.css[m
[1m+++ b/src/public/css/style.css[m
[36m@@ -540,6 +540,10 @@[m [mhtml {[m
   text-align: center;[m
 }[m
 [m
[32m+[m[32m.c-default-form .login-link:hover {[m
[32m+[m[32m  opacity: 0.6;[m
[32m+[m[32m}[m
[32m+[m
 /* ---------------------- */[m
 /* 商品一覧[m
 /* ---------------------- */[m
[36m@@ -1517,6 +1521,8 @@[m [mbody {[m
   text-align: center;[m
 }[m
 [m
[32m+[m
[32m+[m
 #item-input .form-name-category {[m
   margin-bottom: 31px;[m
 }[m
[36m@@ -1805,4 +1811,22 @@[m [mbody {[m
 [m
 #thanks a {[m
   color: var(--color-font-blue);[m
[32m+[m[32m}[m
[32m+[m
[32m+[m[32m/* ----------------------- */[m
[32m+[m[32m/* login.blade.php[m
[32m+[m[32m/* 商品購入画面（#thanks）[m
[32m+[m[32m/* ----------------------- */[m
[32m+[m[32m#login .links {[m
[32m+[m[32m  display: flex;[m
[32m+[m[32m  flex-direction: column;[m
[32m+[m[32m  justify-content: center;[m
[32m+[m[32m  align-items: center;[m
[32m+[m[32m  gap: 30px;[m
[32m+[m[32m  color: var(--color-font-blue);[m
[32m+[m[32m}[m
[32m+[m
[32m+[m[32m#login .links-register:hover,[m
[32m+[m[32m#login .links-activate:hover {[m
[32m+[m[32m  opacity: 0.6;[m
 }[m
\ No newline at end of file[m
[1mdiff --git a/src/resources/views/auth/input_email.blade.php b/src/resources/views/auth/input_email.blade.php[m
[1mnew file mode 100644[m
[1mindex 0000000..99801d6[m
[1m--- /dev/null[m
[1m+++ b/src/resources/views/auth/input_email.blade.php[m
[36m@@ -0,0 +1,19 @@[m
[32m+[m[32m@extends('layouts.base')[m
[32m+[m[32m@section('title', 'アカウント有効化')[m
[32m+[m[32m@section('header')[m
[32m+[m[32m  @include('components.header')[m
[32m+[m[32m@endsection[m
[32m+[m[32m@section('content')[m
[32m+[m[32m  <div class="c-default-form" id="register">[m
[32m+[m[32m    <h1 class="title">アカウントの有効化</h1>[m
[32m+[m[32m    <form class="form" action="{{ route('activate') }}" method="POST">[m
[32m+[m[32m      @csrf[m
[32m+[m[32m      <label class="form-title">メールアドレスを入力してください</label>[m
[32m+[m[32m      <input class="form-input" type="text" name="email" value="{{ old('email') }}">[m
[32m+[m[32m      @error('email')[m
[32m+[m[32m        <p class="c-error-message">{{ $message }}</p>[m
[32m+[m[32m      @enderror[m
[32m+[m[32m      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>[m
[32m+[m[32m    </form>[m
[32m+[m[32m  </div>[m
[32m+[m[32m@endsection[m
\ No newline at end of file[m
[1mdiff --git a/src/resources/views/auth/input_password.blade.php b/src/resources/views/auth/input_password.blade.php[m
[1mnew file mode 100644[m
[1mindex 0000000..a68bbda[m
[1m--- /dev/null[m
[1m+++ b/src/resources/views/auth/input_password.blade.php[m
[36m@@ -0,0 +1,25 @@[m
[32m+[m[32m@extends('layouts.base')[m
[32m+[m[32m@section('title', 'アカウント有効化')[m
[32m+[m[32m@section('header')[m
[32m+[m[32m  @include('components.header')[m
[32m+[m[32m@endsection[m
[32m+[m[32m@section('content')[m
[32m+[m[32m  <div class="c-default-form" id="register">[m
[32m+[m[32m    <h1 class="title">パスワード変更</h1>[m
[32m+[m[32m    <form class="form" action="{{ route('activate.profile.update') }}" method="POST">[m
[32m+[m[32m      @csrf[m
[32m+[m[32m      @method('PUT')[m
[32m+[m[32m      <label class="form-title">パスワード</label>[m
[32m+[m[32m      <input class="form-input" type="password" name="password">[m
[32m+[m[32m      @error('password')[m
[32m+[m[32m        <p class="c-error-message">{{ $message }}</p>[m
[32m+[m[32m      @enderror[m
[32m+[m[32m      <label class="form-title">確認用パスワード</label>[m
[32m+[m[32m      <input class="form-input" type="password" name="confirm_password">[m
[32m+[m[32m      @error('confirm_password')[m
[32m+[m[32m        <p class="c-error-message">{{ $message }}</p>[m
[32m+[m[32m      @enderror[m
[32m+[m[32m      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>[m
[32m+[m[32m    </form>[m
[32m+[m[32m  </div>[m
[32m+[m[32m@endsection[m
\ No newline at end of file[m
[1mdiff --git a/src/resources/views/auth/login.blade.php b/src/resources/views/auth/login.blade.php[m
[1mindex e2734b1..9d6a886 100644[m
[1m--- a/src/resources/views/auth/login.blade.php[m
[1m+++ b/src/resources/views/auth/login.blade.php[m
[36m@@ -20,6 +20,9 @@[m
       @enderror[m
       <button class="form-btn c-btn c-btn--red" type="submit">ログインする</button>[m
     </form>[m
[31m-    <a class="login-link u-opacity-08" href="{{ route('register') }}">会員登録はこちら</a>[m
[32m+[m[32m    <div class="links">[m
[32m+[m[32m      <a class="links-register" href="{{ route('register') }}">会員登録はこちら</a>[m
[32m+[m[32m      <a class="links-activate" href="{{ route('activate.index') }}">アカウントの有効化はこちら</a>[m
[32m+[m[32m    </div>[m
   </div>[m
 @endsection[m
\ No newline at end of file[m
[1mdiff --git a/src/resources/views/index.blade.php b/src/resources/views/index.blade.php[m
[1mindex 624c196..6e2d9ec 100644[m
[1m--- a/src/resources/views/index.blade.php[m
[1m+++ b/src/resources/views/index.blade.php[m
[36m@@ -1,5 +1,8 @@[m
 @extends('layouts.base')[m
 @section('title', 'Carmeri')[m
[32m+[m[32m@section('modal')[m
[32m+[m[32m  @include('components.modal')[m
[32m+[m[32m@endsection[m
 @section('header')[m
   @include('components.header')[m
 @endsection[m
[1mdiff --git a/src/resources/views/prototype/test.blade.php b/src/resources/views/prototype/test.blade.php[m
[1mnew file mode 100644[m
[1mindex 0000000..61771b5[m
[1m--- /dev/null[m
[1m+++ b/src/resources/views/prototype/test.blade.php[m
[36m@@ -0,0 +1,12 @@[m
[32m+[m[32m<html>[m
[32m+[m[32m<head>[m
[32m+[m[32m<title>Hello</title>[m
[32m+[m[32m</head>[m
[32m+[m[32m<body>[m
[32m+[m[32m    <form action="/test/activate" method="POST">[m
[32m+[m[32m        @csrf[m
[32m+[m[32m        <input type="text" name="email" value="">[m
[32m+[m[32m        <input type="submit" value="Submit">[m
[32m+[m[32m    </form>[m
[32m+[m[32m</body>[m
[32m+[m[32m</html>[m
\ No newline at end of file[m
[1mdiff --git a/src/resources/views/register_profile_input.blade.php b/src/resources/views/register_profile_input.blade.php[m
[1mindex 895f6a3..1c120df 100644[m
[1m--- a/src/resources/views/register_profile_input.blade.php[m
[1m+++ b/src/resources/views/register_profile_input.blade.php[m
[36m@@ -1,5 +1,8 @@[m
 @extends('layouts.base')[m
 @section('title', 'プロフィール入力')[m
[32m+[m[32m@section('modal')[m
[32m+[m[32m  @include('components.modal')[m
[32m+[m[32m@endsection[m
 @section('header')[m
   @include('components.header')[m
 @endsection[m
[1mdiff --git a/src/routes/web.php b/src/routes/web.php[m
[1mindex c8d454c..9bec206 100644[m
[1m--- a/src/routes/web.php[m
[1m+++ b/src/routes/web.php[m
[36m@@ -22,44 +22,39 @@[m
 |[m
 */[m
 [m
[31m-// Route::get('/test/mail', function () {[m
[31m-//     $user = \App\Models\User::find(1);[m
[31m-//     $user->sendEmailVerificationNotification();[m
[31m-//     return 'メールを送信しました';[m
[31m-// });[m
[31m-[m
 Route::middleware('header')->group(function () {[m
     Route::get('/', [HomeController::class, 'index'])->name('index');[m
     Route::post('/', [HomeController::class, 'search'])->name('index.search');[m
     Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('item.show');[m
[32m+[m[32m    Route::get('activate', [UserController::class, 'inputEmail'])->name('activate.index');[m
[32m+[m[32m    Route::post('activate', [UserController::class, 'activateUser'])->name('activate');[m
 [m
     Route::middleware(['auth', 'verified'])->group(function () {[m
[31m-        Route::get('/mypage', [HomeController::class, 'myPageIndex'])->name('mypage');[m
[31m-        Route::get('/register/profile', [ProfileController::class, 'edit'])->name('register.profile.edit');[m
[31m-        Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');[m
[31m-        Route::post('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');[m
[31m-        Route::post('/item/{item_id}/like', [LikeController::class, 'toggleLike'])->name('like');[m
[31m-        Route::post('/item/{item_id}/comment', [CommentController::class, 'store'])->name('comment.store');[m
[31m-        Route::post('/item/{item_id}/comment/update/{comment_id}', [CommentController::class, 'update'])->name('comment.update');[m
[31m-        Route::post('/item/{item_id}/comment/delete/{comment_id}', [CommentController::class, 'delete'])->name('comment.delete');[m
[31m-        Route::get('/purchase/address/{item_id}', [AddressController::class, 'edit'])->name('address.edit');[m
[31m-        Route::post('/purchase/address/{item_id}', [AddressController::class, 'update'])->name('address.update');[m
[31m-        Route::get('/purchase/{item_id}', [PurchaseController::class, 'index'])->name('purchase');[m
[31m-        Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');[m
[31m-        Route::get('/sell', [ItemController::class, 'create'])->name('sell.create');[m
[31m-        Route::post('/sell', [ItemController::class, 'store'])->name('sell.store');[m
[31m-        Route::get('/sell/edit/{item_id}', [ItemController::class, 'edit'])->name('sell.edit');[m
[31m-        Route::post('/sell/update/{item_id}', [ItemController::class, 'update'])->name('sell.update');[m
[31m-        Route::delete('/sell/delete/{item_id}', [ItemController::class, 'delete'])->name('sell.delete');[m
[32m+[m[32m        Route::get('mypage', [HomeController::class, 'myPageIndex'])->name('mypage');[m
[32m+[m[32m        Route::get('register/profile', [ProfileController::class, 'edit'])->name('register.profile.edit');[m
[32m+[m[32m        Route::get('mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');[m
[32m+[m[32m        Route::post('mypage/profile', [ProfileController::class, 'update'])->name('profile.update');[m
[32m+[m[32m        Route::post('item/{item_id}/like', [LikeController::class, 'toggleLike'])->name('like');[m
[32m+[m[32m        Route::post('item/{item_id}/comment', [CommentController::class, 'store'])->name('comment.store');[m
[32m+[m[32m        Route::post('item/{item_id}/comment/update/{comment_id}', [CommentController::class, 'update'])->name('comment.update');[m
[32m+[m[32m        Route::post('item/{item_id}/comment/delete/{comment_id}', [CommentController::class, 'delete'])->name('comment.delete');[m
[32m+[m[32m        Route::get('purchase/address/{item_id}', [AddressController::class, 'edit'])->name('address.edit');[m
[32m+[m[32m        Route::post('purchase/address/{item_id}', [AddressController::class, 'update'])->name('address.update');[m
[32m+[m[32m        Route::get('purchase/{item_id}', [PurchaseController::class, 'index'])->name('purchase');[m
[32m+[m[32m        Route::post('purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');[m
[32m+[m[32m        Route::get('sell', [ItemController::class, 'create'])->name('sell.create');[m
[32m+[m[32m        Route::post('sell', [ItemController::class, 'store'])->name('sell.store');[m
[32m+[m[32m        Route::get('sell/edit/{item_id}', [ItemController::class, 'edit'])->name('sell.edit');[m
[32m+[m[32m        Route::post('sell/update/{item_id}', [ItemController::class, 'update'])->name('sell.update');[m
[32m+[m[32m        Route::delete('sell/delete/{item_id}', [ItemController::class, 'delete'])->name('sell.delete');[m
 [m
[31m-        // 最終的にPOSTにする[m
[31m-        Route::delete('/user/deactivate', [UserController::class, 'deactivateUser'])->name('user.deactivate');[m
[31m-        Route::get('/test/thanks', function () {[m
[31m-            return view('thanks');[m
[31m-        });[m
[32m+[m[32m        // ユーザ無効化・有効化[m
[32m+[m[32m        Route::delete('user/deactivate', [UserController::class, 'deactivateUser'])->name('user.deactivate');[m
[32m+[m[32m        Route::get('activate/profile/password', [UserController::class, 'editPassword'])->name('activate.profile.edit');[m
[32m+[m[32m        Route::put('activate/profile/password', [UserController::class, 'updatePassword'])->name('activate.profile.update');[m
 [m
         // stripeの成功・キャンセル用ルーティング[m
[31m-        Route::get('/payment/success/{purchase_id}', [PurchaseController::class, 'success'])->name('payment.success');[m
[31m-        Route::get('/payment/cancel/{purchase_id}', [PurchaseController::class, 'cancel'])->name('payment.cancel');[m
[32m+[m[32m        Route::get('payment/success/{purchase_id}', [PurchaseController::class, 'success'])->name('payment.success');[m
[32m+[m[32m        Route::get('payment/cancel/{purchase_id}', [PurchaseController::class, 'cancel'])->name('payment.cancel');[m
     });[m
 });[m
\ No newline at end of file[m
[1mdiff --git a/src/storage/app/.gitignore b/src/storage/app/.gitignore[m
[1mnew file mode 100755[m
[1mindex 0000000..3d12156[m
[1m--- /dev/null[m
[1m+++ b/src/storage/app/.gitignore[m
[36m@@ -0,0 +1,5 @@[m
[32m+[m[32m*[m
[32m+[m[32m!public/[m
[32m+[m[32m!.gitignore[m
[32m+[m[32mpublic/profile_images/profile_image*[m
[32m+[m[32mpublic/item_images/item_image*[m
