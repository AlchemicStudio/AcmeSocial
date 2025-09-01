<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of transactions (only for "manage donations" permission).
     */
    public function index(): AnonymousResourceCollection
    {
        $user = Auth::user();

        if (!$user->can('manage donations') && !$user->is_admin) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to view transactions.');
        }

        $transactions = Transaction::with(['donation.campaign', 'donation.donor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return TransactionResource::collection($transactions);
    }
}
