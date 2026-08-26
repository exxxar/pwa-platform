<?php

namespace App\Services\Tenants;

use App\Http\Resources\BasketCollection;
use App\Http\Resources\BasketResource;
use App\Http\Resources\TableCollection;
use App\Http\Resources\TableResource;
use App\Http\Resources\TenantUserResource;
use App\Models\Tenant\Basket;
use App\Models\Tenant\Table;
use App\Services\HttpException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\ExceptionHttpException;

class TableService
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

    public function sendOrderToChat($tableId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant", "clients"])
            ->where("tenant_id", $tenant->id)
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Ошибка выбора столика!");

        $basket = Basket::query()
            ->where("tenant_id", $tenant->id)
            ->where("table_id", $tableId)
            ->whereNull("ordered_at")
            ->get();

        $summaryPrice = 0;
        $summaryCount = 0;
        $description = "Ваш столик <b>№$table->number</b>. Ваш текущий заказ состоит из:\n\n<b>Основной заказ</b>:\n";

        foreach ($basket as $basketItem) {
            $product = $basketItem->product ?? null;
            $collection = $basketItem->collection ?? null;
            $count = $basketItem->count ?? 0;
            $price = 0;

            if (!is_null($product)) {
                $price = $product->price ?? 0;//* $count;
                $description .= "$product->title x$count = $price руб.,\n";
                $price = $price * $count;
            }

            if (!is_null($collection)) {
                $collectionTitles = "";

                $params = is_null($item->params ?? null) ? null : (object)$basketItem->params;

                foreach (($collection->products ?? []) as $basketProduct) {
                    if (!in_array($basketProduct->id, $params->ids ?? []))
                        continue;

                    $collectionTitles .= "-" . $basketProduct->name . "\n";
                    $price += $product->price ?? 0;
                }

                $description .= "Коллекция $collection->name x$count = $price руб.,\n";
                $price = $price * $basketItem->count;
            }


            $summaryCount += $count;
            $summaryPrice += $price;
        }

        $additionalServices = $table->additional_services ?? [];

        if (count($additionalServices) > 0) {
            $description .= "\n<b>Дополнительные платные сервисы:</b>\n";
            foreach ($additionalServices as $serviceItem) {
                $serviceItem = (object)$serviceItem;
                $price = $serviceItem->price ?? 0;//* $count;
                $description .= "$serviceItem->title x1 = $price,\n";
                $summaryCount += 1;
                $summaryPrice += $price;
            }
        }

        $description .= "\nИтого: <b>$summaryPrice руб.</b>";

        MessageService::call()
            ->sendMessage([
                "message" => $description
            ]);
    }

    public function storeAdditionalService($tableId, $services)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant", "clients"])
            ->where("tenant_id", $tenant->id)
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Ошибка выбора столика!");

        $table->additional_services = $services ?? [];
        $table->save();

        $table->refresh();

        return new TableResource($table);
    }

    public function changeBasketStatus($tableId, $type = 0)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $baskets = Basket::query()
            ->with(["collection", "product"])
            ->where("table_id", $tableId)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->get();

        foreach ($baskets as $basket) {
            $basket->table_approved_at = $type == 0 ? Carbon::now() : null;
            $basket->save();
        }

        return new BasketCollection($baskets);
    }

    public function changeProductStatus($basketId): BasketResource
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $basket = Basket::query()
            ->with(["collection", "product"])
            ->where("id", $basketId)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->first();

        $basket->table_approved_at = is_null($basket->table_approved_at) ? Carbon::now() : null;
        $basket->save();

        return new BasketResource($basket);
    }

    /**
     * @throws ValidationException
     */
    public function tablePay(array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "table_id" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);


        $tableId = $data["table_id"];

        $table = Table::query()
            ->with(["creator", "officiant", "clients"])
            ->where("tenant_id", $tenant->id)
            ->where("id", $tableId)
            ->first();

        return PaymentService::call()
            ->sbpTablePayment($data, $table);
    }

    public function getFullTableData($tableId): object
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant", "clients"])
            ->where("tenant_id", $tenant->id)
            ->where("id", $tableId)
            ->first();

        $baskets = Basket::query()
            ->with(["collection", "product", "user"])
            ->where("table_id", $tableId)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->get();


        $clientBaskets = [];
        foreach ($table->clients as $client) {
            $clientBaskets[] = (object)[
                "id" => $client->id ?? null,
                "name" => $client->name ?? $client->fio_from_telegram ?? '-',
                "summary_price" => 0,
                "summary_count" => 0,
                "basket" => [],
            ];
        }


        $allSummaryPrice = 0;
        $allSummaryCount = 0;
        foreach ($baskets as $basket) {
            foreach ($clientBaskets as $clientBasket) {
                if ($clientBasket->id == $basket->tenant_user_id) {
                    $product = (object)$basket->product;
                    $clientBasket->summary_count += $basket->count;

                    $price = ($product->price ?? 0) * $basket->count;
                    $clientBasket->summary_price += $price;
                    $allSummaryPrice += $price;
                    $allSummaryCount += $basket->count;
                    $clientBasket->basket[] = new BasketResource($basket);
                }
            }
        }


        return (object)[
            "summary_price" => $allSummaryPrice,
            "summary_count" => $allSummaryCount,
            "table" => new TableResource($table),
            "clients" => TenantUserResource::collection($table->clients ?? null),
            "basket" => $clientBaskets
        ];
    }

    public function changeTableWaiter($tableId)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant", "clients"])
            ->where("tenant_id", $tenant->id)
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Ошибка выбора столика!");

        $table->officiant_id = is_null($table->officiant_id) ? $tenantUser->id : null;
        $table->save();

        $table->refresh();

        return new TableResource($table);
    }

    public function waiterTableList($size = null): TableCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $size = $size ?? config('app.results_per_page');

        $tables = Table::query()
            ->with(["creator"])
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->where(function ($query) use ($tenantUser) {
                $query->where('officiant_id', $tenantUser->id)
                    ->orWhereNull("officiant_id");
            })
            ->orderBy("id", "asc")
            ->paginate($size);

        return new TableCollection($tables);
    }

    public function approvedSelfBasket(): BasketCollection
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $allProductsInBasket = Basket::query()
            ->with(["collection", "product"])
            ->where("tenant_user_id", $tenantUser->id)
            ->where("tenant_id", $tenant->id)
            ->whereNull("ordered_at")
            ->whereNotNull("table_approved_at")
            ->get();

        return new BasketCollection($allProductsInBasket);

    }

    /**
     * @throws HttpException
     */
    public function current($tableId = null): object
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();


        $tableWithClient = is_null($tableId) ?
            Table::query()
                ->with(["creator", "officiant"])
                ->where("tenant_id", $tenant->id)
                ->whereNull("closed_at")
                ->whereHas('clients', function ($query) use ($tenantUser) {
                    $query->where('id', $tenantUser->id);
                })->first() :
            Table::query()
                ->with(["creator", "officiant"])
                ->where("tenant_id", $tenant->id)
                ->where("id", $tableId)
                ->first();


        if (is_null($tableWithClient))
            throw new HttpException(404, "Увы, вы не заняли ни один из столиков!");

        return $this->getFullTableData($tableWithClient->id);
    }

    public function allOrders()
    {

    }

    public function callWaiter($tableId, $needPayment = false): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant"])
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Столик в данный момент не занят!");

        $tableNumber = $table->number ?? null;

        if (is_null($table->officiant_id ?? null)) {
            $thread = $tenant->topics["orders"] ?? null;


            MessageService::call()
                ->sendMessage([
                    "thread_id" => $thread,
                    "message" => "Клиент ждет официанта за столиком №" . ($tableNumber + 1) . ". Официант еще не назначен!",
                ]);


        } else {

            MessageService::call()
                ->sendMessage([

                    "message" => "Клиент ждет вас за столиком №" . ($tableNumber + 1) . "!" . ($needPayment ? "Клиент просит принести счет" : "")
                ]);

        }

    }

    public function requestApproveTable($tableId): void
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant"])
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Столик в данный момент не занят!");


        MessageService::call()
            ->sendMessage([
                "message" => "Один из клиентов за столиком №" . ($table->number + 1) . " сделал заказ и просит вас подтвердить его!",
            ]);


    }

    public function closeTable($tableId)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->with(["creator", "officiant"])
            ->where("tenant_id", $tenant->id)
            ->whereNull("closed_at")
            ->where("id", $tableId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Столик в данный момент не занят!");

        if (is_null($table->officiant_id ?? null))
            throw new HttpException(404, "В данный момент у столика нет официанта!");

        if (!is_null($table->closed_at ?? null))
            throw new HttpException(400, "Столик уже закрыт!");

        $table->closed_at = Carbon::now();
        $table->save();

        $basket = Basket::query()
            ->where("table_id", $table->id)
            ->get();

        foreach ($basket as $basketItem) {
            $basketItem->ordered_at = Carbon::now();
            $basketItem->save();
        }


        MessageService::call()
            ->sendMessage([
                "message" => "Спасибо за Ваш визит, ждем с нетерпением ещё! Пожалуйста, поставьте оценку нашей работе!",
                "keyboard" => [
                    [
                        ["text" => "😡", "callback_data" => "/send_review 0"],
                        ["text" => "😕", "callback_data" => "/send_review 1"],
                        ["text" => "😐", "callback_data" => "/send_review 2"],
                        ["text" => "🙂", "callback_data" => "/send_review 3"],
                        ["text" => "😁", "callback_data" => "/send_review 4"],
                    ]
                ]
            ]);

    }

    /**
     * @throws ValidationException
     */
    public function nearestBookingList(array $data)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $start = is_null($data['start_date'] ?? null) ? Carbon::now() : Carbon::parse($data['start_date']); // например "2025-11-01"
        $end = is_null($data['end_date'] ?? null) ? Carbon::now()->addWeek() : Carbon::parse($data['end_date']);   // например "2025-11-30

        // получаем все брони за период (без groupBy)
        $bookings = Table::query()
            ->whereBetween('booked_date_at', [$start, $end])
            ->where('booked_date_at', '>=', now()->toDateString())
            ->orderBy('booked_date_at')
            ->get();


// группируем и считаем количество броней на день через PHP
        $bookingCounts = $bookings->groupBy('booked_date_at')->map(function ($items, $date) {
            return [
                'date' => $date,
                'total' => $items->count(),
            ];
        });


        $bookings = Table::query()
            ->whereBetween('booked_date_at', [$start, $end])
            ->where('booked_date_at', '>=', now()->toDateString())
            ->orderBy('booked_date_at')
            ->get();

        return [
            "counts" => $bookingCounts,
            "bookings" => new TableCollection($bookings)
        ];
    }

    public function myUpcomingBookings()
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $now = Carbon::now();

        $tables = Table::query()
            ->where('creator_id', $tenantUser->id)
            ->whereRaw("
                STR_TO_DATE(CONCAT(booked_date_at, ' ', booked_time_at), '%Y-%m-%d %H:%i:%s') >= ?
            ", [$now])
            ->orderBy('booked_date_at')
            ->orderBy('booked_time_at')
            ->get();

        return new TableCollection($tables);
    }

    public function bookingList($tableNumber, $date = null)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $now = !is_null($date) ? Carbon::parse($date) : Carbon::now();

        $tables = Table::query()
            ->where('number', $tableNumber)
            ->whereRaw("STR_TO_DATE(CONCAT(booked_date_at, ' ', booked_time_at), '%Y-%m-%d %H:%i:%s') >= ?", [$now])
            ->get();

        return new TableCollection($tables);
    }

    /**
     * @throws ValidationException
     */
    public function exportNearestBookings(array $data)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();


        $validator = Validator::make($data, [
            "start_date" => "required",
            "end_date" => "required",

        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        /*BotMethods::bot()
            ->whereBot($tenant)
            ->sendDocument(
                $tenantUser->telegram_chat_id,
                "Выгрузка броней за период",
                InputFile::createFromContents(
                    Excel::raw(new BookingsExport($start, $end), \Maatwebsite\Excel\Excel::XLSX),
                    "Бронирование столиков.xlsx"
                )
            );*/
    }

    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function bookATable(array $data)
    {
        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $validator = Validator::make($data, [
            "name" => "required",
            "phone" => "required",
            "persons" => "required",
            "description" => "required",
            "table" => "required",
        ]);

        if ($validator->fails())
            throw new ValidationException($validator);

        $date = $data["date"];
        $time = $data["time"];

        $bookedAt = Carbon::parse($date . ' ' . $time);

        $table = json_decode($data["table"]);
        $number = $table->number;
        $persons = $data["persons"];

        $exists = Table::query()
            ->where('number', $number)
            ->get()
            ->filter(fn($table) => $table->booked_at->between($bookedAt, $bookedAt->addHours(2)))
            ->isNotEmpty();

        if ($exists) {
            $thread = $tenant->topics["callback"] ?? null;

            MessageService::call()
                ->sendMessage([
                    "thread_id" => $thread,
                    "message" => "Ваш столик #$number к сожалению занят $date в $time! С вами свяжется администратор для уточнения информации."
                ]);

            throw new HttpException("На данное время уже есть бронь!", 400);
        }

        $table = Table::query()
            ->create([
                "tenant_id" => $tenant->id,
                'creator_id' => $tenantUser->id,
                'number' => $number,
                "booked_date_at" => $date,
                "booked_time_at" => $time,
                "booked_info" => (object)[
                    "name" => $data["name"],
                    "phone" => $data["phone"],
                    "persons" => $persons,
                    "description" => $data["description"],
                    "table" => $data["table"],
                ],
            ]);
        $table->clients()->sync($tenantUser->id);
        $thread = $tenant->topics["orders"] ?? null;
        MessageService::call()
            ->sendMessage([
                "thread_id" => $thread,
                "message" => "Ваш столик #$number успешно забронирован на $date в $time на $persons чел.! "
            ]);


        return new TableResource($table);
    }


    /**
     * @throws ValidationException
     * @throws HttpException
     */
    public function cancelBookingTable($bookingId)
    {

        $tenant = app('tenant');
        $tenantUser = Auth::guard('tenant')->user();

        $table = Table::query()
            ->where('id', $bookingId)
            ->first();

        if (is_null($table))
            throw new HttpException(404, "Бронь не найдена!");

        $baskets = Basket::query()
            ->where("table_id", $table->id)
            ->get();

        foreach ($baskets as $basket)
            $basket->delete();

        $table->clients()->detach();

        $number = $table->number;
        $date = $table->booked_date_at;
        $time = $table->booked_time_at;

        $table->delete();
        $thread = $tenant->topics["callback"] ?? null;
        MessageService::call()
            ->sendMessage([
                "thread_id" => $thread,
                "message" => "Вы отменили бронь на столик #$number на дату $date $time."
            ]);

    }
}
