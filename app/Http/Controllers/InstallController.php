<?php

namespace App\Http\Controllers;

use App\DTO\InstallAdminCreateData;
use App\Helpers\StringHelper;
use App\Http\Requests\Frontend\InstallAdminRequest;
use App\Http\Requests\Frontend\InstallRequest;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class InstallController extends Controller
{
    private const APP_VERSION = '7.1.0';

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function index(): View
    {
        return view('install.start');
    }

    public function requirements(): View
    {
        return view('install.requirements', [
            'requirements' => $this->getRequirements(),
            'allLoaded' => $this->allRequirementsLoaded(),
        ]);
    }

    public function permissions(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return to_route('install.requirements');
        }

        return view('install.permissions', [
            'folders' => $this->getPermissions(),
            'allGranted' => $this->allPermissionsGranted(),
        ]);
    }

    public function database(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return to_route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return to_route('install.permissions');
        }

        return view('install.database');
    }

    /**
     * @param InstallRequest $request
     * @return RedirectResponse
     */
    public function installation(InstallRequest $request): RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return to_route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return to_route('install.permissions');
        }

        $dbCredentials = $request->validated();

        if (!$this->dbCredentialsAreValid($dbCredentials)) {
            return to_route('install.database')
                ->withInput()
                ->withErrors(__('install.str.connection_to_database_cannot_be_established'));
        }

        Session::put('install.db_credentials', $dbCredentials);

        return to_route('install.admin');
    }

    public function admin(): View
    {
        return view('install.installation');
    }

    /**
     * @param InstallAdminRequest $request
     * @return RedirectResponse
     */
    public function install(InstallAdminRequest $request): RedirectResponse
    {
        try {
            $db = Session::pull('install.db_credentials');

            if (!is_array($db) || empty($db)) {
                return to_route('install.database')
                    ->withErrors(__('install.str.connection_to_database_cannot_be_established'));
            }

            copy(base_path('.env.example'), base_path('.env'));

            $this->reloadEnv();
            $this->writeEnvironmentFile($db);
            $this->setDatabaseCredentials($db);

            config(['app.debug' => true]);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('key:generate', ['--force' => true]);

            $this->userRepository->createAdminFromInstall(
                new InstallAdminCreateData(
                    name: 'admin',
                    login: $request->input('login'),
                    role: 'admin',
                    password: $request->input('password'),
                )
            );

            return to_route('install.complete');
        } catch (\Throwable $e) {
            @unlink(base_path('.env'));

            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return to_route('install.error');
        }
    }

    public function complete(): View
    {
        return view('install.complete');
    }

    public function error(): View
    {
        return view('install.error');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = (string) $request->input('action');

        if ($action !== 'change_lng') {
            return response()->json(['result' => false], 400);
        }

        $locale = (string) $request->input('locale');

        if ($locale !== '' && in_array($locale, Config::get('app.locales', []), true)) {
            Cookie::queue(
                Cookie::forever('lang', $locale)
            );
        }

        return response()->json(['result' => true]);
    }

    private function reloadEnv(): void
    {
        (new LoadEnvironmentVariables())->bootstrap(app());
    }

    private function getRequirements(): array
    {
        $requirements = [
            'PHP Version (>= 8.2.0)' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'Zip' => extension_loaded('zip'),
            'iconv' => extension_loaded('iconv'),
            'PDO Extension' => extension_loaded('PDO'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'JSON PHP Extension' => extension_loaded('json'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
        ];

        if (extension_loaded('xdebug')) {
            $requirements['Xdebug Max Nesting Level (>= 500)'] = (int) ini_get('xdebug.max_nesting_level') >= 500;
        }

        return $requirements;
    }

    private function allRequirementsLoaded(): bool
    {
        foreach ($this->getRequirements() as $loaded) {
            if ($loaded === false) {
                return false;
            }
        }

        return true;
    }

    private function getPermissions(): array
    {
        return [
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'Base Directory' => is_writable(base_path()),
        ];
    }

    private function allPermissionsGranted(): bool
    {
        foreach ($this->getPermissions() as $granted) {
            if ($granted === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $credentials
     * @return bool
     */
    private function dbCredentialsAreValid(array $credentials): bool
    {
        $this->setDatabaseCredentials($credentials);

        try {
            DB::purge(config('database.default'));
            DB::reconnect(config('database.default'));
            DB::statement('SHOW TABLES');
        } catch (\Throwable $e) {
            Log::info($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param array $credentials
     * @return void
     */
    private function setDatabaseCredentials(array $credentials): void
    {
        $default = config('database.default');

        config([
            "database.connections.{$default}.host" => $credentials['host'],
            "database.connections.{$default}.database" => $credentials['database'],
            "database.connections.{$default}.username" => $credentials['username'],
            "database.connections.{$default}.password" => $credentials['password'],
            "database.connections.{$default}.prefix" => $credentials['prefix'] ?? '',
        ]);
    }

    /**
     * @param array $db
     * @return void
     */
    private function writeEnvironmentFile(array $db): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException('Unable to read .env file.');
        }

        $replacements = [
            'DB_HOST' => $db['host'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'DB_PREFIX' => $db['prefix'] ?? '',
            'VERSION' => self::APP_VERSION,
            'APP_URL' => StringHelper::getUrl(),
        ];

        foreach ($replacements as $key => $value) {
            $content = $this->replaceEnvValue($content, $key, (string) $value);
        }

        file_put_contents($path, $content);
    }

    /**
     * @param string $content
     * @param string $key
     * @param string $value
     * @return string
     */
    private function replaceEnvValue(string $content, string $key, string $value): string
    {
        $escapedValue = str_replace('"', '\"', $value);
        $replacement = sprintf('%s="%s"', $key, $escapedValue);
        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replacement, $content) ?? $content;
        }

        return rtrim($content) . PHP_EOL . $replacement . PHP_EOL;
    }
}
