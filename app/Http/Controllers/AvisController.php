<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    public function index(Request $request)
    {
        $avis = Avis::where('user_id', $request->user()->id)->latest()->paginate(30);
        return view('avis.index', compact('avis'));
    }

    public function lire(Request $request, Avis $avis)
    {
        abort_unless((int) $avis->user_id === (int) $request->user()->id, 403);
        $avis->update(['lu_at' => now()]);
        return $avis->lien ? redirect($avis->lien) : back();
    }

    public function toutLire(Request $request)
    {
        Avis::where('user_id', $request->user()->id)->whereNull('lu_at')->update(['lu_at' => now()]);
        return back()->with('ok', 'Notifications marquées comme lues.');
    }
}
