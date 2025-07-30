<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Notifications\OtpNotification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Helper\Mailer;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private \App\Models\User $user;

    // private $all_permission_roles = ['IT Admin', 'Director'];

    // public function __construct()
    // {
    //     $this->middleware(function ($request, $next) {
    //         $this->user = \App\Models\User::find(auth()->id());
    //         return $next($request);
    //     });
    // }

    function login(Request $request)
    {
        $request->validate([
            'username' => 'required|email|max:191',
            'password' => 'required|min:5'
        ]);

        if (auth()->attempt(['email' => $request['username'], 'password' => $request['password'], 'is_active' => 1])) {
            $user = User::find(auth()->id());

            $token = $user->createToken('api');

            return response()->json(['token' => $token->plainTextToken]);
        } else {
            return response()->json(['errorMsg' => 'Invalid Credentials!'], 422);
        }
    }

    function index()
    {
        $user = User::find(auth()->id());

        $page = request('page') ? (int)request('page') : 1;
        $limit = request('limit') ? (int)request('limit') : 30;
        $offset = ($page - 1) * $limit;

        $query = User::with('roles:id,name', 'history.addedBy.roles:id,name', 'history.editedBy.roles:id,name', 'addedBy.roles:id,name', 'editedBy.roles:id,name');

        $query->when(
            request('search'),
            fn($q) => $q->where('first_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('middle_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('last_name', 'LIKE', '%' . request('search') . '%')
                ->orwhere('employee_code', 'LIKE', '%' . request('search') . '%')
        );


        if (!auth()->user()->hasRole('Pensioners Operator')) {
            if ($user->institute !== 'BOTH') {
                $query->where('institute', $user->institute);
            } else {
                $query->when(
                    request('institute'),
                    fn($q) => $q->where('institute', request('institute'))
                );
            }
        }

        // $query->where()

        $total_count = $query->count();

        $data = $query->orderBy('created_at', 'DESC')->offset($offset)->limit($limit)->get();

        return response()->json(['data' => $data, 'total_count' => $total_count]);
    }

    function allUsers()
    {
        $user = User::find(auth()->id());

        $query = User::with('roles');

        if (!auth()->user()->hasRole('Pensioners Operator')) {
            if ($user->institute !== 'BOTH') {
                $query->where('institute', $user->institute);
            } else {
                $query->when(
                    request('institute'),
                    fn($q) => $q->where('institute', request('institute'))
                );
            }
        }

        $query->withCount('employee');

        $data = $query->get();

        return response()->json(['data' => $data]);
    }

    function user()
    {

        $user = User::with('roles:id,name')->find(auth()->id());


        return response()->json(['data' => $user]);
    }

    function changeStatus($id)
    {
        $userRole = User::with('role')->find(auth()->id());
        if ($userRole->role_id != 1) return response()->json(['errorMsg' => 'You are not allowed to do this!'], 401);

        $user = User::find($id);
        if (!$user) return response()->json(['errorMsg' => 'User not found!'], 404);

        if ($id == 1) return response()->json(['errorMsg' => 'Admin status can not be changed!'], 409);

        DB::beginTransaction();

        $old_data = $user->toArray();

        $user->is_active = !$user->is_active;

        try {
            $user->save();

            $user->history()->create($old_data);
            DB::commit();
            return response()->json(['successMsg' => 'Status successfully changed!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function store(Request $request)
    {
        $allowedRoles = Role::where('name', '!=', 'IT Admin')->pluck('name')->toArray();
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($allowedRoles)],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'is_retired' => 'required|in:0,1',
            'employee_code' => 'required|string|min:3|max:191|unique:users,employee_code',
            'password' => 'required|string|min:5|max:30',
            'email' => 'required_if:is_retired,0|nullable|email|unique:users,email',
            'institute' => 'required|in:NIOH,ROHC,BOTH'
        ]);


        $user = new User();
        $user->first_name = $request['first_name'];
        $user->middle_name = $request['middle_name'];
        $user->last_name = $request['last_name'];
        $user->employee_code = $request['employee_code'];
        $user->email = $request['email'];
        $user->is_retired = $request['is_retired'];
        $user->institute = $request['institute'];
        $user->password = Hash::make($request['password']);
        $user->added_by = auth()->id();

        try {
            $user->save();

            $user->syncRoles($request['roles']);

            return response()->json(['successMsg' => 'User successfully created!', 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    function update(Request $request, $id)
    {
        if (!auth()->user()->hasRole('IT Admin')) {
            return response()->json(['errorMsg' => 'You Don\'t have Access!'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['errorMsg' => 'User not found!'], 404);
        $allowedRoles = Role::where('name', '!=', 'IT Admin')->pluck('name')->toArray();

        $employee = Employee::where('user_id', $user->id)->first();


        // if ($request['role_id'] == 1 || $id == 1) return response()->json(['errorMsg' => 'Admin already exists!'], 409);
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($allowedRoles)],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'is_retired' => 'required|in:1,0',
            'employee_code' => "required|string|min:3|max:191|unique:users,employee_code,$id,id",
            'email' => "required_if:is_retired,0|nullable|email|unique:users,email,$id,id",
            'institute' => 'required|in:NIOH,ROHC,BOTH'
        ]);

        DB::beginTransaction();

        $old_data = $user->toArray();
        if ($employee) {
            $old_employee_data = $employee->toArray();
        }

        $user->first_name = $request['first_name'];
        $user->middle_name = $request['middle_name'];
        $user->last_name = $request['last_name'];
        $user->employee_code = $request['employee_code'];
        $user->email = $request['email'];
        $user->is_retired = $request['is_retired'];
        $user->institute = $request['institute'];
        $user->edited_by = auth()->id();

        try {
            $user->save();

            if ($employee) {
                $employee->first_name = $request['first_name'];
                $employee->middle_name = $request['middle_name'];
                $employee->last_name = $request['last_name'];
                $employee->employee_code = $request['employee_code'];
                $employee->email = $request['email'];
                $employee->institute = $request['institute'];
                $employee->edited_by = auth()->id();
                $employee->save();
                $employee->history()->create($old_employee_data);
            }

            $user->syncRoles($request['roles']);

            $user->history()->create($old_data);

            DB::commit();
            return response()->json(['successMsg' => 'User successfully updated', 'data' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request, $userId)
    {
        // Ensure only IT Admin can change passwords
        if (!auth()->user()->hasRole('IT Admin')) {
            return response()->json(['errorMsg' => 'Unauthorized. Only IT Admin can change passwords.'], 403);
        }

        $request->validate([
            'password' => 'required|string|max:30',
        ]);

        try {
            $user = User::findOrFail($userId);
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['successMsg' => 'Password updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    public function assignRoles(Request $request, $userId)
    {
        // Ensure only IT Admin can assign roles
        if (!auth()->user()->hasRole('IT Admin')) {
            return response()->json(['errorMsg' => 'Unauthorized. Only IT Admin can assign roles.'], 403);
        }

        $allowedRoles = Role::where('name', '!=', 'IT Admin')->pluck('name')->toArray();

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($allowedRoles)],
        ]);

        try {
            $user = User::findOrFail($userId);
            $user->assignRole($request->roles);

            // $roles = $request->roles;

            // $ddoNioh = 'Drawing and Disbursing Officer (NIOH)';
            // $ddoRohc = 'Drawing and Disbursing Officer (ROHC)';

            // $spcNioh = 'Salary Processing Coordinator (NIOH)';
            // $spcRohc = 'Salary Processing Coordinator (ROHC)';

            // // If user has both DDO roles or both SPC roles, set institute to BOTH
            // if (
            //     (in_array($ddoNioh, $roles) && in_array($ddoRohc, $roles)) ||
            //     (in_array($spcNioh, $roles) && in_array($spcRohc, $roles))
            // ) {
            //     $user->institute = 'BOTH';
            //     $user->edited_by = auth()->id();
            //     $user->save();
            // }

            return response()->json(['successMsg' => 'Roles assigned successfully.']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }

    public function removeRoles(Request $request, $userId)
    {
        // Ensure only IT Admin can remove roles
        if (!auth()->user()->hasRole('IT Admin')) {
            return response()->json(['errorMsg' => 'Unauthorized. Only IT Admin can remove roles.'], 403);
        }

        $allowedRoles = Role::where('name', '!=', 'IT Admin')->pluck('name')->toArray();

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => ['string', Rule::in($allowedRoles)],
        ]);

        try {
            $user = User::findOrFail($userId);

            $currentRoles = $user->roles->pluck('name')->toArray();
            $rolesToRemove = $request->roles;

            // Prevent removing all roles
            if (count($currentRoles) === count($rolesToRemove)) {
                return response()->json(['errorMsg' => 'Cannot remove the last role. A user must have at least one role.'], 422);
            }

            foreach ($rolesToRemove as $roleName) {
                if ($user->hasRole($roleName)) {
                    $user->removeRole($roleName);
                }
            }

            return response()->json(['successMsg' => 'Roles removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => $e->getMessage()], 500);
        }
    }




    /**
     * Send OTP for password reset
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['errorMsg' => 'User not found with this email address.'], 404);
        }

        if (!$user->is_active) {
            return response()->json(['errorMsg' => 'Your account is deactivated. Please contact administrator.'], 403);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Set OTP expiry time (10 minutes from now)
        $otpExpiresAt = Carbon::now()->addMinutes(10);

        $user->otp = $otp;
        $user->otp_expires_at = $otpExpiresAt;
        $user->save();

        try {
            // Send OTP via PHPMailer
            $sent = $this->sendOtpMail($user->email, $otp);
            if (!$sent) {
                return response()->json(['errorMsg' => 'Failed to send OTP. Please try again.'], 500);
            }
            return response()->json([
                'successMsg' => 'OTP has been sent to your email address.',
                $sent
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => 'Failed to send OTP. Please try again.'], 500);
        }
    }

    /**
     * Verify OTP and allow password reset
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['errorMsg' => 'User not found.'], 404);
        }

        // Check if OTP is valid and not expired
        if ($user->otp !== $request->otp) {
            return response()->json(['errorMsg' => 'Invalid OTP.'], 422);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['errorMsg' => 'OTP has expired. Please request a new one.'], 422);
        }

        // Generate password reset token
        $resetToken = Str::random(60);
        $resetTokenExpiresAt = Carbon::now()->addMinutes(30);

        $user->password_reset_token = $resetToken;
        $user->password_reset_expires_at = $resetTokenExpiresAt;
        $user->otp = null; // Clear OTP after successful verification
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'successMsg' => 'OTP verified successfully.',
            'reset_token' => $resetToken,
            'message' => 'You can now reset your password.'
        ]);
    }

    /**
     * Reset password using reset token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:5|max:30'
        ]);

        $user = User::where('email', $request->email)
            ->where('password_reset_token', $request->reset_token)
            ->first();

        if (!$user) {
            return response()->json(['errorMsg' => 'Invalid reset token.'], 422);
        }

        // Check if reset token is expired
        if (Carbon::now()->isAfter($user->password_reset_expires_at)) {
            return response()->json(['errorMsg' => 'Reset token has expired. Please request a new OTP.'], 422);
        }

        // Update password and clear reset token
        $user->password = Hash::make($request->password);
        $user->password_reset_token = null;
        $user->password_reset_expires_at = null;
        $user->save();

        return response()->json([
            'successMsg' => 'Password has been reset successfully.',
            'message' => 'You can now login with your new password.'
        ]);
    }

    /**
     * Resend OTP if expired
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['errorMsg' => 'User not found.'], 404);
        }

        if (!$user->is_active) {
            return response()->json(['errorMsg' => 'Your account is deactivated.'], 403);
        }

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = Carbon::now()->addMinutes(10);

        $user->otp = $otp;
        $user->otp_expires_at = $otpExpiresAt;
        $user->save();

        try {
            $sent = $this->sendOtpMail($user->email, $otp);
            if (!$sent) {
                return response()->json(['errorMsg' => 'Failed to send OTP. Please try again.'], 500);
            }
            return response()->json([
                'successMsg' => 'New OTP has been sent to your email.',
                'message' => 'Please check your email for the new OTP.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => 'Failed to send OTP. Please try again.'], 500);
        }
    }

    /**
     * Send OTP email using Mailer helper
     */
    private function sendOtpMail($email, $otp)
    {
        try {

            $mailer = new Mailer();

            $mailData = [
                'to' => $email,
                'subject' => 'Password Reset OTP - NIOH Payroll System',
                'body' => "Your OTP for password reset is: $otp\n\nThis OTP will expire in 10 minutes.\n\nBest regards,\nNIOH Payroll Team"
            ];

            $result = $mailer->sendMail($mailData);
            return $result;
        } catch (\Exception $e) {
            // \Log::error('OTP Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        try {
            $mailer = new Mailer();

            $mailData = [
                'to' => 'detobah721@fenexy.com',
                'subject' => 'Test Email - NIOH Payroll System',
                'body' => 'This is a test email to verify email configuration.'
            ];

            $result = $mailer->sendMail($mailData);

            if ($result) {
                return response()->json(['successMsg' => 'Test email sent successfully!']);
            } else {
                return response()->json(['errorMsg' => 'Failed to send test email. Check logs for details.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['errorMsg' => 'Email test failed: ' . $e->getMessage()], 500);
        }
    }
}
