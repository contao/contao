<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Controller;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\Encryption;
use Contao\InstallationBundle\Config\ParameterDumper;
use Contao\InstallationBundle\Database\ConnectionFactory;
use Contao\InstallationBundle\Database\VersionUpdateInterface;
use Doctrine\DBAL\DBALException;
use Patchwork\Utf8;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the installation process.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @Route(defaults={"_scope" = "backend"})
 */
class InstallationController extends ContainerAware
{
    /**
     * @var array
     */
    private $context = [];

    /**
     * Handles the installation process.
     *
     * @return Response The response object
     *
     * @Route("/_contao/install", name="contao_install")
     */
    public function indexAction()
    {
        $installTool = $this->container->get('contao.install_tool');

        $this->runPostInstallCommands();

        if ($installTool->isLocked()) {
            return $this->render('locked.html.twig');
        }

        if (!$installTool->canWriteFiles()) {
            return $this->render('not_writable.html.twig');
        }

        if ($installTool->shouldAcceptLicense()) {
            return $this->acceptLicense();
        }

        $installTool->createLocalConfigurationFiles();

        if ('' === $installTool->getConfig('installPassword')) {
            return $this->setPassword();
        }

        if (!$this->container->get('contao.install_tool_user')->isAuthenticated()) {
            return $this->login();
        }

        if (!$installTool->canConnectToDatabase($this->container->getParameter('database_name'))) {
            return $this->setUpDatabaseConnection();
        }

        if ($installTool->hasOldDatabase()) {
            return $this->render('old_database.html.twig');
        }

        $this->runDatabaseUpdates();

        if (null !== ($response = $this->adjustDatabaseTables())) {
            return $response;
        }

        if (null !== ($response = $this->importExampleWebsite())) {
            return $response;
        }

        if (null !== ($response = $this->createAdminUser())) {
            return $response;
        }

        return $this->render('main.html.twig', $this->context);
    }

    /**
     * Runs the post install commands.
     */
    private function runPostInstallCommands()
    {
        $rootDir = $this->container->getParameter('kernel.root_dir');

        if (is_dir($rootDir . '/../files') && is_link($rootDir . '/../web/assets')) {
            return;
        }

        // Install the bundle assets
        $command = new AssetsInstallCommand();
        $command->setContainer($this->container);
        $command->run(new ArgvInput(['assets:install', '--relative', $rootDir . '/../web']), new NullOutput());

        // Add the Contao directories
        $command = new InstallCommand();
        $command->setContainer($this->container);
        $command->run(new ArgvInput([]), new NullOutput());

        // Generate the symlinks
        $command = new SymlinksCommand();
        $command->setContainer($this->container);
        $command->run(new ArgvInput([]), new NullOutput());
    }

    /**
     * Renders a form to accept the license.
     *
     * @return Response|RedirectResponse The response object
     */
    private function acceptLicense()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_license' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('license.html.twig');
        }

        $this->container->get('contao.install_tool')->persistConfig('licenseAccepted', true);

        return $this->getRedirectResponse();
    }

    /**
     * Renders a form to set the install tool password.
     *
     * @return Response|RedirectResponse The response object
     */
    private function setPassword()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_password' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('password.html.twig');
        }

        $password = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');

        // The passwords do not match
        if ($password !== $confirmation) {
            return $this->render('password.html.twig', [
                'error' => $this->trans('password_confirmation_mismatch'),
            ]);
        }

        $installTool = $this->container->get('contao.install_tool');
        $minlength = $installTool->getConfig('minPasswordLength');

        // The passwords is too short
        if (Utf8::strlen($password) < $minlength) {
            return $this->render('password.html.twig', [
                'error' => sprintf($this->trans('password_too_short'), $minlength),
            ]);
        }

        $installTool->persistConfig('installPassword', Encryption::hash($password));
        $this->container->get('contao.install_tool_user')->setAuthenticated(true);

        return $this->getRedirectResponse();
    }

    /**
     * Renders a form to log in.
     *
     * @return Response|RedirectResponse The response object
     */
    private function login()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('login.html.twig');
        }

        $installTool = $this->container->get('contao.install_tool');

        $verified = Encryption::verify(
            $request->request->get('password'),
            $installTool->getConfig('installPassword')
        );

        if (!$verified) {
            $installTool->increaseLoginCount();

            return $this->render('login.html.twig', [
                'error' => $this->trans('invalid_password'),
            ]);
        }

        $installTool->resetLoginCount();
        $this->container->get('contao.install_tool_user')->setAuthenticated(true);

        return $this->getRedirectResponse();
    }

    /**
     * Renders a form to set up the database connection.
     *
     * @return Response|RedirectResponse The response object
     */
    private function setUpDatabaseConnection()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $parameters['parameters'] = [
            'database_host' => $this->container->getParameter('database_host'),
            'database_port' => $this->container->getParameter('database_port'),
            'database_user' => $this->container->getParameter('database_user'),
            'database_password' => $this->container->getParameter('database_password'),
            'database_name' => $this->container->getParameter('database_name'),
        ];

        if ('tl_database_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('database.html.twig', $parameters);
        }

        $parameters['parameters'] = [
            'database_host' => $request->request->get('dbHost'),
            'database_port' => $request->request->get('dbPort'),
            'database_user' => $request->request->get('dbUser'),
            'database_password' => $this->container->getParameter('database_password'),
            'database_name' => $request->request->get('dbName'),
        ];

        if ('*****' !== $request->request->get('dbPassword')) {
            $parameters['parameters']['database_password'] = $request->request->get('dbPassword');
        }

        $installTool = $this->container->get('contao.install_tool');
        $installTool->setConnection(ConnectionFactory::create($parameters));

        if (!$installTool->canConnectToDatabase($parameters['parameters']['database_name'])) {
            return $this->render('database.html.twig', array_merge(
                $parameters,
                ['database_error' => $this->trans('database_could_not_connect')]
            ));
        }

        $dumper = new ParameterDumper($this->container->getParameter('kernel.root_dir'));
        $dumper->setParameters($parameters);
        $dumper->dump();

        return $this->getRedirectResponse();
    }

    /**
     * Runs the database updates.
     */
    private function runDatabaseUpdates()
    {
        if ($this->container->get('contao.install_tool')->isFreshInstallation()) {
            return;
        }

        $finder = Finder::create()
            ->files()
            ->name('Version*Update.php')
            ->in(__DIR__ . '/../Database')
        ;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $class = 'Contao\\InstallationBundle\\Database\\' . $file->getBasename('.php');

            /** @var VersionUpdateInterface $update */
            $update = new $class($this->container->get('database_connection'));

            if ($update instanceof VersionUpdateInterface && $update->shouldBeRun()) {
                $update->run();
            }
        }
    }

    /**
     * Renders a form to adjust the database tables.
     *
     * @return Response|RedirectResponse|null The response object
     */
    private function adjustDatabaseTables()
    {
        $this->container->get('contao.install_tool')->handleRunOnce();

        $installer = $this->container->get('contao.installer');

        $this->context['sql_form'] = $installer->getCommands();

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_database_update' !== $request->request->get('FORM_SUBMIT')) {
            return null;
        }

        $sql = $request->request->get('sql');

        if (!empty($sql) && is_array($sql)) {
            foreach ($sql as $hash) {
                $installer->execCommand($hash);
            }
        }

        return $this->getRedirectResponse();
    }

    /**
     * Renders a form to import the example website.
     *
     * @return Response|RedirectResponse|null The response object
     */
    private function importExampleWebsite()
    {
        $installTool = $this->container->get('contao.install_tool');
        $templates = $installTool->getTemplates();

        $this->context['templates'] = $templates;

        if ($installTool->getConfig('exampleWebsite')) {
            $this->context['import_date'] = date('Y-m-d H:i', $installTool->getConfig('exampleWebsite'));
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_template_import' !== $request->request->get('FORM_SUBMIT')) {
            return null;
        }

        $template = $request->request->get('template');

        if ('' === $template || !in_array($template, $templates)) {
            $this->context['import_error'] = $this->trans('import_empty_source');

            return null;
        }

        try {
            $installTool->importTemplate($template, ('1' === $request->request->get('preserve')));
        } catch (DBALException $e) {
            $installTool->persistConfig('exampleWebsite', null);
            $installTool->logException($e);

            $this->context['import_error'] = $this->trans('import_exception');

            return null;
        }

        $installTool->persistConfig('exampleWebsite', time());

        return $this->getRedirectResponse();
    }

    /**
     * Creates an admin user.
     *
     * @return Response|RedirectResponse|null The response object
     */
    private function createAdminUser()
    {
        $installTool = $this->container->get('contao.install_tool');

        if (!$installTool->hasTable('tl_user')) {
            $this->context['hide_admin'] = true;

            return null;
        }

        if ($installTool->hasAdminUser()) {
            $this->context['has_admin'] = true;

            return null;
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_admin' !== $request->request->get('FORM_SUBMIT')) {
            return null;
        }

        $username = $request->request->get('username');
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');

        $this->context['admin_username_value'] = $username;
        $this->context['admin_name_value'] = $name;
        $this->context['admin_email_value'] = $email;

        // All fields are mandatory
        if ('' === $username || '' === $name || '' === $email || '' === $password) {
            $this->context['admin_error'] = $this->trans('admin_error');

            return null;
        }

        // Do not allow special characters in usernames
        if (preg_match('/[#()\/<=>]/', $username)) {
            $this->context['admin_username_error'] = $this->trans('admin_error_extnd');

            return null;
        }

        // The username must not contain whitespace characters (see #4006)
        if (false !== strpos($username, ' ')) {
            $this->context['admin_username_error'] = $this->trans('admin_error_no_space');

            return null;
        }

        // Validate the e-mail address (see #6003)
        if ($email !== filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->context['admin_email_error'] = $this->trans('admin_error_email');

            return null;
        }

        // The passwords do not match
        if ($password !== $confirmation) {
            $this->context['admin_password_error'] = $this->trans('admin_error_password_match');

            return null;
        }

        $minlength = $installTool->getConfig('minPasswordLength');

        // The password is too short
        if (Utf8::strlen($password) < $minlength) {
            $this->context['admin_password_error'] = sprintf($this->trans('password_too_short'), $minlength);

            return null;
        }

        // Password and username are the same
        if ($password === $username) {
            $this->context['admin_password_error'] = sprintf($this->trans('admin_error_password_user'), $minlength);

            return null;
        }

        $installTool->persistConfig('adminEmail', $email);

        $installTool->persistAdminUser(
            $username,
            $name,
            $email,
            $password,
            $this->container->getParameter('locale')
        );

        return $this->getRedirectResponse();
    }

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context The context array
     *
     * @return Response The response object
     */
    private function render($name, $context = [])
    {
        return new Response(
            $this->container->get('twig')->render(
                '@ContaoInstallation/' . $name,
                $this->addRequestTokenToContext($context)
            )
        );
    }

    /**
     * Translate a key.
     *
     * @param string $key The translation key
     *
     * @return string The translated string
     */
    private function trans($key)
    {
        return $this->container->get('translator.default')->trans($key);
    }

    /**
     * Returns a redirect response to reload the page.
     *
     * @return RedirectResponse The redirect response
     */
    private function getRedirectResponse()
    {
        return new RedirectResponse($this->container->get('request_stack')->getCurrentRequest()->getRequestUri());
    }

    /**
     * Adds the request token to the template context.
     *
     * @param array $context The context
     *
     * @return array The context with the request token
     */
    private function addRequestTokenToContext(array $context)
    {
        $context['request_token'] = '';

        if (file_exists($this->container->getParameter('kernel.root_dir') . '/config/parameters.yml')) {
            $tokenName = $this->container->getParameter('contao.csrf_token_name');
            $token = $this->container->get('security.csrf.token_manager')->getToken($tokenName);
            $context['request_token'] = $token->getValue();
        }

        return $context;
    }
}
