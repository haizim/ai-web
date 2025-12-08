<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravolt\Epicentrum\Contracts\Requests\Account\Store;
use Laravolt\Epicentrum\Mail\AccountInformation;
use Laravolt\Epicentrum\Repositories\RepositoryInterface;
use Laravolt\Platform\Models\User;
use Laravolt\Support\Contracts\TimezoneRepository;

class UsersCustomController extends Controller
{
    use AuthorizesRequests;

    protected RepositoryInterface $repository;

    protected TimezoneRepository $timezone;

    /**
     * UserController constructor.
     *
     * @param \Laravolt\Epicentrum\Repositories\RepositoryInterface $repository
     * @param \Laravolt\Support\Contracts\TimezoneRepository        $timezone
     */
    public function __construct(RepositoryInterface $repository, TimezoneRepository $timezone)
    {
        $this->repository = $repository;
        $this->timezone = $timezone;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        // save to db
        $roles = $request->get('roles', []);
        $user = $this->repository->createByAdmin($request->all(), $roles);
        
        $password = $request->get('password');

        if (config('laravolt.platform.features.verification') === false) {
            Log::debug('laravolt.platform.features.verification === false called from UserCustom');
            $user->markEmailAsVerified();
        }

        // send account info to email
        if ($request->has('send_account_information')) {
            Mail::to($user)->send(new AccountInformation($user, $password));
        }

        return redirect()->route('epicentrum::users.index')->withSuccess(trans('laravolt::message.user_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
