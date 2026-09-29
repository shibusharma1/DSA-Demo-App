<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionImage;
use App\Models\CollectionType;
use App\Models\Bank;
use App\Models\PartyBusy;
use App\Services\busy\DsaToBusyCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class CollectionController extends Controller
{
    /**
     * Display collections.
     */
    public function index(Request $request): View
    {
        // $companyId = company_id();
        $companyId = 1;

        $query = Collection::query()
            ->with([
                'client'
            ])
            ->where(
                'company_id',
                $companyId
            );

        /*
         * Search.
         */
        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'payment_method',
                    'like',
                    "%{$search}%"
                );

                $q->orWhere(
                    'busycollection_id',
                    'like',
                    "%{$search}%"
                );

                $q->orWhereHas(
                    'client',
                    function ($clientQuery) use ($search) {

                        $clientQuery
                            ->where(
                                'company_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            });
        }

        /*
         * BUSY status filter.
         */
        if ($request->filled('busy_sync_status')) {

            $query->where(
                'busy_sync_status',
                $request->busy_sync_status
            );
        }

        /*
         * Payment method filter.
         */
        if ($request->filled('payment_method')) {

            $query->where(
                'payment_method',
                $request->payment_method
            );
        }

        /*
         * Date filter.
         */
        if ($request->filled('from_date')) {

            $query->whereDate(
                'payment_date',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {

            $query->whereDate(
                'payment_date',
                '<=',
                $request->to_date
            );
        }

        $collections = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'collections.index',
            compact('collections')
        );
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $companyId = 1;

        /*
         * Only show clients that belong to
         * current company and already have BUSY party ID.
         */
        $clients = PartyBusy::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereNotNull(
                'busyparty_id'
            )
            ->where(
                'busyparty_id',
                '!=',
                ''
            )
            ->orderBy(
                'company_name'
            )
            ->get();

        // $collectionTypes =
        //     CollectionType::where(
        //         'company_id',
        //         $companyId
        //     )
        //         ->orderBy('name')
        //         ->get();

        // $banks =
        //     Bank::where(
        //         'company_id',
        //         $companyId
        //     )
        //         ->orderBy('name')
        //         ->get();

         $collectionTypes = [
            (object) ['id' => 1, 'name' => 'Cash'],
            (object) ['id' => 2, 'name' => 'Bank'],
        ];
         $banks = [
            (object) ['id' => 1, 'name' => 'SBL'],
            (object) ['id' => 2, 'name' => 'Nabil'],
        ];

        return view(
            'collections.create',
            compact(
                'clients',
                'collectionTypes',
                'banks'
            )
        );
    }

    /**
     * Store collection.
     */
    public function store(
        Request $request,
        DsaToBusyCollection $busyCollection
    ): RedirectResponse {

        $companyId = 1;

        $validated =
            $this->validateCollection(
                $request
            );

        /*
         * Make sure selected client belongs
         * to current company.
         */
        $client = PartyBusy::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'id',
                $validated['client_id']
            )
            ->firstOrFail();

        /*
         * BUSY party is mandatory because
         * this collection is automatically synced.
         */
        if (
            empty(
                $client->busyparty_id
            )
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Selected party is not synchronized with BUSY.'
                );
        }

        DB::beginTransaction();

        try {

            $collection =
                Collection::create([
                    'company_id' =>
                        $companyId,

                    'client_id' =>
                        $client->id,

                    'payment_received' =>
                        $validated[
                            'payment_received'
                        ],

                    'due_payment' =>
                        $validated[
                            'due_payment'
                        ] ?? 0,

                    'payment_method' =>
                        $validated[
                            'payment_method'
                        ] ?? 'Cash',

                    'payment_note' =>
                        $validated[
                            'payment_note'
                        ] ?? null,

                    'status' =>
                        $validated[
                            'status'
                        ] ?? 'Pending',

                    'payment_date' =>
                        $validated[
                            'payment_date'
                        ],

                    'next_date' =>
                        $validated[
                            'next_date'
                        ] ?? null,

                    'bank_id' =>
                        $validated[
                            'bank_id'
                        ] ?? null,

                    'cheque_no' =>
                        $validated[
                            'cheque_no'
                        ] ?? null,

                    'cheque_date' =>
                        $validated[
                            'cheque_date'
                        ] ?? null,

                    'payment_status' =>
                        $validated[
                            'payment_status'
                        ] ?? 'Received',

                    'payment_status_note' =>
                        $validated[
                            'payment_status_note'
                        ] ?? null,

                    'collection_types_id' =>
                        $validated[
                            'collection_types_id'
                        ] ?? null,

                    'include_in_credit' =>
                        $request->boolean(
                            'include_in_credit'
                        ),

                    'busy_sync_status' =>
                        'pending',
                ]);

            /*
             * Upload up to 10 images.
             */
            $this->storeImages(
                $request,
                $collection
            );

            DB::commit();

            /*
             * Sync after local transaction succeeds.
             */
            $sync =
                $busyCollection->sync(
                    $collection->fresh()
                );

            if ($sync['success']) {

                return redirect()
                    ->route(
                        'collections.index'
                    )
                    ->with(
                        'success',
                        'Collection created and synchronized with BUSY successfully.'
                    );
            }

            return redirect()
                ->route(
                    'collections.index'
                )
                ->with(
                    'warning',
                    'Collection was saved locally, but BUSY synchronization failed: '
                    . (
                        $sync['message']
                        ?? 'Unknown error.'
                    )
                );

        } catch (Throwable $e) {

            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to create collection: '
                    . $e->getMessage()
                );
        }
    }

    /**
     * Display a collection.
     */
    public function show(
        Collection $collection
    ): View {

        $this->ensureCompanyCollection(
            $collection
        );

        $collection->load([
            'client',
            'collectionType',
            'bank',
            'order',
            'images',
        ]);

        return view(
            'collections.show',
            compact('collection')
        );
    }

    /**
     * Show edit form.
     */
    public function edit(
        Collection $collection
    ): View {

        $this->ensureCompanyCollection(
            $collection
        );

        $companyId = 1;

        $clients = PartyBusy::query()
            ->where(
                'company_id',
                $companyId
            )
            ->whereNotNull(
                'busyparty_id'
            )
            ->where(
                'busyparty_id',
                '!=',
                ''
            )
            ->orderBy(
                'company_name'
            )
            ->get();

        $collectionTypes =
            CollectionType::where(
                'company_id',
                $companyId
            )
                ->orderBy('name')
                ->get();

        $banks =
            Bank::where(
                'company_id',
                $companyId
            )
                ->orderBy('name')
                ->get();

        $collection->load('images');

        return view(
            'collections.edit',
            compact(
                'collection',
                'clients',
                'collectionTypes',
                'banks'
            )
        );
    }

    /**
     * Update collection.
     */
    public function update(
        Request $request,
        Collection $collection,
        DsaToBusyCollection $busyCollection
    ): RedirectResponse {

        $this->ensureCompanyCollection(
            $collection
        );

        $companyId = 1;

        $validated =
            $this->validateCollection(
                $request,
                $collection
            );

        $client = PartyBusy::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'id',
                $validated['client_id']
            )
            ->firstOrFail();

        if (
            empty(
                $client->busyparty_id
            )
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Selected party is not synchronized with BUSY.'
                );
        }

        DB::beginTransaction();

        try {

            $collection->update([
                'client_id' =>
                    $client->id,

                'payment_received' =>
                    $validated[
                        'payment_received'
                    ],

                'due_payment' =>
                    $validated[
                        'due_payment'
                    ] ?? 0,

                'payment_method' =>
                    $validated[
                        'payment_method'
                    ] ?? 'Cash',

                'payment_note' =>
                    $validated[
                        'payment_note'
                    ] ?? null,

                'status' =>
                    $validated[
                        'status'
                    ] ?? 'Pending',

                'payment_date' =>
                    $validated[
                        'payment_date'
                    ],

                'next_date' =>
                    $validated[
                        'next_date'
                    ] ?? null,

                'bank_id' =>
                    $validated[
                        'bank_id'
                    ] ?? null,

                'cheque_no' =>
                    $validated[
                        'cheque_no'
                    ] ?? null,

                'cheque_date' =>
                    $validated[
                        'cheque_date'
                    ] ?? null,

                'payment_status' =>
                    $validated[
                        'payment_status'
                    ] ?? 'Received',

                'payment_status_note' =>
                    $validated[
                        'payment_status_note'
                    ] ?? null,

                'collection_types_id' =>
                    $validated[
                        'collection_types_id'
                    ] ?? null,

                'include_in_credit' =>
                    $request->boolean(
                        'include_in_credit'
                    ),

                /*
                 * Force re-sync status after modification.
                 */
                'busy_sync_status' =>
                    'pending',

                'busy_sync_message' =>
                    null,
            ]);

            /*
             * New images.
             */
            $this->storeImages(
                $request,
                $collection
            );

            /*
             * Delete selected existing images.
             */
            $this->deleteSelectedImages(
                $request,
                $collection
            );

            DB::commit();

            /*
             * Sync updated collection to BUSY.
             */
            $sync =
                $busyCollection->sync(
                    $collection->fresh()
                );

            if ($sync['success']) {

                return redirect()
                    ->route(
                        'collections.index'
                    )
                    ->with(
                        'success',
                        'Collection updated and synchronized with BUSY successfully.'
                    );
            }

            return redirect()
                ->route(
                    'collections.index'
                )
                ->with(
                    'warning',
                    'Collection was updated locally, but BUSY synchronization failed: '
                    . (
                        $sync['message']
                        ?? 'Unknown error.'
                    )
                );

        } catch (Throwable $e) {

            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to update collection: '
                    . $e->getMessage()
                );
        }
    }

    /**
     * Delete collection.
     */
    public function destroy(
        Collection $collection
    ): RedirectResponse {

        $this->ensureCompanyCollection(
            $collection
        );

        try {

            /*
             * We soft-delete the DSA collection.
             *
             * We DO NOT delete the BUSY voucher here
             * because your supplied BUSY API reference does
             * not provide a delete voucher endpoint.
             */
            $collection->delete();

            return redirect()
                ->route(
                    'collections.index'
                )
                ->with(
                    'success',
                    'Collection deleted successfully.'
                );

        } catch (Throwable $e) {

            report($e);

            return back()
                ->with(
                    'error',
                    'Unable to delete collection: '
                    . $e->getMessage()
                );
        }
    }

    /**
     * Manually sync collection to BUSY.
     */
    public function syncBusy(
        Collection $collection,
        DsaToBusyCollection $busyCollection
    ): RedirectResponse {

        $this->ensureCompanyCollection(
            $collection
        );

        $sync =
            $busyCollection->sync(
                $collection
            );

        if ($sync['success']) {

            return back()
                ->with(
                    'success',
                    $sync['message']
                );
        }

        return back()
            ->with(
                'error',
                $sync['message']
            );
    }

    /**
     * Validation.
     */
    protected function validateCollection(
        Request $request,
        ?Collection $collection = null
    ): array {

        return $request->validate([

            'client_id' => [
                'required',
                'integer',
            ],

            'payment_received' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'due_payment' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                'string',
                'max:100',
            ],

            'payment_note' => [
                'nullable',
                'string',
            ],

            'next_date' => [
                'nullable',
                'date',
            ],

            'bank_id' => [
                'nullable',
                'integer',
            ],

            'cheque_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cheque_date' => [
                'nullable',
                'date',
            ],

            'payment_status' => [
                'nullable',
                'string',
                'max:100',
            ],

            'payment_status_note' => [
                'nullable',
                'string',
            ],

            'collection_types_id' => [
                'nullable',
                'integer',
            ],

            'status' => [
                'nullable',
                'string',
                'max:100',
            ],

            'include_in_credit' => [
                'nullable',
                'boolean',
            ],

            /*
             * Maximum 10 images.
             */
            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'delete_images' => [
                'nullable',
                'array',
            ],

            'delete_images.*' => [
                'integer',
            ],
        ]);
    }

    /**
     * Store collection images.
     */
    protected function storeImages(
        Request $request,
        Collection $collection
    ): void {

        if (
            !$request->hasFile(
                'images'
            )
        ) {
            return;
        }

        $currentCount =
            $collection->images()->count();

        $files =
            $request->file('images');

        /*
         * Never allow more than 10.
         */
        $availableSlots =
            10 - $currentCount;

        if ($availableSlots <= 0) {
            return;
        }

        $files =
            array_slice(
                $files,
                0,
                $availableSlots
            );

        $sortOrder =
            ($collection->images()->max(
                'sort_order'
            ) ?? -1) + 1;

        foreach ($files as $file) {

            $path =
                $file->store(
                    'collections/' .
                    $collection->id,
                    'public'
                );

            CollectionImage::create([
                'collection_id' =>
                    $collection->id,

                'image' =>
                    basename($path),

                'image_path' =>
                    $path,

                'sort_order' =>
                    $sortOrder++,
            ]);
        }
    }

    /**
     * Delete selected images.
     */
    protected function deleteSelectedImages(
        Request $request,
        Collection $collection
    ): void {

        $ids =
            $request->input(
                'delete_images',
                []
            );

        if (empty($ids)) {
            return;
        }

        $images =
            CollectionImage::where(
                'collection_id',
                $collection->id
            )
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        foreach ($images as $image) {

            if (
                $image->image_path &&
                Storage::disk('public')->exists(
                    $image->image_path
                )
            ) {
                Storage::disk('public')->delete(
                    $image->image_path
                );
            }

            $image->delete();
        }
    }

    /**
     * Tenant protection.
     */
    protected function ensureCompanyCollection(
        Collection $collection
    ): void {

        if (
            (int) $collection->company_id
            !== 1
        ) {
            abort(404);
        }
    }
}