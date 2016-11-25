<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Controller;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\Encryption;
use Contao\Environment;
use Contao\InstallationBundle\Config\ParameterDumper;
use Contao\InstallationBundle\Database\AbstractVersionUpdate;
use Contao\InstallationBundle\Database\ConnectionFactory;
use Doctrine\DBAL\DBALException;
use Patchwork\Utf8;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the installation process.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @Route("/contao", defaults={"_scope" = "backend", "_token_check" = true})
 */
class InstallationController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private $context = [
        'has_admin' => false,
        'hide_admin' => false,
    ];

    /**
     * Handles the installation process.
     *
     * @return Response
     *
     * @Route("/install", name="contao_install")
     */
    public function installAction()
    {
        if ($this->container->has('contao.framework')) {
            $this->container->get('contao.framework')->initialize();
        }

        if (null !== ($response = $this->runPostInstallCommands())) {
            return $response;
        }

        $installTool = $this->container->get('contao.install_tool');

        if ($installTool->isLocked()) {
            return $this->render('locked.html.twig');
        }

        if (!$installTool->canWriteFiles()) {
            return $this->render('not_writable.html.twig');
        }

        if ($installTool->shouldAcceptLicense()) {
            return $this->acceptLicense();
        }

        if ('' === $installTool->getConfig('installPassword')) {
            return $this->setPassword();
        }

        if (!$this->container->get('contao.install_tool_user')->isAuthenticated()) {
            return $this->login();
        }

        $this->purgeSymfonyCache();

        if (!$installTool->canConnectToDatabase($this->getContainerParameter('database_name'))) {
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
     *
     * @return Response|null
     */
    public function runPostInstallCommands()
    {
        $rootDir = $this->getContainerParameter('kernel.root_dir');

        $response = $this->runCommand(
            new AssetsInstallCommand(),
            new ArgvInput(['assets:install', '--relative', $rootDir.'/../web'])
        );

        if (null !== $response) {
            return $response;
        }

        // Add the Contao directories
        if (null !== ($response = $this->runCommand(new InstallCommand()))) {
            return $response;
        }

        // Generate the symlinks
        if (null !== ($response = $this->runCommand(new SymlinksCommand()))) {
            return $response;
        }

        // Build the bootstrap.php.cache file
        ScriptHandler::doBuildBootstrap($this->getContainerParameter('kernel.cache_dir').'/../..');

        return null;
    }

    /**
     * Runs a command and returns a response if there was an error.
     *
     * @param ContainerAwareCommand $command
     * @param InputInterface|null   $input
     *
     * @return Response|null
     */
    private function runCommand(ContainerAwareCommand $command, InputInterface $input = null)
    {
        if (null === $input) {
            $input = new ArgvInput([]);
        }

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

        $command->setContainer($this->container);
        $status = $command->run($input, $output);

        if ($status > 0) {
            return $this->render('console.html.twig', [
                'output' => $output->fetch(),
            ]);
        }

        return null;
    }

    /**
     * Renders a form to accept the license.
     *
     * @return Response|RedirectResponse
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
     * @return Response|RedirectResponse
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
     * @return Response|RedirectResponse
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
     * Purges the Symfony cache.
     */
    private function purgeSymfonyCache()
    {
        $fs = new Filesystem();
        $cacheDir = $this->getContainerParameter('kernel.cache_dir');

        $finder = Finder::create()
            ->directories()
            ->depth('==0')
            ->in($cacheDir.'/..')
        ;

        foreach ($finder as $dir) {
            $fs->remove($dir);
        }
    }

    /**
     * Renders a form to set up the database connection.
     *
     * @return Response|RedirectResponse
     */
    private function setUpDatabaseConnection()
    {
        $parameters = [];
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $parameters['parameters'] = [
            'database_host' => $this->getContainerParameter('database_host'),
            'database_port' => $this->getContainerParameter('database_port'),
            'database_user' => $this->getContainerParameter('database_user'),
            'database_password' => $this->getContainerParameter('database_password'),
            'database_name' => $this->getContainerParameter('database_name'),
        ];

        if ('tl_database_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('database.html.twig', $parameters);
        }

        $parameters['parameters'] = [
            'database_host' => $request->request->get('dbHost'),
            'database_port' => $request->request->get('dbPort'),
            'database_user' => $request->request->get('dbUser'),
            'database_password' => $this->getContainerParameter('database_password'),
            'database_name' => $request->request->get('dbName'),
        ];

        if (!preg_match('/^[A-Za-z0-9$_]+$/', $request->request->get('dbName'))) {
            return $this->render('database.html.twig', array_merge(
                $parameters,
                ['database_name_error' => $this->trans('database_name_error')]
            ));
        }

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

        $dumper = new ParameterDumper($this->getContainerParameter('kernel.root_dir'));
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
            ->in(__DIR__.'/../Database')
        ;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $class = 'Contao\InstallationBundle\Database\\'.$file->getBasename('.php');

            /** @var AbstractVersionUpdate $update */
            $update = new $class($this->container->get('database_connection'));

            if ($update instanceof AbstractVersionUpdate && $update->shouldBeRun()) {
                $update->setContainer($this->container);
                $update->run();
            }
        }
    }

    /**
     * Renders a form to adjust the database tables.
     *
     * @return Response|RedirectResponse|null
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
     * @return Response|RedirectResponse|null
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
     * @return Response|RedirectResponse|null
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
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== $email) {
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
            $request->getLocale()
        );

        return $this->getRedirectResponse();
    }

    /**
     * Renders a template.
     *
     * @param string $name
     * @param array  $context
     *
     * @return Response
     */
    private function render($name, $context = [])
    {
        return new Response(
            $this->container->get('twig')->render(
                '@ContaoInstallation/'.$name,
                $this->addDefaultsToContext($context)
            )
        );
    }

    /**
     * Translate a key.
     *
     * @param string $key
     *
     * @return string
     */
    private function trans($key)
    {
        return $this->container->get('translator')->trans($key);
    }

    /**
     * Returns a redirect response to reload the page.
     *
     * @return RedirectResponse
     */
    private function getRedirectResponse()
    {
        return new RedirectResponse($this->container->get('request_stack')->getCurrentRequest()->getRequestUri());
    }

    /**
     * Adds the default values to the context.
     *
     * @param array $context
     *
     * @return array
     */
    private function addDefaultsToContext(array $context)
    {
        $context = array_merge($this->context, $context);

        if (!isset($context['request_token'])) {
            $context['request_token'] = $this->getRequestToken();
        }

        if (!isset($context['language'])) {
            $context['language'] = $this->container->get('translator')->getLocale();
        }

        if (!isset($context['ua'])) {
            $context['ua'] = Environment::get('agent')->class;
        }

        if (!isset($context['path'])) {
            $context['path'] = $this->container->get('request_stack')->getCurrentRequest()->getBasePath();
        }

        return $context;
    }

    /**
     * Returns the request token.
     *
     * @return string
     */
    private function getRequestToken()
    {
        if (!$this->container->hasParameter('contao.csrf_token_name')) {
            return '';
        }

        return $this->container
            ->get('security.csrf.token_manager')
            ->getToken($this->getContainerParameter('contao.csrf_token_name'))
            ->getValue()
        ;
    }

    /**
     * Returns a parameter from the container.
     *
     * @param string $name
     *
     * @return mixed
     */
    private function getContainerParameter($name)
    {
        if ($this->container->hasParameter($name)) {
            return $this->container->getParameter($name);
        }

        return null;
    }
}
