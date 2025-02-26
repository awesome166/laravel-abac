<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountUserController extends Controller
{
    public function attachUser(Request $request, Account $account)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_primary' => 'sometimes|boolean'
        ]);

        $user = User::find($request->user_id);
        $user->attachToAccount($account->id, $request->is_primary);

        return response()->json([
            'message' => 'User attached to account',
            'account' => $account,
            'user' => $user
        ]);
    }

    public function detachUser(Account $account, User $user)
    {
        $user->detachFromAccount($account->id);

        return response()->json([
            'message' => 'User detached from account',
            'account' => $account,
            'user' => $user
        ]);
    }

    public function setPrimaryAccount(Request $request, User $user)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id'
        ]);

        $user->setPrimaryAccount($request->account_id);

        return response()->json([
            'message' => 'Primary account updated',
            'user' => $user->load('primaryAccount')
        ]);
    }

    public function getUserAccounts(User $user)
    {
        return response()->json([
            'accounts' => $user->accounts,
            'primary_account' => $user->primaryAccount
        ]);
    }
}