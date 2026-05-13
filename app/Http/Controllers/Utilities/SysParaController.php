<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\SysPara;
use Illuminate\Http\Request;

class SysParaController extends Controller
{
    public function index()
    {
        $para = SysPara::first() ?? new SysPara();

        return view('utilities.system-parameters', compact('para'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'admin_name' => 'nullable|string|max:50',
            'user_name'  => 'nullable|string|max:50',
            'admin_pw'   => 'nullable|string|max:100',
            'user_pw'    => 'nullable|string|max:100',
        ]);

        $para = SysPara::first();

        $data = [
            'admin_name' => $request->admin_name,
            'user_name'  => $request->user_name,
        ];

        if ($request->filled('admin_pw')) {
            $data['admin_pw'] = $request->admin_pw;
        }
        if ($request->filled('user_pw')) {
            $data['user_pw'] = $request->user_pw;
        }

        if ($para) {
            $para->update($data);
        } else {
            SysPara::create($data);
        }

        return back()->with('success', 'System parameters saved successfully.');
    }
}
