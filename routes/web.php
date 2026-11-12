<?php

use App\Http\Controllers\AnneeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\BilanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ReunionController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\UniversiteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('inscription');
    Route::post('/inscription', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/bilans', [BilanController::class, 'index'])->name('bilans.index');
    Route::get('/bilans/nouveau', [BilanController::class, 'create'])->name('bilans.create');
    Route::post('/bilans', [BilanController::class, 'store'])->name('bilans.store');
    Route::get('/bilans/{bilan}', [BilanController::class, 'edit'])->name('bilans.edit');
    Route::put('/bilans/{bilan}', [BilanController::class, 'update'])->name('bilans.update');
    Route::post('/bilans/{bilan}/transmettre', [BilanController::class, 'transmettre'])->name('bilans.transmettre');
    Route::post('/bilans/{bilan}/examiner', [BilanController::class, 'examiner'])->name('bilans.examiner');
    Route::post('/bilans/{bilan}/rouvrir', [BilanController::class, 'rouvrir'])->name('bilans.rouvrir');

    Route::get('/ufrs', [StructureController::class, 'ufrs'])->name('ufrs.index');
    Route::post('/ufrs', [StructureController::class, 'storeUfr'])->name('ufrs.store');
    Route::put('/ufrs/{ufr}', [StructureController::class, 'updateUfr'])->name('ufrs.update');
    Route::delete('/ufrs/{ufr}', [StructureController::class, 'destroyUfr'])->name('ufrs.destroy');

    Route::get('/departements', [StructureController::class, 'departements'])->name('departements.index');
    Route::post('/departements', [StructureController::class, 'storeDepartement'])->name('departements.store');
    Route::put('/departements/{departement}', [StructureController::class, 'updateDepartement'])->name('departements.update');
    Route::delete('/departements/{departement}', [StructureController::class, 'destroyDepartement'])->name('departements.destroy');

    Route::get('/formations', [StructureController::class, 'formations'])->name('formations.index');
    Route::post('/formations', [StructureController::class, 'storeFormation'])->name('formations.store');
    Route::delete('/formations/{formation}', [StructureController::class, 'destroyFormation'])->name('formations.destroy');

    Route::get('/annees', [AnneeController::class, 'index'])->name('annees.index');
    Route::post('/annees', [AnneeController::class, 'storeAnnee'])->name('annees.store');
    Route::post('/annees/{annee}/statut', [AnneeController::class, 'statutAnnee'])->name('annees.statut');
    Route::post('/campagnes', [AnneeController::class, 'storeCampagne'])->name('campagnes.store');
    Route::post('/campagnes/{campagne}/statut', [AnneeController::class, 'statutCampagne'])->name('campagnes.statut');
    Route::post('/periodes', [AnneeController::class, 'storePeriode'])->name('periodes.store');
    Route::post('/periodes/{periode}/basculer', [AnneeController::class, 'togglePeriode'])->name('periodes.toggle');

    Route::get('/notifications', [AvisController::class, 'index'])->name('avis.index');
    Route::post('/notifications/lire', [AvisController::class, 'toutLire'])->name('avis.tout');
    Route::post('/notifications/{avis}/lire', [AvisController::class, 'lire'])->name('avis.lire');

    Route::get('/reunions', [ReunionController::class, 'index'])->name('reunions.index');
    Route::post('/reunions', [ReunionController::class, 'store'])->name('reunions.store');
    Route::get('/reunions/{reunion}', [ReunionController::class, 'show'])->name('reunions.show');
    Route::post('/reunions/{reunion}/emarger', [ReunionController::class, 'emarger'])->name('reunions.emarger');
    Route::post('/reunions/{reunion}/presence', [ReunionController::class, 'presence'])->name('reunions.presence');
    Route::post('/reunions/{reunion}/pv', [ReunionController::class, 'pv'])->name('reunions.pv');

    Route::get('/decisions', [DecisionController::class, 'index'])->name('decisions.index');
    Route::post('/decisions', [DecisionController::class, 'store'])->name('decisions.store');
    Route::post('/decisions/{decision}/statut', [DecisionController::class, 'statut'])->name('decisions.statut');

    Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
    Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
    Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/universites', [UniversiteController::class, 'index'])->name('universites.index');
    Route::post('/universites', [UniversiteController::class, 'store'])->name('universites.store');
    Route::put('/universites/{universite}', [UniversiteController::class, 'update'])->name('universites.update');

    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
});
