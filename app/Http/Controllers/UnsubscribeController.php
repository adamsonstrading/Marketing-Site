<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    /**
     * Show unsubscribe page
     */
    public function index(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');
        
        if (!$email) {
            return redirect('/');
        }
        
        return view('unsubscribe', compact('email'));
    }

    /**
     * Process unsubscribe
     */
    public function unsubscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        
        // Add to blacklist
        Blacklist::addEmail($email, 'User unsubscribed');
        
        return redirect()->route('unsubscribe.success')->with('email', $email);
    }

    /**
     * Show success page
     */
    public function success(): View
    {
        return view('unsubscribe-success');
    }
}
