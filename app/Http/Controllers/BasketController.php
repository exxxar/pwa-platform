<?php

namespace App\Http\Controllers;

use App\Http\Resources\BasketCollection;
use App\Models\Tenant\Basket;
use App\Services\BasketService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BasketController extends Controller
{

    public function index()
    {
        $cartData = BasketService::call()->productsInBasket();

        // Если вы используете API Resources, можно передать $cartData['items'] в BasketCollection
        // return new BasketCollection(collect($cartData['items']));

        // Или вернуть сразу массив:
        return response()->json($cartData);
    }

    // app/Http/Controllers/BasketController.php
    public function addProduct(Request $request)
    {
        try {
            BasketService::call()->addAndIncrementProduct($request->all());
            $basketData = BasketService::call()->productsInBasket();

            return response()->json($basketData);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (HttpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка добавления товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function useWheelOfFortunePrize(Request $request)
    {
        $request->validate([
            "action_prize" => "required"
        ]);

        $bot = $request->bot ?? null;
        $botUser = $request->botUser ?? null;

        $actionId = $request->action_id;

        $actionPrize = (object)$request->action_prize;

        $selectedPrizeDescription = $actionPrize->description ?? 'Без описания приза';
        $selectedPrizeWinId = (!is_null($actionPrize->win ?? null) ? json_decode($actionPrize->win) : null)->id ?? null;
        $playedAt = $actionPrize->played_at ?? null;

        $action = ActionStatus::query()
            ->find($actionId ?? null);

        if (!is_null($action)) {
            $tmpData = $action->data ?? [];
            $processedPrizes = [];

            foreach ($tmpData as $index => $item) {
                $item = (object)$item;
                $itemPrizeWinId = (!is_null($item->win ?? null) ? json_decode($item->win) : null)->id ?? null;

                if ($item->description == $selectedPrizeDescription &&
                    $itemPrizeWinId == $selectedPrizeWinId &&
                    !is_null($selectedPrizeWinId)) {
                    // Проверяем, не был ли этот приз уже обработан
                    $prizeKey = $selectedPrizeDescription . '_' . $selectedPrizeWinId;
                    if (in_array($prizeKey, $processedPrizes)) {
                        continue; // Если приз уже есть, пропускаем
                    }
                    $processedPrizes[] = $prizeKey; // Добавляем в обработанные

                    $tmpData[$index]["taked_at"] = Carbon::now();
                    $itemPrizeType = $tmpData[$index]["type"] ?? "text";
                    $itemPrizeEffectedValue = $tmpData[$index]["effect_value"] ?? 0;
                    $itemPrizeEffectedProduct = $tmpData[$index]["effect_product"] ?? null;

                    switch ($itemPrizeType) {
                        default:
                        case "text":
                            $tmpUserLink = "\n<a href='tg://user?id=$botUser->telegram_chat_id'>Перейти к чату с пользователем</a>\n";
                            $thread = $bot->topics["actions"] ?? null;
                            $prizeText = "<em><b>" . ($item->description ?? '-') . "</b></em> - ручной режим выдачи\n";
                            BotMethods::bot()
                                ->whereBot($bot)
                                ->sendMessage($bot->order_channel,
                                    "Пользователь хочет получить свой приз из колеса фортуны: $prizeText $tmpUserLink",
                                    $thread);
                            sleep(1);
                            BotMethods::bot()
                                ->whereBot($bot)
                                ->sendMessage($botUser->telegram_chat_id,
                                    "Вы запросили получение приза <em><b>" . ($item->description ?? '-') . "</b></em>");
                            break;
                        case "effect_product":
                            $basket = \App\Models\Basket::query()
                                ->with('product')
                                ->where('bot_id', $bot->id)
                                ->where('bot_user_id', $botUser->id)
                                ->where('product_id', $itemPrizeEffectedProduct->id ?? null)
                                ->whereNull('ordered_at')
                                ->first();

                            if (is_null($basket) && !is_null($itemPrizeEffectedProduct)) {

                                $product = Product::query()
                                    ->find($itemPrizeEffectedProduct->id);

                                if (is_null($product))
                                    break;

                                $params = [];
                                $params["discount_price"] = $product->price - ($product->price * ($itemPrizeEffectedValue / 100));
                                $params["discount_amount"] = $product->price * ($itemPrizeEffectedValue / 100);

                                Basket::query()->create([
                                    "bot_id" => $bot->id,
                                    'bot_user_id' => $botUser->id,
                                    'product_id' => $product->id,
                                    'count' => 1,
                                    'params' => $params
                                ]);
                            }
                            break;
                        case "delivery_discount":
                            break;
                        case "product_discount":

                            $basket = \App\Models\Basket::query()
                                ->with('product')
                                ->where('bot_id', $bot->id)
                                ->where('bot_user_id', $botUser->id)
                                ->whereNull('ordered_at')
                                ->get();

                            foreach ($basket as $b) {
                                $params = $b->params ?? [];
                                $params["discount_price"] = $b->product->price - ($b->product->price * ($itemPrizeEffectedValue / 100));
                                $params["discount_amount"] = $b->product->price * ($itemPrizeEffectedValue / 100);

                                $b->params = $params;
                                $b->save();
                            }

                            break;
                        case "cashback":
                            $adminBotUser = BotUser::query()
                                ->where("bot_id", $this->bot->id)
                                ->where("is_admin", true)
                                ->first();

                            $userId = $this->botUser->user_id;

                            if (!is_null($adminBotUser))
                                event(new CashBackEvent(
                                    (int)$this->bot->id,
                                    (int)$userId,
                                    (int)$adminBotUser->user_id,
                                    ((float)$itemPrizeEffectedValue ?? 0),
                                    "Начисление баллов за колесо фортуны",
                                    CashBackDirectionEnum::Crediting
                                ));
                            break;
                    }
                }
            }

            $action->data = $tmpData;
            $action->save();
        }

        return response()->noContent();
    }

    public function loadProductsInBasket(Request $request)
    {

        return BasketService::call()
            ->productsInBasket($request->table_id ?? null);
    }

    /**
     * @throws ValidationException
     */
    public function commentProductInBasket(Request $request)
    {
        BasketService::call()
            ->addProductComment($request->all());

        return BasketService::call()
            ->productsInBasket();
    }

    /**
     * @throws ValidationException
     */
    public function incProductInBasket(Request $request)
    {
        BasketService::call()
            ->addAndIncrementProduct($request->all());

        return BasketService::call()
            ->productsInBasket();
    }

    /**
     * @throws ValidationException
     */
    public function incCollectionInBasket(Request $request)
    {
        $variantId = $request->variant_id ?? null;
        is_null($variantId) ?
            BasketService::call()
                ->addCollection($request->all()) :
            BasketService::call()
                ->incrementCollection($request->all());

        return BasketService::call()
            ->productsInBasket();
    }


    public function decProductInBasket(Request $request)
    {
        $request->validate([
            "product_id" => "required"
        ]);
        BasketService::call()
            ->decrementAndRemoveProduct($request->product_id ?? null);

        return BasketService::call()
            ->productsInBasket();
    }

    public function removeCollection(Request $request)
    {

        BasketService::call()
            ->removeCollectionFromBasket($request->all());

        return BasketService::call()
            ->productsInBasket();
    }

    public function decCollectionInBasket(Request $request)
    {

        BasketService::call()
            ->decrementCollection($request->all());

        return BasketService::call()
            ->productsInBasket();
    }


    public function clearBasket(Request $request)
    {
        BasketService::call()
            ->clearBasket();

        return BasketService::call()
            ->productsInBasket();
    }

    public function removeBasketItem(Request $request, $id)
    {
        BasketService::call()
            ->removeFromBasket($id);

        return BasketService::call()
            ->productsInBasket();

    }

    public function incrementItem(Request $request, $id)
    {
        BasketService::call()
            ->increment($id);

        return BasketService::call()
            ->productsInBasket();

    }

    public function decrementItem(Request $request, $id)
    {
        BasketService::call()
            ->decrement($id);

        return BasketService::call()
            ->productsInBasket();

    }

    /**
     * @throws ValidationException
     */
    public function checkout(Request $request)
    {
        return response()
            ->json(BasketService::call()
                ->checkout($request->all(),
                    $request->hasFile('photo') ? $request->file('photo') : null
                ));
    }
}
