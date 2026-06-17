<?php

namespace App\Http\Controllers;

use App\Facades\TableService;
use App\Models\Tenant\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{

    /**
     * @throws ValidationException
     */
    public function tablePay(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            "table_id" => "required",
        ]);

        return response()
            ->json([
                "url" => TableService::call()
                    ->tablePay(
                        $request->all()
                    )
            ]);
    }

    public function sendOrderToMyChat(Request $request)
    {
        $request->validate([
            "table_id" => "required"
        ]);

        TableService::call()
            ->sendOrderToChat(
                $request->table_id ?? null,
            );

        return response()->noContent();
    }

    public function storeAdditionalService(Request $request)
    {
        $request->validate([
            "services" => "required",
            "table_id" => "required"
        ]);

        return TableService::call()
            ->storeAdditionalService(
                $request->table_id ?? null,
                $request->services ?? []
            );
    }

    public function changeBasketStatus(Request $request)
    {
        $request->validate([
            "table_id" => "required",
            "type" => "required"
        ]);

        return TableService::call()
            ->changeBasketStatus(
                $request->table_id ?? null,
                $request->type ?? 0
            );
    }

    public function changeTableWaiter(Request $request)
    {
        $request->validate([
            "table_id" => "required"
        ]);

        return TableService::call()
            ->changeTableWaiter($request->table_id ?? null);
    }

    public function loadTableData(Request $request): object
    {
        $request->validate([
            "table_id" => "required"
        ]);

        return TableService::call()
            ->getFullTableData($request->table_id ?? null);
    }

    public function currentTable(Request $request)
    {
        return TableService::call()
            ->current($request->table_id ?? null);
    }

    public function approvedSelfBasket(Request $request)
    {
        return TableService::call()
            ->approvedSelfBasket();
    }

    public function waiterTableList(Request $request)
    {
        return TableService::call()
            ->waiterTableList($request->size ?? null);
    }

    public function getAllTableOrders(Request $request)
    {

    }

    public function requestApproveTable(Request $request)
    {
        $request->validate([
            "table_id" => "required",
        ]);

        TableService::call()
            ->requestApproveTable($request->table_id ?? null);

        return response()->noContent();
    }

    public function callWaiter(Request $request)
    {
        $request->validate([
            "table_id" => "required",
        ]);

        TableService::call()
            ->callWaiter($request->table_id ?? null, $request->need_payment ?? false);

        return response()->noContent();
    }

    public function closeTable(Request $request)
    {
        $request->validate([
            "table_id" => "required",
        ]);

        TableService::call()
            ->closeTable($request->table_id ?? null);

        return response()->noContent();
    }

    public function nearestBookingList(Request $request)
    {
        return TableService::call()
            ->nearestBookingList($request->all());
    }

    public function myUpcomingBookings(Request $request)
    {
        return TableService::call()
            ->myUpcomingBookings();
    }

    public function bookingList(Request $request)
    {
        $request->validate([
            "number" => "required"
        ]);

        return TableService::call()
            ->bookingList($request->number ?? null, $request->date ?? null);
    }

    public function bookATable(Request $request)
    {
        return TableService::call()
            ->bookATable($request->all());
    }

    public function exportNearestBookings(Request $request)
    {
        TableService::call()
            ->exportNearestBookings($request->all());

        return response()->noContent();
    }

    /**
     * @throws \HttpException
     * @throws ValidationException
     */
    public function cancelBooking(Request $request, $bookingId)
    {
        TableService::call()
            ->cancelBookingTable($bookingId);

        return response()->noContent();
    }
}
