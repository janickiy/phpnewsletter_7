<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Macros;
use App\Models\Redirect;
use App\Models\Schedule;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocalDemoSeeder extends Seeder
{
    private const CATEGORIES = [
        'Category 1',
        'Category 2',
        'Category 3',
        'Product Updates',
        'Promotions',
        'Blog Digest',
        'Events',
        'VIP Customers',
        'New Users',
    ];

    private const TEMPLATES = [
        [
            'name' => 'Welcome email for new subscribers',
            'prior' => 0,
            'body' => '<h2>Hello, %NAME%!</h2><p>Thanks for subscribing to PHP Newsletter. This email collects the most useful resources to help you get started quickly.</p><p><a href="https://example.test/start">Open the starter guide</a></p>',
        ],
        [
            'name' => 'July product digest',
            'prior' => 1,
            'body' => '<h2>Main updates this month</h2><p>New templates, improved subscriber imports, and clearer mailing statistics are now available in the control panel.</p>',
        ],
        [
            'name' => 'Promo campaign: summer discount',
            'prior' => 1,
            'body' => '<h2>Summer promotion</h2><p>A special offer is available for active subscribers until the end of the week. Use promo code <strong>SUMMER25</strong>.</p>',
        ],
        [
            'name' => 'Webinar invitation',
            'prior' => 0,
            'body' => '<h2>Email marketing webinar</h2><p>We will show how to segment your audience, prepare messages, and track results without unnecessary routine work.</p>',
        ],
        [
            'name' => 'Inactive subscriber reactivation',
            'prior' => 2,
            'body' => '<h2>We missed you</h2><p>We have not seen you among active readers for a while. Here is a short list of resources worth checking out.</p>',
        ],
    ];

    private const SCHEDULES = [
        ['event_name' => 'July digest: sent', 'day_offset' => -12, 'template' => 'July product digest'],
        ['event_name' => 'Summer promo campaign: sent', 'day_offset' => -7, 'template' => 'Promo campaign: summer discount'],
        ['event_name' => 'Welcome series: sent', 'day_offset' => -3, 'template' => 'Welcome email for new subscribers'],
        ['event_name' => 'Email marketing webinar', 'day_offset' => 2, 'template' => 'Webinar invitation'],
        ['event_name' => 'Inactive subscriber reactivation', 'day_offset' => 5, 'template' => 'Inactive subscriber reactivation'],
    ];

    /**
     * Fill a local installation without resetting or overwriting existing data.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                CharsetSeeder::class,
                CategorySeeder::class,
                SettingsSeeder::class,
                UsersSeeder::class,
            ]);

            $categories = $this->seedCategories();
            $templates = $this->seedTemplates();
            $subscribers = $this->seedSubscribers();

            $this->seedSubscriptions($subscribers, $categories);
            $this->seedMacros();
            $this->seedSmtpServers();
            $this->seedSchedulesAndDeliveryLog($templates, $subscribers, $categories);
            $this->seedRedirects($subscribers);
        });

        $this->command?->info('Local site has been seeded with demo data. Existing rows were left unchanged.');
    }

    /**
     * @return Collection<int, Category>
     */
    private function seedCategories(): Collection
    {
        foreach (self::CATEGORIES as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }

        return Category::query()->whereIn('name', self::CATEGORIES)->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Templates>
     */
    private function seedTemplates(): Collection
    {
        foreach (self::TEMPLATES as $template) {
            Templates::query()->firstOrCreate(
                ['name' => $template['name']],
                ['body' => $template['body'], 'prior' => $template['prior']],
            );
        }

        return Templates::query()
            ->whereIn('name', array_column(self::TEMPLATES, 'name'))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Subscribers>
     */
    private function seedSubscribers(): Collection
    {
        $faker = fake('en_US');
        $faker->seed(1200);
        $emails = [];
        $now = now();

        for ($index = 0; $index < 200; $index++) {
            $email = sprintf('demo.subscriber%03d@phpnewsletter.test', $index + 1);
            $createdAt = $now->copy()
                ->subDays(($index % 75) + 1)
                ->subMinutes(($index * 37) % 720);

            Subscribers::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $faker->name(),
                    'active' => $index % 11 === 0 ? 0 : 1,
                    'token' => substr(hash('sha256', $email), 0, 32),
                    'timeSent' => $index % 5 === 0 ? $createdAt->copy()->addDays(($index % 8) + 1) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ],
            );

            $emails[] = $email;
        }

        return Subscribers::query()->whereIn('email', $emails)->orderBy('id')->get();
    }

    /**
     * @param  Collection<int, Subscribers>  $subscribers
     * @param  Collection<int, Category>  $categories
     */
    private function seedSubscriptions(Collection $subscribers, Collection $categories): void
    {
        $rows = [];
        $categoryIds = $categories->pluck('id')->values();

        foreach ($subscribers->values() as $index => $subscriber) {
            $categoryCount = ($index % 3) + 1;
            $offset = $index % $categoryIds->count();

            for ($position = 0; $position < $categoryCount; $position++) {
                $rows[] = [
                    'subscriber_id' => $subscriber->id,
                    'category_id' => $categoryIds[($offset + $position) % $categoryIds->count()],
                ];
            }
        }

        DB::table('subscriptions')->insertOrIgnore($rows);
    }

    private function seedMacros(): void
    {
        $macros = [
            ['name' => '%NAME%', 'value' => 'Subscriber name', 'type' => Macros::TYPE_TAGS],
            ['name' => '%EMAIL%', 'value' => 'email@example.test', 'type' => Macros::TYPE_EMAIL],
            ['name' => '%UNSUB%', 'value' => 'https://example.test/unsubscribe', 'type' => Macros::TYPE_URL],
            ['name' => '%CONFIRM%', 'value' => 'https://example.test/confirm', 'type' => Macros::TYPE_URL],
            ['name' => '%PROMO%', 'value' => 'SUMMER25 WINBACK10 VIP30', 'type' => Macros::TYPE_WRAP_PHRASE],
        ];

        foreach ($macros as $macro) {
            Macros::query()->firstOrCreate(
                ['name' => $macro['name']],
                ['value' => $macro['value'], 'type' => $macro['type']],
            );
        }
    }

    private function seedSmtpServers(): void
    {
        $servers = [
            [
                'host' => 'smtp.mailtrap.local',
                'username' => 'mailtrap-demo',
                'email' => 'newsletter@example.test',
                'password' => 'secret',
                'port' => 2525,
                'authentication' => Smtp::AUTH_LOGIN,
                'secure' => Smtp::SECURE_TLS,
                'timeout' => 30,
                'active' => 1,
            ],
            [
                'host' => 'smtp.backup.local',
                'username' => 'backup-demo',
                'email' => 'backup@example.test',
                'password' => 'secret',
                'port' => 587,
                'authentication' => Smtp::AUTH_PLAIN,
                'secure' => Smtp::SECURE_TLS,
                'timeout' => 45,
                'active' => 1,
            ],
            [
                'host' => 'smtp.paused.local',
                'username' => 'paused-demo',
                'email' => 'paused@example.test',
                'password' => 'secret',
                'port' => 465,
                'authentication' => Smtp::AUTH_LOGIN,
                'secure' => Smtp::SECURE_SSL,
                'timeout' => 30,
                'active' => 0,
            ],
        ];

        foreach ($servers as $server) {
            Smtp::query()->firstOrCreate(
                ['host' => $server['host'], 'username' => $server['username']],
                collect($server)->except(['host', 'username'])->all(),
            );
        }
    }

    /**
     * @param  Collection<int, Templates>  $templates
     * @param  Collection<int, Subscribers>  $subscribers
     * @param  Collection<int, Category>  $categories
     */
    private function seedSchedulesAndDeliveryLog(
        Collection $templates,
        Collection $subscribers,
        Collection $categories,
    ): void {
        $categoryIds = $categories->pluck('id')->values();

        foreach (self::SCHEDULES as $index => $row) {
            /** @var Templates $template */
            $template = $templates->firstWhere('name', $row['template']);
            $start = now()->startOfDay()->addDays($row['day_offset'])->addHours(10 + $index);
            $schedule = Schedule::query()->firstOrCreate(
                ['event_name' => $row['event_name']],
                [
                    'event_start' => $start,
                    'event_end' => $start->copy()->addMinutes(45),
                    'template_id' => $template->id,
                ],
            );

            for ($position = 0; $position < 2; $position++) {
                DB::table('schedule_category')->insertOrIgnore([
                    'schedule_id' => $schedule->id,
                    'category_id' => $categoryIds[($index + $position) % $categoryIds->count()],
                ]);
            }

            if ($row['day_offset'] < 0) {
                $this->seedDeliveryRows($schedule, $template, $subscribers, $index);
            }
        }
    }

    /**
     * @param  Collection<int, Subscribers>  $subscribers
     */
    private function seedDeliveryRows(
        Schedule $schedule,
        Templates $template,
        Collection $subscribers,
        int $batchIndex,
    ): void {
        $sample = $subscribers->slice($batchIndex * 30, 45);

        if ($sample->count() < 45) {
            $sample = $subscribers->take(45);
        }

        $missingSubscribers = $sample->reject(fn (Subscribers $subscriber): bool => DB::table('ready_sent')
            ->where('schedule_id', $schedule->id)
            ->where('subscriber_id', $subscriber->id)
            ->where('template_id', $template->id)
            ->exists());

        if ($missingSubscribers->isEmpty()) {
            return;
        }

        $logId = DB::table('ready_sent')
            ->where('schedule_id', $schedule->id)
            ->whereNotNull('log_id')
            ->value('log_id');

        $logId ??= DB::table('logs')->insertGetId([
            'time' => Carbon::parse($schedule->event_start),
        ]);

        $rows = [];

        foreach ($missingSubscribers->values() as $index => $subscriber) {
            $success = $index % 9 === 0 ? 0 : 1;
            $createdAt = Carbon::parse($schedule->event_start)->addMinutes($index);

            $rows[] = [
                'subscriber_id' => $subscriber->id,
                'email' => $subscriber->email,
                'template_id' => $template->id,
                'template' => $template->name,
                'success' => $success,
                'errorMsg' => $success ? null : 'SMTP timeout during demo delivery',
                'readMail' => $success && $index % 3 !== 0 ? 1 : null,
                'schedule_id' => $schedule->id,
                'log_id' => $logId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('ready_sent')->insert($rows);
    }

    /**
     * @param  Collection<int, Subscribers>  $subscribers
     */
    private function seedRedirects(Collection $subscribers): void
    {
        $urls = [
            'https://example.test/start',
            'https://example.test/pricing',
            'https://example.test/webinar',
            'https://example.test/blog/deliverability',
            'https://example.test/promo/summer',
        ];

        foreach ($subscribers->take(70)->values() as $index => $subscriber) {
            Redirect::query()->firstOrCreate([
                'url' => $urls[$index % count($urls)],
                'email' => $subscriber->email,
            ]);
        }
    }
}
