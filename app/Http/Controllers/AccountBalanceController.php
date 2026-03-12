<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountBalanceController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);
        $transactions = Transaction::where('user_id', $user->id)->orderByDesc('created_at')->paginate(20);

        return view('account-balance.index', [
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }
}
