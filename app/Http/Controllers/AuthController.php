<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('dashboard');
        }

        $branches       = Branch::orderBy('br_name')->get();
        $financialYears = FinancialYear::orderBy('start_date')->get();
        $activeYearId   = FinancialYear::where('is_active', 1)->value('id');

        return view('auth.login', compact('branches', 'financialYears', 'activeYearId'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'br_code'    => 'required|integer',
            'fin_year_id' => 'required|integer',
            'user_name'  => 'required|string',
            'password'   => 'required|string',
            'login_date' => 'required|date',
        ]);

        $user = User::where('user_name', $request->user_name)
                    ->where('br_code', $request->br_code)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withInput($request->except('password'))
                         ->with('error', 'Invalid username or password.');
        }

        $branch      = Branch::find($request->br_code);
        $finYear     = FinancialYear::find($request->fin_year_id);

        if (!$branch || !$finYear) {
            return back()->withInput($request->except('password'))
                         ->with('error', 'Invalid branch or financial year.');
        }

        session([
            'user_id'       => $user->id,
            'user_name'     => $user->user_name,
            'user_type'     => $user->user_type,
            'br_code'       => $branch->br_code,
            'br_name'       => $branch->br_name,
            'fin_year_id'   => $finYear->id,
            'fin_year_name' => $finYear->year_name,
            'login_date'    => $request->login_date,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
