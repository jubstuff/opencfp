<?php

namespace OpenCFP\Http\Controller\Admin;

use OpenCFP\Domain\Services\Authentication;

trait VoterAccessTrait
{
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            // Check if user is an logged in and an Voter
            if (! $this->userHasAccess()) {
                return $this->redirectTo('dashboard');
            }

            return call_user_func_array([$this, $method], $arguments);
        }
    }

    protected function userHasAccess()
    {
        /** @var Authentication $auth */
        $auth = $this->app[Authentication::class];

        if (!$auth->check()) {
            return false;
        }

        $user = $auth->user();

        if (!($user->hasPermission('vote') || $user->hasPermission('admin'))) {
            return false;
        }

        return true;
    }
}
