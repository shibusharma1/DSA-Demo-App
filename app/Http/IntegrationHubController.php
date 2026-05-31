<?php

namespace App\Http\Controllers\Company\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Client;
use App\Product;
use App\Collection;
use App\UnitTypes;
use App\ErpNext;
use App\Employee;
use Auth;
use Spatie\WebhookServer\WebhookCall;
use Carbon\Carbon;


class IntegrationHubController extends Controller
{
    protected $base_url;
    protected $final_payload;
    protected $connectors;
    protected $company_id;
    protected $user_id;


    public function __construct()
    {
        // $this->middleware('auth');
        $this->base_url = config('constants.INTEGRATION_HUB_BASE_URL');
        $this->company_id = 1;
        $this->user_id = 1;
    }

    public function index()
    {
        return view('company.inventory.index');
    }

    public function getToken()
    {
        $company_id = config('settings.company_id');
        $get_company_settings = ErpNext::where('company_id', $company_id)->first();
        $data = [];
        if ($get_company_settings) {
            if ($get_company_settings->erpnextconnstat != 0) {
                $authEmp = Employee::where('company_id', $company_id)->where('user_id', Auth::user()->id)->first();
                $empId = $authEmp->id;
                $data['company_id'] = $get_company_settings->company_id;
                $data['user_id'] = $empId;
                $data['access_token'] = $get_company_settings->access_token;
                $data['refresh_token'] = $get_company_settings->refresh_token;
                $data['access_token_expires_at'] = $get_company_settings->access_token_expires_at;
                $data['refresh_token_expires_at'] = $get_company_settings->refresh_token_expires_at;
                $data['erpnextconnstat'] = $get_company_settings->erpnextconnstat;
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Details fetched',
            'data' => json_encode($data)
        ], 200);
    }

    public function saveToken(Request $request)
    {
        $company_id = config('settings.company_id');
        $decoded_value = json_decode($request->data);
        $get_company_settings = ErpNext::where('company_id', $company_id)->first();
        ErpNext::where('company_id', $company_id)->where('tokentype', 'erpnext')->update(['access_token' => getArrayValue($decoded_value, 'access_token')]);
        return response()->json([
            'status' => true,
            'message' => 'Details fetched',
        ], 200);
    }

    public function createClient($client_id, $m_type = '')
    {
        $client_info = Client::where('id', $client_id)->first();
        if ($client_info) {
            $timestamp = Carbon::now()->format('Y-m-d');
            $payload = [
                "id" => $client_info->id,
                "contact_name" => $client_info->name,
                "email" => $client_info->email,
                "company_name" => $client_info->company_name,
                "phone" => $client_info->phone,
                "mobile" => $client_info->mobile,
                "website" => $client_info->website,
                "address_1" => $client_info->address_1,
                "address_2" => $client_info->address_2,
                "location" => $client_info->location,
                "pin" => $client_info->pin,
                "pan" => $client_info->pan,
                "client_code" => $client_info->client_code,
                "business_id" => $client_info->business_id,
                "client_type" => $client_info->client_type,
                "due_amount" => $client_info->due_amount,
                "updated_at" => $timestamp,
                "created_at" => $timestamp,
            ];
            $m_action = ($m_type == 'update') ? 'customer.updated' : 'customer.created';
            $final_payload = $this->preparePayload(['erpnext'], $this->company_id, $this->user_id, $m_action, 'customer', $client_info->id, $payload);
            WebhookCall::create()
                ->url($this->base_url . 'api/v1/events')
                ->payload($final_payload)
                ->useSecret('DSApassword')
                ->dispatch();
            return true;
        }
        return;
    }


    public function createProduct($product_id, $m_type = '')
    {
        $product_info = Product::where('id', $product_id)->first();
        if ($product_info) {
            $timestamp = Carbon::now()->format('Y-m-d');
            $payload = [
                "id"  => $product_info->id,
                "product_name" =>  $product_info->product_name,
                "product_code" => $product_info->product_code,
                "mrp" => $product_info->mrp,
                "unit_name" => optional(UnitTypes::find($product_info->unit))->name ?? '',
                "status" => $product_info->status,
                "category_name" => $product_info->category_name,
                "details" => $product_info->details,
                "short_desc" => $product_info->short_desc,
                "inventory_available_quantity" => $product_info->inventory_available_quantity,
                "brand_name" => $product_info->brand_name,
                "brand" => $product_info->brand,
                "product_tax" => $product_info->product_tax,
                "variant_flag" => $product_info->variant_flag,
                "moq" => $product_info->moq,
                "app_visibility" => $product_info->app_visibility,
                "inventory_quantity_sold" => $product_info->inventory_quantity_sold,
                "conversion" => $product_info->conversion,
                "updated_at" => $timestamp,
                "created_at" => $timestamp,
            ];
            $m_action = ($m_type == 'update') ? 'item.updated' : 'item.created';
            $final_payload = $this->preparePayload(['erpnext'], $this->company_id, $this->user_id, $m_action, 'item', $product_info->id, $payload);
            WebhookCall::create()
                ->url($this->base_url . 'api/v1/events')
                ->payload($final_payload)
                ->useSecret('DSApassword')
                ->dispatch();
            return true;
        }
        return;
    }

    public function createCollection($coll_id, $m_type = '')
    {
        $collection_info = Collection::where('id', $coll_id)->first();
        if ($collection_info) {
            $timestamp = Carbon::now()->format('Y-m-d');
            $payload = [
                "id" => $collection_info->id,
                "customer_id" => $collection_info->client_id,
                "payment_received" => $collection_info->id,
                "payment_method" => "Cash",
                "payment_date" => $collection_info->id,
                "payment_status" => $collection_info->id,
                "cheque_no" => $collection_info->cheque_no,
                "payment_status_note" => $collection_info->payment_status_note,
                "payment_note" => $collection_info->payment_note,
                "cheque_date" => $collection_info->cheque_date,
                "due_payment" => $collection_info->due_payment,
                "status" => $collection_info->status,
                "collection_types_id" => $collection_info->collection_types_id,
                "include_in_credit" => $collection_info->include_in_credit,
                "bank_id" => $collection_info->bank_id,
                "bank_name" => $collection_info->bank_name,
                "updated_at" => $timestamp,
                "created_at" => $timestamp,
            ];
            $m_action = ($m_type == 'update') ? 'payment.updated' : 'payment.created';
            $final_payload = $this->preparePayload(['erpnext'], $this->company_id, $this->user_id, $m_action, 'payment', $collection_info->id, $payload);
            $webhok = WebhookCall::create()
                ->url($this->base_url . 'api/v1/events')
                ->payload($final_payload)
                ->useSecret('DSApassword')
                ->dispatch();
            return true;
        }
        return;
    }


    protected function preparePayload($connectors, $company_id, $user_id, $eventType, $entityType, $entityId, $payload)
    {
        $final_payload = [
            'connectors'  => $connectors,
            'company_id'  => $company_id,
            'user_id'     => $user_id,
            'event_type'  => $eventType,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'data'     => $payload,
        ];
        return $final_payload;
    }
}
