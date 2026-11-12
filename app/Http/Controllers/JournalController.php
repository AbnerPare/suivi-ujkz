<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Support\Roles;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === Roles::ADMIN, 403);
        $journaux = Journal::with(['user', 'universite'])->latest()->paginate(40);
        return view('journal.index', compact('journaux'));
    }
}
