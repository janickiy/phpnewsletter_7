<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\User;
use App\Http\Requests\Frontend\InstallRequest;
use App\Http\Requests\Frontend\InstallAdminRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Session;
use Hash;
use Artisan;
use Cookie;
use DB;
use Log;
use Config;

class InstallController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('install.start');
    }

    /**
     * @return View
     */
    public function requirements(): View
    {
        $requirements = $this->getRequirements();
        $allLoaded = $this->allRequirementsLoaded();

        return view('install.requirements', compact('requirements', 'allLoaded'));
    }

    /**
     * @return View|RedirectResponse
     */
    public function permissions(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        $folders = $this->getPermissions();
        $allGranted = $this->allPermissionsGranted();

        return view('install.permissions', compact('folders', 'allGranted'));
    }

    /**
     * @return View|RedirectResponse
     */
    public function database(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        return view('install.database');
    }

    /**
     * @param InstallRequest $request
     * @return RedirectResponse|View
     */
    public function installation(InstallRequest $request): RedirectResponse|View
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        $dbCredentials = $request->only('host', 'username', 'password', 'database', 'prefix');

        if (!$this->dbCredentialsAreValid($dbCredentials)) {
            return redirect()->route('install.database')
                ->withInput()
                ->withErrors(trans('install.str.connection_to_database_cannot_be_established'));
        }

        Session::put('install.db_credentials', $dbCredentials);

        return redirect()->route('install.admin');
    }

    /**
     * @return View
     */
    public function admin(): View
    {
        return view('install.installation');
    }

    /**
     * @param InstallRequest $request
     * @return RedirectResponse
     */
    public function install(InstallAdminRequest $request): RedirectResponse
    {
        try {
            $db = Session::pull('install.db_credentials');

            copy(base_path('.env.example'), base_path('.env'));

            $this->reloadEnv();

            $path = base_path('.env');
            $env = file_get_contents($path);
            $env = str_replace('DB_HOST=' . env('DB_HOST'), 'DB_HOST=' . $db['host'], $env);
            $env = str_replace('DB_DATABASE=' . env('DB_DATABASE'), 'DB_DATABASE=' . $db['database'], $env);
            $env = str_replace('DB_USERNAME=' . env('DB_USERNAME'), 'DB_USERNAME=' . $db['username'], $env);
            $env = str_replace('DB_PASSWORD=' . env('DB_PASSWORD'), 'DB_PASSWORD="' . $db['password'] . '"', $env);
            $env = str_replace('VERSION=', 'VERSION="7.0.0"', $env);
            $env = str_replace('APP_URL=', 'APP_URL=' . StringHelper::getUrl(), $env);

            file_put_contents($path, $env);

            $this->setDatabaseCredentials($db);
            config(['app.debug' => true]);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('key:generate', ['--force' => true]);

            User::create(['name' => 'admin', 'login' => $request->input('login'), 'role' => 'admin', 'password' => Hash::make($request->input('password'))]);

            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            @unlink(base_path('.env'));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return redirect()->route('install.error');
        }
    }

    /**
     * @return void
     */
    private function reloadEnv(): void
    {
        (new LoadEnvironmentVariables)->bootstrap(app());
    }

    /**
     * @return View
     */
    public function complete(): View
    {
        return view('install.complete');
    }

    /**
     * @return View
     */
    public function error(): View
    {
        return view('install.error');
    }

    /**
     * @return array
     */
    private function getRequirements(): array
    {
        $requirements = [
            'PHP Version (>= 8.2.0)' => version_compare(phpversion(), '8.2.0', '>='),
            'Zip' => extension_loaded('zip'),
            'iconv' => extension_loaded("iconv"),
            'PDO Extension' => extension_loaded('PDO'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'mbstring' => extension_loaded('mbstring'),
            'JSON PHP Extension' => extension_loaded('json'),
            'Fileinfo Extension' => extension_loaded('fileinfo')
        ];

        if (extension_loaded('xdebug')) {
            $requirements['Xdebug Max Nesting Level (>= 500)'] = (int)ini_get('xdebug.max_nesting_level') >= 500;
        }

        return $requirements;
    }

    /**
     * @return bool
     */
    private function allRequirementsLoaded(): bool
    {
        $allLoaded = true;

        foreach ($this->getRequirements() as $loaded) {
            if ($loaded === false) {
                $allLoaded = false;
            }
        }

        return $allLoaded;
    }

    /**
     * @return array
     */
    private function getPermissions(): array
    {
        return [
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'Base Directory' => is_writable(base_path('')),
        ];
    }

    /**
     * @return bool
     */
    private function allPermissionsGranted(): bool
    {
        $allGranted = true;

        foreach ($this->getPermissions() as $permission => $granted) {
            if ($granted === false) {
                $allGranted = false;
            }
        }

        return $allGranted;
    }

    /**
     * @param array $credentials
     * @return bool
     */
    private function dbCredentialsAreValid(array $credentials): bool
    {
        $this->setDatabaseCredentials($credentials);

        try {
            DB::statement("SHOW TABLES");
        } catch (\Exception $e) {
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
            "database.connections.{$default}.password" => $credentials['password']
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function ajax(Request $request): JsonResponse
    {
        if ($request->input('action')) {
            switch ($request->input('action')) {
                case 'change_lng':

                    if ($request->input('locale')) {
                        if (in_array($request->input('locale'), Config::get('app.locales'))) {
                            Cookie::queue(
                                Cookie::forever('lang', $request->input('locale')));
                        }
                    }

                    return response()->json(['result' => true]);
            }
        }
    }
}
