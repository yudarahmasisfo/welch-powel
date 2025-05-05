#app/Http/Controllers/ValidationDemoController.php

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ValidationDemoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'age' => 'required|integer|min:17|max:99',
            'password' => 'required|confirmed|min:8',
            'role' => 'required|in:admin,user',
            'photo' => 'nullable|image|mimes:jpg,png|max:1024',
        ]);

        return back()->with('success', 'Data valid! Siap diproses.');
    }
}
