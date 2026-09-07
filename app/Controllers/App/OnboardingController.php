<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

/**
 * Onboarding pos-cadastro: tipo de negocio e objetivo para personalizar a experiencia.
 */
class OnboardingController extends Controller
{
    public function show(): Response
    {
        $user = $this->user();
        if ($user['onboarding_done']) {
            return $this->redirect('/dashboard');
        }
        return $this->view('app.onboarding', ['title' => 'Bem-vindo']);
    }

    public function store(Request $request): Response
    {
        $userId = $this->auth()->id();
        $businessType = (string) $request->input('business_type');
        $goal = (string) $request->input('goal');

        User::update($userId, [
            'business_type' => $businessType ?: null,
            'goal' => $goal ?: null,
            'onboarding_done' => 1,
        ]);

        $this->withFlash('success', 'Tudo pronto! Vamos comecar.');
        return $this->redirect('/dashboard');
    }
}
