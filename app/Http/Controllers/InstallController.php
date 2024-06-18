<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
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
     * @return View
     */
    public function permissions(): View
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
    public function databaseInfo(): View|RedirectResponse
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
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function installation(Request $request): RedirectResponse|View
    {
        $rules = [
            'host' => 'required',
            'username' => 'required',
            'database' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

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
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function admin(Request $request)
    {
        return view('install.installation');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function install(Request $request)
    {
        $rules = [
            'login' => 'required',
            'password' => 'required|min:4',
            'confirm_password' => 'required|min:4|same:password',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

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
            $env = str_replace('VERSION=', 'VERSION="6.1.3"', $env);

            file_put_contents($path, $env);

            $this->setDatabaseCredentials($db);
            config(['app.debug' => true]);

            Artisan::call('key:generate', ['--force' => true]);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

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
     *
     */
    private function reloadEnv()
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
    private function getPermissions()
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
    private function allPermissionsGranted()
    {
        $allGranted = true;

        foreach ($this->getPermissions() as $permission => $granted) {
            if ($granted == false) {
                $allGranted = false;
            }
        }

        return $allGranted;
    }

    /**
     * @param $credentials
     * @return bool
     */
    private function dbCredentialsAreValid($credentials): bool
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
     * @param $credentials
     */
    private function setDatabaseCredentials($credentials)
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
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function ajax(Request $request)
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

                    return ResponseHelpers::jsonResponse([
                        'result' => true
                    ]);

                    break;
            }
        }
    }
}
