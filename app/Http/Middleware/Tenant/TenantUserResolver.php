<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantMessage;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TenantUserResolver
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->tenant;
        $tempUser = Auth::guard('tenant')->user();

        if (!is_null($tempUser)) {
            return $next($request);
        }

        $uuid = $request->session()->get('user_uuid');
        $header = $request->header('X-Integration-Auth');

        if ($header) {
            $decoded = base64_decode($header);
            [$tenantId, $uuid] = explode(':', $decoded, 2);
        }

        $user = null;

        if ($uuid) {
            $user = TenantUser::query()
                ->with(["roles"])
                ->where('uuid', $uuid)
                ->first();
        }

        if (!$user) {
            $role = TenantRole::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'guest',
            ]);

            // 🆕 1. БОЛЬШОЙ МАССИВ КРАСИВЫХ И ГРАМОТНЫХ СОЧЕТАНИЙ
            $identities = [
                // 🦊 Лесные и милые
                'Хитрый Енот', 'Сонный Медведь', 'Мудрая Сова', 'Пятнистый Олень',
                'Колючий Ёж', 'Ласковый Бобёр', 'Любопытный Кот', 'Верный Пёс',
                'Шустрый Заяц', 'Полосатый Барсук', 'Пушистая Белка', 'Тихий Волк',
                'Игривый Лисёнок', 'Заботливый Сурок', 'Весёлый Бурундук',

                // 🦅 Птицы и небо
                'Гордый Орёл', 'Пёстрый Попугай', 'Грациозный Лебедь', 'Быстрый Сокол',
                'Яркий Фламинго', 'Загадочный Ворон', 'Нежный Голубь', 'Громкий Дятел',
                'Свободный Альбатрос', 'Зоркий Ястреб', 'Певчий Соловей',

                // 🐟 Вода и глубины
                'Шустрый Карась', 'Мудрый Дельфин', 'Полосатый Скат', 'Гигантский Кит',
                'Хитрая Щука', 'Панцирный Краб', 'Переливчатая Рыбка', 'Быстрая Акула',
                'Спокойный Карп', 'Речной Выдра', 'Морской Конёк',

                // 🌴 Экзотика и джунгли
                'Ловкий Шимпанзе', 'Грациозная Пантера', 'Высокий Жираф', 'Сонная Панда',
                'Игривый Кенгуру', 'Пятнистый Гепард', 'Медлительный Ленивец', 'Яркий Хамелеон',
                'Могучий Слон', 'Полосатый Тигр', 'Грозный Носорог',

                // 🦕 Немного фантазии и атмосферы
                'Космический Кот', 'Пещерный Медведь', 'Лесной Дух', 'Речной Страж',
                'Горный Козёл', 'Степной Орлан', 'Домашний Дракон', 'Ночной Странник',
                'Сказочный Единорог', 'Хранитель Леса', 'Ветреный Странник'
            ];

            // 🆕 2. ВЫБИРАЕМ СЛУЧАЙНОЕ СОЧЕТАНИЕ
            $randomIdentity = $identities[array_rand($identities)];

            // Формируем имя. Вариант "Гость • Хитрый Енот" выглядит очень стильно в интерфейсе.
            // Если хотите строго как в примере, замените на: 'Гость ' . $randomIdentity
            $guestName = 'Гость • ' . $randomIdentity;

            // Создаем нового гостя
            $user = TenantUser::query()->create([
                'tenant_id' => $tenant->id,
                'name'      => $guestName,
                'uuid'      => (string) Str::uuid(),
            ]);

            $dialog = TenantDialog::query()->create([
                'tenant_id'      => $tenant->id,
                'tenant_user_id' => $user->id,
                'type'           => "system",
                'title'          => "Сообщение от администрации"
            ]);

            // 🆕 3. ПЕРСОНАЛИЗИРОВАННОЕ ПРИВЕТСТВИЕ
            // Разбиваем имя, чтобы обратиться к "зверю" напрямую (убираем "Гость • ")
            $animalName = str_replace('Гость • ', '', $guestName);

            $message = TenantMessage::query()->create([
                'tenant_id' => $tenant->id,
                'dialog_id' => $dialog->id,
                'message'   => "Приветствуем вас в системе, <b>{$animalName}</b>! 🐾\nРады видеть вас среди наших гостей."
            ]);

            $user->roles()->syncWithoutDetaching([
                $role->id => [
                    'tenant_id' => $user->tenant_id,
                ]
            ]);

            // сохраняем в сессию
            $request->session()->put('user_uuid', $user->uuid);
        }

        // авторизуем гостя на время сессии
        Auth::guard('tenant')->login($user);

        return $next($request);
    }

}
