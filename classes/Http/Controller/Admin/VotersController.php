<?php

namespace OpenCFP\Http\Controller\Admin;

use OpenCFP\Domain\Services\AccountManagement;
use OpenCFP\Domain\Services\Authentication;
use OpenCFP\Http\Controller\BaseController;
use Pagerfanta\View\TwitterBootstrap3View;
use Spot\Locator;
use Symfony\Component\HttpFoundation\Request;

class VotersController extends BaseController
{
    use VoterAccessTrait;

    public function indexAction(Request $req)
    {
        if (!$this->userHasAccess()) {
            return $this->redirectTo('dashboard');
        }

        /* @var AccountManagement $accounts */
        $accounts = $this->service(AccountManagement::class);

        $voterUsers = $accounts->findByRole('Voters');

        // Set up our page stuff
        $adapter = new \Pagerfanta\Adapter\ArrayAdapter($voterUsers);
        $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->getNbResults();

        if ($req->get('page') !== null) {
            $pagerfanta->setCurrentPage($req->get('page'));
        }

        // Create our default view for the navigation options
        $routeGenerator = function ($page) {
            return '/voter/voters?page=' . $page;
        };
        $view = new TwitterBootstrap3View();
        $pagination = $view->render(
            $pagerfanta,
            $routeGenerator,
            ['proximity' => 3]
        );

        $templateData = [
            'pagination' => $pagination,
            'speakers' => $pagerfanta,
            'page' => $pagerfanta->getCurrentPage(),
        ];

        return $this->render('admin/voters/index.twig', $templateData);
    }

    public function removeAction(Request $req)
    {
        if (!$this->userHasAccess()) {
            return $this->redirectTo('dashboard');
        }

        /** @var Authentication $auth */
        $auth = $this->service(Authentication::class);

        /** @var AccountManagement $accounts */
        $accounts = $this->service(AccountManagement::class);

        $voter = $auth->user();

        if ($voter->getId() == $req->get('id')) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'Sorry, you cannot remove yourself as Voter.',
            ]);

            return $this->redirectTo('admin_voters');
        }

        /* @var Locator $spot */
        $spot = $this->service('spot');

        $mapper = $spot->mapper(\OpenCFP\Domain\Entity\User::class);
        $user_data = $mapper->get($req->get('id'))->toArray();
        $user = $accounts->findByLogin($user_data['email']);

        try {
            $accounts->demoteVoter($user->getLogin());

            $this->service('session')->set('flash', [
                'type' => 'success',
                'short' => 'Success',
                'ext' => 'Successfully removed the Voter!',
            ]);
        } catch (\Exception $e) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'We were unable to remove the Voter. Please try again.',
            ]);
        }

        return $this->redirectTo('admin_voters');
    }

    public function promoteAction(Request $req)
    {
        if (!$this->userHasAccess()) {
            return $this->redirectTo('dashboard');
        }

        /* @var AccountManagement $accounts */
        $accounts = $this->service(AccountManagement::class);

        /* @var Locator $spot */
        $spot = $this->service('spot');

        $mapper = $spot->mapper(\OpenCFP\Domain\Entity\User::class);
        $user_data = $mapper->get($req->get('id'))->toArray();
        $user = $accounts->findByLogin($user_data['email']);

        if ($user->hasAccess('voter')) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'User already is in the Voter group.',
            ]);

            return $this->redirectTo('admin_voters');
        }

        try {
            $accounts->promoteVoter($user->getLogin());

            $this->service('session')->set('flash', [
                'type' => 'success',
                'short' => 'Success',
                'ext' => 'Successfully promoted as an Voter!',
            ]);
        } catch (\Exception $e) {
            $this->service('session')->set('flash', [
                'type' => 'error',
                'short' => 'Error',
                'ext' => 'We were unable to promote the Voter. Please try again.',
            ]);
        }

        return $this->redirectTo('admin_voters');
    }
}
