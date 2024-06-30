<div class="steps">
    <ul>
        <li>
            <a class="{{ isset($steps['welcome']) ? $steps['welcome'] : '' }}">
                <div class="stepNumber"><i class="fa fa-home"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.welcome') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ isset($steps['requirements']) ? $steps['requirements'] : '' }}">
                <div class="stepNumber"><i class="fa fa-list"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.system_requirements') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ isset($steps['permissions']) ? $steps['permissions'] : '' }}">
                <div class="stepNumber"><i class="fa fa-lock"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.permissions') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ isset($steps['database']) ? $steps['database'] : '' }}">
                <div class="stepNumber"><i class="fa fa-database"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.database_info') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ isset($steps['installation']) ? $steps['installation'] : '' }}">
                <div class="stepNumber"><i class="fa fa-terminal"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.installation') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ isset($steps['complete']) ? $steps['complete'] : '' }}">
                <div class="stepNumber"><i class="fa fa-flag-checkered"></i></div>
                <span class="stepDesc text-small">{{ trans('install.str.complete') }}</span>
            </a>
        </li>
    </ul>
</div>
