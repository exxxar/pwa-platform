<?php

namespace App\Services\Tenants;

use App\Http\Resources\TenantUserCollection;
use App\Models\Tenant\TenantUser;
use App\Services\ActionStatus;
use App\Services\ActionStatusCollection;
use App\Services\Basket;
use App\Services\Carbon;
use App\Services\CashBack;
use App\Services\CashBackHistory;
use App\Services\Documents;
use App\Services\Excel;
use App\Services\InputFile;
use App\Services\Order;
use App\Services\ReferralHistory;
use App\Services\Review;
use App\Services\Schema;
use App\Services\Str;
use App\Services\tenantDialogResult;
use App\Services\tenantMedia;
use App\Services\tenantMethods;
use App\Services\tenantNote;
use App\Services\tenantUserResource;
use App\Services\tenantUsersExport;
use App\Services\Transaction;
use App\Services\ValidationException;
use App\Services\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantUserService
{

    public static function call(): self
    {
        return app(self::class);
    }


    /**
     * Универсальный прокси (если вдруг хочешь динамику)
     */
    public static function __callStatic($method, $args)
    {
        return app(self::class)->$method(...$args);
    }

    public function all($needAdmins = false): TenantUserCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $tenantUsers = TenantUser::query()
            ->with(["tenant", "cashBack"])
            ->where("tenant_id", $tenant->id);

    /*    if ($needAdmins)
            $tenantUsers = $tenantUsers
                ->where("is_admin", $needAdmins);*/

        $tenantUsers = $tenantUsers
            ->orderBy("created_at", "DESC")
            ->get();

        return new tenantUserCollection($tenantUsers);
    }


    public function exportTenantUsers($needAdmins = false): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $statistics = $this->all($needAdmins);

        $name = Str::uuid();

        $date = Carbon::now()->format("Y-m-d H-i-s");

        Excel::store(new tenantUsersExport($statistics), "$name.xls", "public", \Maatwebsite\Excel\Excel::XLS);

        tenantMethods::tenant()
            ->wheretenant($this->tenant)
            ->sendDocument($this->tenantUser->telegram_chat_id,
                "Пользователи бота с бонусами",
                InputFile::create(
                    storage_path("app/public") . "/$name.xls",
                    "tenant-users-$date.xls"
                )
            );

        unlink(storage_path("app/public") . "/$name.xls");
    }

    /**
     * @throws HttpException
     */
    public function friends($search = null, $size = null): tenantUserCollection
    {
        if (is_null($this->tenant) || is_null($this->tenantUser))
            throw new HttpException(404, "Не все условия функции выполнены!");


        $size = $size ?? config('app.results_per_page');

        $userIds = ReferralHistory::query()
            ->where("user_sender_id", $this->tenantUser->user_id)
            ->where("tenant_id", $this->tenant->id)
            ->pluck("user_recipient_id");

        $friends = tenantUser::query()
            ->whereIn("user_id", $userIds)
            ->where("tenant_id", $this->tenant->id)
            ->orderBy("created_at", "desc")
            ->paginate($size);

        return new tenantUserCollection($friends);
    }

    public function initCoffee(): array | object
    {
        if (is_null($this->tenant) || is_null($this->tenantUser))
            throw new HttpException(404, "Параметры не соответствуют условию!");

        $config = $this->tenantUser->config ?? [];

        if (!isset($config["coffee"]))
            $config["coffee"] = (object)[
                "count" => 0
            ];

        $this->tenantUser->config = $config;
        $this->tenantUser->save();

        return (object)$config["coffee"];
    }

    public function toggleProductInFavorites($id): array
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $config = $tenantUser->meta ?? [];

        if (in_array($id, $config["favorites"] ?? [])) {
            $config["favorites"] = array_values(array_diff($config["favorites"], [$id]));
        } else {

            if (isset($config["favorites"]))
                $config["favorites"][] = $id;
            else
                $config["favorites"] = [$id];
        }

        $tenantUser->meta = $config;
        $tenantUser->save();

        return $config["favorites"];
    }

    /**
     * @throws HttpException
     */
    public function list($search = null, $size = null, array $params = null): tenantUserCollection
    {
        if (is_null($this->tenant))
            throw new HttpException(404, "Бот не найден!");


        $size = $size ?? config('app.results_per_page');


        $tenantUsers = tenantUser::query()
            ->where("tenant_id", $this->tenant->id);

        if (!is_null($search)) {
            $tenantUsers = $tenantUsers
                ->where(function ($q) use ($search) {
                    $q->orWhere("name", 'like', "%$search%")
                        ->orWhere("phone", 'like', "%$search%")
                        ->orWhere("fio_from_telegram", 'like', "%$search%");
                    //->orWhere("telegram_chat_id", 'like', "%$search%");
                });

        }

        $needAdmins = $params["need_admins"] ?? false;
        $needVip = $params["need_vip"] ?? false;
        $needNotVip = $params["need_not_vip"] ?? false;
        $needDeliveryman = $params["need_deliveryman"] ?? false;
        $needWithPhone = $params["need_with_phone"] ?? false;
        $needWithoutPhone = $params["need_without_phone"] ?? false;

        if ($needAdmins)
            $tenantUsers = $tenantUsers
                ->where("is_admin", true);


        if ($needDeliveryman)
            $tenantUsers = $tenantUsers
                ->where("is_deliveryman", true);

        if ($needVip)
            $tenantUsers = $tenantUsers
                ->where("is_vip", true);

        if ($needNotVip)
            $tenantUsers = $tenantUsers
                ->where("is_vip", false);

        if ($needWithPhone)
            $tenantUsers = $tenantUsers
                ->whereNotNull("phone");

        if ($needWithoutPhone)
            $tenantUsers = $tenantUsers
                ->whereNull("phone");

        $tenantUsers = $tenantUsers
            ->orderBy("created_at", "DESC")
            ->paginate($size);

        return new tenantUserCollection($tenantUsers);
    }

    /**
     * @throws HttpException
     */
    public function resetAlltenantUsers(): void
    {
        if (is_null($this->tenant) || is_null($this->tenantUser))
            throw new HttpException(404, "Параметры не соответствуют условию!");

        if (!$this->tenantUser->is_admin)
            throw new HttpException(403, "Недостаточно прав!");


        Schema::disableForeignKeyConstraints();
        $medias = tenantMedia::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($medias) > 0)
            foreach ($medias as $media)
                $media->forceDelete();

        $notes = tenantNote::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($notes) > 0)
            foreach ($notes as $note)
                $note->forceDelete();

        $baskets = Basket::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($baskets) > 0)
            foreach ($baskets as $basket)
                $basket->forceDelete();

        $cashBacks = CashBack::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($cashBacks) > 0)
            foreach ($cashBacks as $cashBack)
                $cashBack->forceDelete();

        $cashBackHistories = CashBackHistory::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($cashBackHistories) > 0)
            foreach ($cashBackHistories as $history)
                $history->forceDelete();

        $documents = Documents::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($documents) > 0)
            foreach ($documents as $document)
                $document->forceDelete();

        $orders = Order::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($orders) > 0)
            foreach ($orders as $order)
                $order->forceDelete();


        $referrals = ReferralHistory::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($referrals) > 0)
            foreach ($referrals as $referral)
                $referral->forceDelete();

        $reviews = Review::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($reviews) > 0)
            foreach ($reviews as $review)
                $review->forceDelete();

        $transactions = Transaction::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($transactions) > 0)
            foreach ($transactions as $transaction)
                $transaction->forceDelete();

        $statuses = ActionStatus::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($statuses) > 0)
            foreach ($statuses as $status)
                $status->forceDelete();


        $tenantUsers = tenantUser::query()
            ->where("tenant_id", $this->tenant->id)
            ->get();

        if (count($tenantUsers) > 0)
            foreach ($tenantUsers as $tenantUser) {
                $dialogResults = tenantDialogResult::query()
                    ->where("tenant_user_id", $tenantUser->id)
                    ->get();

                if (count($dialogResults) > 0)
                    foreach ($dialogResults as $result)
                        $result->forceDelete();

                $tenantUser->forceDelete();
            }
        Schema::enableForeignKeyConstraints();

    }

    /**
     * @throws HttpException
     */
    public function actionStatusHistoryList($event = "event", $search = null, $size = null): ActionStatusCollection
    {
        if (is_null($this->tenant))
            throw new HttpException(404, "Бот не найден!");


        $size = $size ?? config('app.results_per_page');

        $actions = ActionStatus::query()
            ->with(["slug"])
            ->whereNotNull("data")
            ->where("tenant_id", $this->tenant->id);


        if (!is_null($search)) {

            if ($event == "event")
                $actions = $actions
                    ->whereHas("slug", function ($q) use ($search) {
                        $q->where("command", "like", "%$search%");
                    });

            if ($event == "users") {
                $userIds = tenantUser::query()
                    ->where("name", "like", "%$search%")
                    ->orWhere("fio_from_telegram", "like", "%$search%")
                    ->get()
                    ->pluck("user_id")->toArray();

                $actions = $actions
                    ->orWhereIn("user_id", $userIds);
            }

            if ($event == "phone") {
                $userIds = tenantUser::query()
                    ->where("phone", "like", "%$search%")
                    ->get()
                    ->pluck("user_id")->toArray();

                $actions = $actions
                    ->orWhereIn("user_id", $userIds);
            }

        }


        $actions = $actions
            ->orderBy("updated_at", "asc")
            ->paginate($size);


        return new ActionStatusCollection($actions);
    }

    /**
     * @throws ValidationException
     */
    public function updateProfile(array $data)
    {

        if (is_null($this->tenant) || is_null($this->tenantUser))
            throw new HttpException(404, "Параметры не соответствуют условию!");

        $validator = Validator::make($data, [
            "name" => "required",
            "phone" => "required",
            "email" => "",
            "birthday" => "",
            "city" => "",
            "country" => "",
            "address" => "",
            "sex" => "",

        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $tenantUser = $this->tenantUser;

        $config = $tenantUser->config ?? [];

        $birthday = Carbon::parse($data["birthday"] ?? $tenantUser->birthday ?? Carbon::now())->format("Y-m-d");


        if (!is_null($data["phone"] ?? null)) {
            $vowels = ["(", ")", "-"];
            $filteredPhone = str_replace($vowels, "", $data["phone"]);
            $tenantUser->phone = $filteredPhone;
        } else
            $tenantUser->phone = $tenantUser->phone ?? null;

        $config["need_tenant_mailing"] = (bool)(($data["config"]["need_tenant_mailing"] ?? false));

        $tenantUser->name = $data["name"] ?? $tenantUser->name ?? null;
        $tenantUser->email = $data["email"] ?? $tenantUser->email ?? null;
        $tenantUser->birthday = $birthday;
        $tenantUser->city = $data["city"] ?? $tenantUser->city ?? null;
        $tenantUser->country = $data["country"] ?? $tenantUser->country ?? null;
        $tenantUser->address = $data["address"] ?? $tenantUser->address ?? null;
        $tenantUser->sex = (bool)(($data["sex"] ?? false));
        $tenantUser->age = Carbon::now()->year - Carbon::parse($birthday)
                ->year;

        $tenantUser->config = $config;
        $tenantUser->save();


        $message = sprintf("Ф.И.О: %s\nТелефон: %s\nПочта: %s\nДР: %s\nВозраст: %s\nСтрана: %s\nГород: %s\nАдрес: %s\nПол: %s\nРассылки: %s",
            $tenantUser->name ?? "Не указано",
            $tenantUser->phone ?? "Не указано",
            $tenantUser->email ?? "Не указано",
            $tenantUser->birthday ?? "Не указано",
            $tenantUser->age ?? "Не указано",
            $tenantUser->country ?? "Не указано",
            $tenantUser->city ?? "Не указано",
            $tenantUser->address ?? "Не указано",
            $tenantUser->sex ? "муж" : "жен",
            $config["need_tenant_mailing"] ? "включены" : "отключены"
        );
        tenantMethods::tenant()
            ->wheretenant($this->tenant)
            ->sendMessage(
                $tenantUser->telegram_chat_id,
                "Ваши анкетные данные обновлены:\n $message"
            );

        return new tenantUserResource($tenantUser);
    }

    /**
     * @throws HttpException
     * @throws ValidationException
     */
    public function update(array $data): tenantUserResource
    {
        $validator = Validator::make($data, [
            "id" => "required",
            "is_vip" => "required",
            "is_admin" => "required",
            "is_work" => "required",
            "user_in_location" => "required",
            "name" => "required",
            "phone" => "required",
            "email" => "",
            "birthday" => "",
            "city" => "",
            "country" => "",
            "address" => "",
            "sex" => "",

        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $tenantUser = tenantUser::query()
            ->where("id", $data["id"])
            ->first();


        if (is_null($tenantUser))
            throw new HttpException(404, "Пользователь бота не найден");

        $config = $tenantUser->config ?? [];

        $config["need_tenant_mailing"] = (bool)(($data["config"]["need_tenant_mailing"] ?? false));

        $birthday = Carbon::parse($data["birthday"] ?? $tenantUser->birthday ?? Carbon::now())->format("Y-m-d");

        if (!is_null($data["phone"] ?? null)) {
            $vowels = ["(", ")", "-"];
            $filteredPhone = str_replace($vowels, "", $data["phone"]);
            $tenantUser->phone = $filteredPhone;
        } else
            $tenantUser->phone = $tenantUser->phone ?? null;

        $tenantUser->is_vip = (bool)(($data["is_vip"] ?? false));
        $tenantUser->is_admin = (bool)(($data["is_admin"] ?? false));
        $tenantUser->is_work = (bool)(($data["is_work"] ?? false));
        $tenantUser->is_manager = (bool)(($data["is_manager"] ?? false));
        $tenantUser->user_in_location = (bool)(($data["user_in_location"] ?? false));
        $tenantUser->name = $data["name"] ?? $tenantUser->name ?? null;

        $tenantUser->email = $data["email"] ?? $tenantUser->email ?? null;
        $tenantUser->birthday = $birthday;
        $tenantUser->city = $data["city"] ?? $tenantUser->city ?? null;
        $tenantUser->country = $data["country"] ?? $tenantUser->country ?? null;
        $tenantUser->address = $data["address"] ?? $tenantUser->address ?? null;
        $tenantUser->sex = (bool)(($data["sex"] ?? false));
        $tenantUser->age = Carbon::now()->year - Carbon::parse($birthday)
                ->year;
        $tenantUser->blocked_at = (bool)(($data["is_blocked"] ?? false)) ? Carbon::now() : null;
        $tenantUser->blocked_message = $data["blocked_message"] ?? null;
        $tenantUser->config = $config;
        $tenantUser->save();

        if (!is_null($tenantUser->blocked_at))
            return new tenantUserResource($tenantUser);


        $message = sprintf("Ф.И.О: %s\nТелефон: %s\nПочта: %s\nДР: %s\nВозраст: %s\nСтрана: %s\nГород: %s\nАдрес: %s\nПол: %s\nVip: %s\nAdmin: %s\nЗа работой: %s\nМенеджер: %s\nРассылки: %s",
            $tenantUser->name ?? "Не указано",
            $tenantUser->phone ?? "Не указано",
            $tenantUser->email ?? "Не указано",
            $tenantUser->birthday ?? "Не указано",
            $tenantUser->age ?? "Не указано",
            $tenantUser->country ?? "Не указано",
            $tenantUser->city ?? "Не указано",
            $tenantUser->address ?? "Не указано",
            $tenantUser->sex ? "муж" : "жен",
            $tenantUser->is_vip ? "да" : "нет",
            $tenantUser->is_admin ? "да" : "нет",
            $tenantUser->is_work ? "да" : "нет",
            $tenantUser->is_manager ? "да" : "нет",
            $config["need_tenant_mailing"] ? "включены" : "отключены"
        );
        tenantMethods::tenant()
            ->wheretenant($this->tenant)
            ->sendMessage(
                $tenantUser->telegram_chat_id,
                "Ваши анкетные данные обновлены администратором:\n $message"
            );

        return new tenantUserResource($tenantUser);
    }
}
