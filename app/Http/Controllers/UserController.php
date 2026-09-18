<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Hanya Admin & Super Admin yang sampai di sini (sudah dijaga Middleware di routes)
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->withTrashed()->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'username'       => 'required|string|max:50|unique:users',
            'email'          => 'required|email|max:255|unique:users',
            'password'       => 'required|string|min:8',
            'role'           => 'required|in:Super Admin,Admin,Admin Purchase,Leader,Sales,Sales Marketing',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:Active,Inactive',
            'monthly_target' => 'nullable|numeric|min:0',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = match ($validated['role']) {
            'Admin Purchase' => 'Admin',
            'Sales Marketing' => 'Sales',
            default => $validated['role'],
        };

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'Berhasil menambahkan karyawan baru!');
    }

    public function edit(User $user)
    {
        // Untuk fitur migrate customer, kita butuh daftar Sales yang aktif selain dia sendiri
        $activeSales = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->where('id', '!=', $user->id)
            ->get();

        return view('users.edit', compact('user', 'activeSales'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'username'       => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'          => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'       => 'nullable|string|min:8',
            'role'           => 'required|in:Super Admin,Admin,Admin Purchase,Leader,Sales,Sales Marketing',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:Active,Inactive',
            'monthly_target' => 'nullable|numeric|min:0',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['role'] = match ($validated['role']) {
            'Admin Purchase' => 'Admin',
            'Sales Marketing' => 'Sales',
            default => $validated['role'],
        };

        $user->update($validated);

        return redirect()->route('users.index')->with('success', "Data {$user->name} berhasil diupdate!");
    }

    public function migrateCustomers(Request $request, User $user)
    {
        $request->validate([
            'new_sales_id' => 'required|exists:users,id'
        ]);

        $newSales = User::findOrFail($request->new_sales_id);

        // Pindahkan semua customer dari sales lama ke sales baru
        $count = \App\Models\Customer::where('sales_id', $user->id)->update([
            'sales_id' => $newSales->id
        ]);

        return redirect()->back()->with('success', "Berhasil memigrasikan {$count} database pelanggan ke {$newSales->name}.");
    }

    public function destroy(User $user)
    {
        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun {$name} berhasil dinonaktifkan.");
    }

    public function restore(User $user)
    {
        $user->restore();

        return redirect()->route('users.index')->with('success', "Akun {$user->name} berhasil diaktifkan kembali.");
    }
}
