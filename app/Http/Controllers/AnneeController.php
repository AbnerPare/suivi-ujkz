<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Campagne;
use App\Models\Periode;
use App\Models\Universite;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;

class AnneeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Roles::ADMIN, Roles::ADMIN_UNIV, Roles::DIP, Roles::VICE], true), 403);
        if ($request->user()->isAdmin() && ! $request->query('universite_id')) {
            $universites = Universite::orderBy('nom')->get();
            return view('structures.choisir', compact('universites'));
        }
        $universiteId = $request->user()->isAdmin()
            ? (int) $request->query('universite_id')
            : (int) $request->user()->universite_id;
        $annees = AnneeAcademique::with('campagnes.periodes')->where('universite_id', $universiteId)->orderByDesc('libelle')->get();
        $editable = $request->user()->ouvreCampagnes();
        return view('annees.index', compact('annees', 'editable', 'universiteId'));
    }

    public function storeAnnee(Request $request)
    {
        $this->write($request);
        $data = $request->validate([
            'libelle' => 'required|string|max:20',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);
        $data['universite_id'] = $this->uid($request);
        AnneeAcademique::create($data);
        Notifier::journal($request->user(), 'Création d\'une année académique', $data['libelle']);
        return back()->with('ok', 'Année académique créée.');
    }

    public function statutAnnee(Request $request, AnneeAcademique $annee)
    {
        $this->write($request);
        abort_unless((int) $annee->universite_id === $this->uid($request) || $request->user()->isAdmin(), 403);
        $data = $request->validate(['statut' => 'required|in:ouverte,cloturee,archivee']);
        $annee->update($data);
        return back()->with('ok', 'Statut de l\'année mis à jour.');
    }

    public function storeCampagne(Request $request)
    {
        $this->write($request);
        $data = $request->validate([
            'annee_academique_id' => 'required|exists:annees_academiques,id',
            'libelle' => 'required|string|max:120',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
        ]);
        $annee = AnneeAcademique::findOrFail($data['annee_academique_id']);
        abort_unless((int) $annee->universite_id === $this->uid($request) || $request->user()->isAdmin(), 403);
        Campagne::create($data);
        return back()->with('ok', 'Campagne de suivi ouverte.');
    }

    public function statutCampagne(Request $request, Campagne $campagne)
    {
        $this->write($request);
        abort_unless((int) $campagne->annee->universite_id === $this->uid($request) || $request->user()->isAdmin(), 403);
        $data = $request->validate(['statut' => 'required|in:ouverte,cloturee,archivee']);
        $campagne->update($data);
        return back()->with('ok', 'Campagne mise à jour.');
    }

    public function storePeriode(Request $request)
    {
        $this->write($request);
        $data = $request->validate([
            'campagne_id' => 'required|exists:campagnes,id',
            'mois' => 'required|integer|min:1|max:12',
            'annee' => 'required|integer|min:2020|max:2100',
        ]);
        $campagne = Campagne::with('annee')->findOrFail($data['campagne_id']);
        abort_unless((int) $campagne->annee->universite_id === $this->uid($request) || $request->user()->isAdmin(), 403);
        $data['libelle'] = Periode::MOIS[(int) $data['mois']].' '.$data['annee'];
        Periode::create($data);
        return back()->with('ok', 'Période mensuelle ajoutée.');
    }

    public function togglePeriode(Request $request, Periode $periode)
    {
        $this->write($request);
        abort_unless((int) $periode->campagne->annee->universite_id === $this->uid($request) || $request->user()->isAdmin(), 403);
        $periode->update(['ouverte' => ! $periode->ouverte]);
        return back()->with('ok', $periode->ouverte ? 'Période rouverte.' : 'Période clôturée.');
    }

    private function write(Request $request): void
    {
        abort_unless($request->user()->ouvreCampagnes(), 403);
    }

    private function uid(Request $request): int
    {
        return $request->user()->isAdmin()
            ? (int) $request->input('universite_id', $request->query('universite_id'))
            : (int) $request->user()->universite_id;
    }
}
